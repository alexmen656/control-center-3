<?php
/**
 * CSS-Optimizer in PHP
 * Entfernt ungenutzte CSS-Klassen aus einer CSS-Datei basierend auf HTML-Dateien
 */

class CssOptimizer {
    /**
     * Analysiert CSS und entfernt ungenutzte Klassen
     * 
     * @param string $cssFile Pfad zur CSS-Datei
     * @param array $htmlFiles Array mit Pfaden zu HTML-Dateien
     * @param string $outputFile Speicherort für optimierte CSS
     * @return bool Erfolg der Operation
     */
    public function optimize($cssFile, $htmlFiles, $outputFile) {
        // Überprüfen, ob die Dateien existieren
        if (!file_exists($cssFile)) {
            echo "CSS-Datei nicht gefunden: $cssFile\n";
            return false;
        }

        if (empty($htmlFiles)) {
            echo "Keine HTML-Dateien angegeben\n";
            return false;
        }

        // CSS-Inhalt laden
        $cssContent = file_get_contents($cssFile);
        if (!$cssContent) {
            echo "Konnte CSS-Datei nicht lesen\n";
            return false;
        }

        // HTML-Dateien laden und zusammenführen
        $htmlContent = '';
        $processedFiles = 0;
        foreach ($htmlFiles as $htmlFile) {
            if (file_exists($htmlFile)) {
                $htmlContent .= file_get_contents($htmlFile) . ' ';
                $processedFiles++;
            }
        }

        echo "$processedFiles HTML-Dateien für die Analyse verarbeitet.\n";

        if ($processedFiles === 0) {
            echo "Keine HTML-Dateien konnten verarbeitet werden\n";
            return false;
        }

        // CSS in einzelne Regeln aufteilen
        $cssRules = $this->parseCssRules($cssContent);
        echo "Insgesamt " . count($cssRules) . " CSS-Regeln gefunden.\n";

        // Verwendete Selektoren finden
        $usedSelectors = [];
        $keepRules = [];
        $removedCount = 0;

        // Immer grundlegende HTML-Elemente behalten
        $alwaysKeepSelectors = ['body', 'html', '.container', '.row', '.col', '.active', '.show', 
            'a', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'section', 'header', 'footer', 
            'img', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'td', 'th'];

        foreach ($cssRules as $rule) {
            $selectors = explode(',', $rule['selector']);
            $keepRule = false;

            // Überprüfen der Selektoren
            foreach ($selectors as $selector) {
                $selector = trim($selector);
                
                // Grundlegende Selektoren immer behalten
                if ($this->matchesAnyPattern($selector, $alwaysKeepSelectors)) {
                    $keepRule = true;
                    break;
                }

                // Prüfen auf Klassen, IDs oder Elemente
                if (preg_match('/(\.[\w-]+|#[\w-]+|^[a-z]+)/', $selector, $matches)) {
                    $extractedSelector = $matches[0];
                    
                    // Bereinigen des Selektors für den Vergleich
                    if (strpos($extractedSelector, '.') === 0) {
                        $classToFind = substr($extractedSelector, 1); // Entferne den Punkt für .class
                        if (strpos($htmlContent, "class=\"") !== false && 
                            (preg_match('/class=("|\')[^"\']*' . preg_quote($classToFind, '/') . '[^"\']*("|\')/', $htmlContent) || 
                            preg_match('/class=("|\')[^"\']*' . preg_quote($classToFind, '/') . '[^"\']*("|\')/', $htmlContent))) {
                            $keepRule = true;
                            break;
                        }
                    } elseif (strpos($extractedSelector, '#') === 0) {
                        $idToFind = substr($extractedSelector, 1); // Entferne das # für #id
                        if (strpos($htmlContent, "id=\"$idToFind\"") !== false || 
                            strpos($htmlContent, "id='$idToFind'") !== false) {
                            $keepRule = true;
                            break;
                        }
                    } else {
                        // Einfache HTML-Elemente
                        if (strpos($htmlContent, "<$extractedSelector") !== false) {
                            $keepRule = true;
                            break;
                        }
                    }
                }
                
                // Wiederholende Muster wie .btn-*, .col-* usw. beibehalten
                // Dies berücksichtigt Framework-Klassen wie Bootstrap
                $commonPatterns = ['/\.btn-/', '/\.col-/', '/\.nav-/', '/\.alert-/', '/\.badge-/', '/\.text-/', '/\.bg-/'];
                foreach ($commonPatterns as $pattern) {
                    if (preg_match($pattern, $selector)) {
                        $keepRule = true;
                        break 2;
                    }
                }
            }

            if ($keepRule) {
                $keepRules[] = $rule['raw'];
            } else {
                $removedCount++;
            }
        }

        // Optimierte CSS erstellen
        $optimizedCss = implode("\n", $keepRules);
        
        // In Datei speichern
        if (file_put_contents($outputFile, $optimizedCss) !== false) {
            echo "Optimierte CSS erstellt: $outputFile\n";
            echo "$removedCount von " . count($cssRules) . " CSS-Regeln entfernt.\n";
            echo "CSS-Größe vor der Optimierung: " . strlen($cssContent) . " Bytes\n";
            echo "CSS-Größe nach der Optimierung: " . strlen($optimizedCss) . " Bytes\n";
            return true;
        } else {
            echo "Fehler beim Speichern der optimierten CSS\n";
            return false;
        }
    }

    /**
     * Prüft, ob ein Selektor einem der Muster entspricht
     */
    private function matchesAnyPattern($selector, $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($selector, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Zerlegt CSS in einzelne Regeln
     * 
     * @param string $css CSS-Inhalt
     * @return array Array mit CSS-Regeln
     */
    private function parseCssRules($css) {
        // Kommentare entfernen
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Regeln extrahieren
        $rules = [];
        
        // Durchsuche nach CSS-Selektoren und ihren Deklarationsblöcken
        preg_match_all('/([^{]+){([^}]*)}/', $css, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (isset($match[1]) && isset($match[2])) {
                $selector = trim($match[1]);
                $declarations = trim($match[2]);
                
                // Media Queries und Keyframes separat behandeln
                if (strpos($selector, '@media') === 0 || strpos($selector, '@keyframes') === 0) {
                    // Media Queries und Keyframes immer beibehalten
                    $rules[] = [
                        'selector' => $selector,
                        'declarations' => $declarations,
                        'raw' => $match[0]
                    ];
                } else {
                    $rules[] = [
                        'selector' => $selector,
                        'declarations' => $declarations,
                        'raw' => $match[0]
                    ];
                }
            }
        }
        
        return $rules;
    }
}

/**
 * Führt die CSS-Optimierung für ein Projekt durch
 * 
 * @param int $projectId Die ID des Projekts
 * @return bool Erfolg der Operation
 */
function optimizeCssForProject($projectId) {
    $projectId = (int)$projectId;
    
    if ($projectId <= 0) {
        echo "Ungültige Projekt-ID\n";
        return false;
    }
    
    $publishedDir = __DIR__ . "/published/$projectId/";
    $cssFile = __DIR__ . "/assets/styles.css";
    $outputFile = $publishedDir . "styles.css";
    
    if (!is_dir($publishedDir)) {
        echo "Projektordner nicht gefunden: $publishedDir\n";
        return false;
    }
    
    if (!file_exists($cssFile)) {
        echo "CSS-Datei nicht gefunden: $cssFile\n";
        return false;
    }
    
    // HTML-Dateien finden
    $htmlFiles = glob($publishedDir . "*.html");
    
    if (empty($htmlFiles)) {
        echo "Keine HTML-Dateien im Ordner gefunden: $publishedDir\n";
        return false;
    }
    
    echo "Starte CSS-Optimierung für Projekt $projectId...\n";
    echo "HTML-Dateien gefunden: " . count($htmlFiles) . "\n";
    
    $optimizer = new CssOptimizer();
    return $optimizer->optimize($cssFile, $htmlFiles, $outputFile);
}

// Wenn das Skript direkt aufgerufen wird
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    if (isset($argv[1])) {
        $projectId = (int)$argv[1];
        if ($projectId > 0) {
            optimizeCssForProject($projectId);
        } else {
            echo "Bitte eine gültige Projekt-ID angeben.\n";
        }
    } else if (isset($_GET['project_id'])) {
        $projectId = (int)$_GET['project_id'];
        if ($projectId > 0) {
            optimizeCssForProject($projectId);
        } else {
            echo "Bitte eine gültige Projekt-ID angeben.\n";
        }
    } else {
        echo "Bitte eine Projekt-ID als Parameter oder über project_id angeben.\n";
    }
}
?>