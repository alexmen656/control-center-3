<?php
include "head.php";

/**
 * Echte AI-gestützte Schema-Generierung
 * Nutzt externe AI APIs um intelligente Datenbankschemas zu generieren
 */

class AISchemaGenerator {
    
    private $openaiApiKey;
    private $anthropicApiKey;
    
    public function __construct() {
        // API Keys aus Umgebungsvariablen oder Config
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
        $this->anthropicApiKey = getenv('ANTHROPIC_API_KEY') ?: '';
    }
    
    /**
     * Generiert Schema mit OpenAI GPT
     */
    public function generateWithOpenAI($description, $context = '') {
        if (empty($this->openaiApiKey)) {
            return $this->generateFallbackSchema($description);
        }
        
        $prompt = $this->buildPrompt($description, $context);
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Datenbankdesign. Erstelle JSON-Schemas für Formulare basierend auf Benutzerbeschreibungen. Antworte NUR mit gültigem JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1500,
            'temperature' => 0.7
        ];
        
        $response = $this->makeOpenAIRequest($data);
        
        if ($response) {
            return $this->parseAIResponse($response);
        }
        
        return $this->generateFallbackSchema($description);
    }
    
    /**
     * Generiert Schema mit Anthropic Claude
     */
    public function generateWithClaude($description, $context = '') {
        if (empty($this->anthropicApiKey)) {
            return $this->generateFallbackSchema($description);
        }
        
        $prompt = $this->buildPrompt($description, $context);
        
        $data = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 1500,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $response = $this->makeClaudeRequest($data);
        
        if ($response) {
            return $this->parseAIResponse($response);
        }
        
        return $this->generateFallbackSchema($description);
    }
    
    /**
     * Lokale AI-Alternative mit regelbasierter Logik
     */
    public function generateWithLocalAI($description, $context = '') {
        // Analysiere den Text und extrahiere wichtige Begriffe
        $analysis = $this->analyzeDescription($description);
        
        // Generiere Schema basierend auf der Analyse
        $schema = $this->buildSchemaFromAnalysis($analysis, $description);
        
        return $schema;
    }
    
    private function buildPrompt($description, $context) {
        return "Erstelle ein JSON-Schema für ein Datenbankformular basierend auf dieser Beschreibung: '$description'
        
        " . ($context ? "Zusätzlicher Kontext: $context" : "") . "
        
        Das JSON sollte diese Struktur haben:
        {
            \"title\": \"Titel des Formulars\",
            \"description\": \"Beschreibung was das Formular macht\",
            \"inputs\": [
                {
                    \"name\": \"feldname\",
                    \"type\": \"text|email|number|textarea|select|checkbox|date|time\",
                    \"label\": \"Anzeige-Label\",
                    \"required\": true|false,
                    \"options\": [\"option1\", \"option2\"] // nur bei select
                }
            ]
        }
        
        Berücksichtige:
        - Deutsche Labels und Beschreibungen
        - Sinnvolle Feldtypen
        - Realistische Optionen bei Select-Feldern
        - Welche Felder required sein sollten
        - Typische Felder für diese Art von Daten
        
        Antworte NUR mit dem JSON, keine zusätzlichen Erklärungen.";
    }
    
    private function makeOpenAIRequest($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openaiApiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $decoded = json_decode($response, true);
            return $decoded['choices'][0]['message']['content'] ?? null;
        }
        
        return null;
    }
    
    private function makeClaudeRequest($data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->anthropicApiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $decoded = json_decode($response, true);
            return $decoded['content'][0]['text'] ?? null;
        }
        
        return null;
    }
    
    private function parseAIResponse($response) {
        // Versuche JSON aus der Antwort zu extrahieren
        $response = trim($response);
        
        // Entferne mögliche Markdown-Code-Blöcke
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        $decoded = json_decode($response, true);
        
        if ($decoded && isset($decoded['title']) && isset($decoded['inputs'])) {
            // Validiere und bereinige das Schema
            return $this->validateAndCleanSchema($decoded);
        }
        
        return null;
    }
    
    private function validateAndCleanSchema($schema) {
        // Stelle sicher, dass alle erforderlichen Felder vorhanden sind
        if (!isset($schema['title'])) $schema['title'] = 'Neues Formular';
        if (!isset($schema['description'])) $schema['description'] = 'AI-generiertes Formular';
        if (!isset($schema['inputs']) || !is_array($schema['inputs'])) {
            $schema['inputs'] = [];
        }
        
        // Validiere jedes Input-Feld
        $validTypes = ['text', 'email', 'number', 'textarea', 'select', 'checkbox', 'date', 'time'];
        
        foreach ($schema['inputs'] as &$input) {
            if (!isset($input['name'])) continue;
            if (!isset($input['type']) || !in_array($input['type'], $validTypes)) {
                $input['type'] = 'text';
            }
            if (!isset($input['label'])) {
                $input['label'] = ucfirst($input['name']);
            }
            if (!isset($input['required'])) {
                $input['required'] = false;
            }
            
            // Bereinige den Namen für Datenbank-Kompatibilität
            $input['name'] = preg_replace('/[^a-z0-9_]/', '_', strtolower($input['name']));
        }
        
        return $schema;
    }
    
    /**
     * Lokale AI-Alternative: Analysiert Beschreibung und extrahiert Begriffe
     */
    private function analyzeDescription($description) {
        $description = strtolower($description);
        $words = preg_split('/[\s,\.;!?]+/', $description);
        
        $analysis = [
            'domain' => $this->detectDomain($description),
            'keywords' => $this->extractKeywords($words),
            'entities' => $this->extractEntities($description),
            'actions' => $this->extractActions($words)
        ];
        
        return $analysis;
    }
    
    private function detectDomain($description) {
        $domains = [
            'ecommerce' => ['shop', 'produkt', 'bestell', 'kauf', 'verkauf', 'preis', 'artikel'],
            'crm' => ['kunde', 'kontakt', 'verkauf', 'lead', 'vertrieb'],
            'hr' => ['mitarbeiter', 'personal', 'gehalt', 'urlaub', 'anwesenheit'],
            'project' => ['projekt', 'aufgabe', 'task', 'deadline', 'team'],
            'inventory' => ['lager', 'bestand', 'artikel', 'inventar', 'stock'],
            'event' => ['veranstaltung', 'event', 'termin', 'buchung', 'ticket'],
            'finance' => ['rechnung', 'zahlung', 'kosten', 'budget', 'finanz'],
            'support' => ['ticket', 'support', 'problem', 'anfrage', 'hilfe']
        ];
        
        foreach ($domains as $domain => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    return $domain;
                }
            }
        }
        
        return 'general';
    }
    
    private function extractKeywords($words) {
        $commonWords = ['der', 'die', 'das', 'und', 'oder', 'ein', 'eine', 'für', 'mit', 'von', 'zu', 'im', 'am', 'ist', 'sind', 'wird', 'werden'];
        $keywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 2 && !in_array($word, $commonWords);
        });
        
        return array_slice(array_unique($keywords), 0, 10);
    }
    
    private function extractEntities($description) {
        $entities = [];
        
        // Einfache Entitätserkennung
        $patterns = [
            'person' => '/\b(kunde|mitarbeiter|benutzer|person|nutzer)\b/i',
            'product' => '/\b(produkt|artikel|item|ware)\b/i',
            'order' => '/\b(bestellung|order|auftrag)\b/i',
            'event' => '/\b(event|veranstaltung|termin)\b/i',
            'project' => '/\b(projekt|project)\b/i'
        ];
        
        foreach ($patterns as $entity => $pattern) {
            if (preg_match($pattern, $description)) {
                $entities[] = $entity;
            }
        }
        
        return $entities;
    }
    
    private function extractActions($words) {
        $actionWords = ['erstell', 'verwalt', 'bearbeit', 'hinzufüg', 'lösch', 'änder', 'speicher'];
        $actions = [];
        
        foreach ($words as $word) {
            foreach ($actionWords as $action) {
                if (strpos($word, $action) !== false) {
                    $actions[] = $action;
                }
            }
        }
        
        return array_unique($actions);
    }
    
    private function buildSchemaFromAnalysis($analysis, $description) {
        $domain = $analysis['domain'];
        $entities = $analysis['entities'];
        
        // Basis-Schema je nach Domain
        $schema = $this->getBaseSchemaForDomain($domain);
        
        // Erweitere Schema basierend auf erkannten Entitäten
        $schema = $this->enhanceSchemaWithEntities($schema, $entities, $analysis['keywords']);
        
        // Setze Titel und Beschreibung
        $schema['title'] = $this->generateTitle($analysis['keywords']);
        $schema['description'] = "AI-generiertes Schema für: " . $description;
        
        return $schema;
    }
    
    private function getBaseSchemaForDomain($domain) {
        $baseSchemas = [
            'ecommerce' => [
                'inputs' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Produktname', 'required' => true],
                    ['name' => 'price', 'type' => 'number', 'label' => 'Preis', 'required' => true],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'Beschreibung', 'required' => false]
                ]
            ],
            'crm' => [
                'inputs' => [
                    ['name' => 'first_name', 'type' => 'text', 'label' => 'Vorname', 'required' => true],
                    ['name' => 'last_name', 'type' => 'text', 'label' => 'Nachname', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'label' => 'E-Mail', 'required' => true]
                ]
            ],
            'hr' => [
                'inputs' => [
                    ['name' => 'employee_id', 'type' => 'text', 'label' => 'Mitarbeiter-ID', 'required' => true],
                    ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                    ['name' => 'department', 'type' => 'select', 'label' => 'Abteilung', 'options' => ['IT', 'Marketing', 'Verkauf'], 'required' => true]
                ]
            ]
        ];
        
        return $baseSchemas[$domain] ?? ['inputs' => [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
            ['name' => 'description', 'type' => 'textarea', 'label' => 'Beschreibung', 'required' => false]
        ]];
    }
    
    private function enhanceSchemaWithEntities($schema, $entities, $keywords) {
        // Füge spezifische Felder basierend auf Entitäten hinzu
        $additionalFields = [];
        
        if (in_array('person', $entities)) {
            $additionalFields[] = ['name' => 'phone', 'type' => 'text', 'label' => 'Telefon', 'required' => false];
            $additionalFields[] = ['name' => 'address', 'type' => 'textarea', 'label' => 'Adresse', 'required' => false];
        }
        
        if (in_array('order', $entities)) {
            $additionalFields[] = ['name' => 'order_date', 'type' => 'date', 'label' => 'Bestelldatum', 'required' => true];
            $additionalFields[] = ['name' => 'total_amount', 'type' => 'number', 'label' => 'Gesamtbetrag', 'required' => true];
        }
        
        if (in_array('event', $entities)) {
            $additionalFields[] = ['name' => 'event_date', 'type' => 'date', 'label' => 'Veranstaltungsdatum', 'required' => true];
            $additionalFields[] = ['name' => 'location', 'type' => 'text', 'label' => 'Ort', 'required' => true];
        }
        
        // Füge Keywords als potentielle Felder hinzu
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 3 && !$this->fieldExists($schema['inputs'], $keyword)) {
                $fieldType = $this->guessFieldType($keyword);
                $additionalFields[] = [
                    'name' => $keyword,
                    'type' => $fieldType,
                    'label' => ucfirst($keyword),
                    'required' => false
                ];
            }
        }
        
        $schema['inputs'] = array_merge($schema['inputs'], array_slice($additionalFields, 0, 5));
        
        return $schema;
    }
    
    private function fieldExists($inputs, $name) {
        foreach ($inputs as $input) {
            if ($input['name'] === $name) {
                return true;
            }
        }
        return false;
    }
    
    private function guessFieldType($keyword) {
        $numberKeywords = ['preis', 'anzahl', 'menge', 'kosten', 'betrag', 'gehalt'];
        $textareaKeywords = ['beschreibung', 'notiz', 'kommentar', 'text'];
        $emailKeywords = ['email', 'mail'];
        $dateKeywords = ['datum', 'date', 'termin'];
        
        foreach ($numberKeywords as $nk) {
            if (strpos($keyword, $nk) !== false) return 'number';
        }
        
        foreach ($textareaKeywords as $tk) {
            if (strpos($keyword, $tk) !== false) return 'textarea';
        }
        
        foreach ($emailKeywords as $ek) {
            if (strpos($keyword, $ek) !== false) return 'email';
        }
        
        foreach ($dateKeywords as $dk) {
            if (strpos($keyword, $dk) !== false) return 'date';
        }
        
        return 'text';
    }
    
    private function generateTitle($keywords) {
        if (empty($keywords)) return 'Neues Formular';
        
        $primaryKeyword = ucfirst($keywords[0]);
        return $primaryKeyword . ' Verwaltung';
    }
    
    private function generateFallbackSchema($description) {
        // Fallback wenn AI APIs nicht verfügbar sind
        return $this->generateWithLocalAI($description);
    }
}

// API Endpoints
if (isset($_POST['generate_ai_schema'])) {
    $description = escape_string($_POST['description'] ?? '');
    $context = escape_string($_POST['context'] ?? '');
    $provider = escape_string($_POST['provider'] ?? 'local');
    
    if (empty($description)) {
        echo json_encode([
            'success' => false,
            'message' => 'Beschreibung ist erforderlich'
        ]);
        exit;
    }
    
    $generator = new AISchemaGenerator();
    
    switch ($provider) {
        case 'openai':
            $schema = $generator->generateWithOpenAI($description, $context);
            break;
        case 'claude':
            $schema = $generator->generateWithClaude($description, $context);
            break;
        case 'local':
        default:
            $schema = $generator->generateWithLocalAI($description, $context);
            break;
    }
    
    if ($schema) {
        echo json_encode([
            'success' => true,
            'schema' => $schema,
            'provider_used' => $provider
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Generieren des Schemas'
        ]);
    }
}

if (isset($_POST['create_ai_form']) && isset($_POST['schema']) && isset($_POST['name']) && isset($_POST['project'])) {
    $schema = json_decode($_POST['schema'], true);
    $formName = escape_string($_POST['name']);
    $project = escape_string($_POST['project']);
    
    if (!$schema || !isset($schema['inputs'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Ungültiges Schema-Format'
        ]);
        exit;
    }
    
    $formJSON = json_encode($schema);
    
    // Create form in database
    if (query("INSERT INTO form_settings (form_name, form_json, project) VALUES ('$formName', '$formJSON', '$project')")) {
        // Create database table
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
        
        $sql = "CREATE TABLE $tableName (id INT AUTO_INCREMENT PRIMARY KEY";
        
        foreach ($schema['inputs'] as $field) {
            $name = $field['name'];
            $type = mapFieldType($field['type']);
            $sql .= ", $name $type";
        }
        $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP);";
        
        if (query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'AI-generiertes Formular erfolgreich erstellt!',
                'table_name' => $tableName,
                'form_name' => $formName
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Erstellen der Datenbanktabelle'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern des Formulars'
        ]);
    }
}

function mapFieldType($type) {
    switch ($type) {
        case 'text':
        case 'email':
        case 'select':
        case 'time':
        case 'date':
            return 'VARCHAR(255)';
        case 'textarea':
            return 'TEXT';
        case 'number':
            return 'DECIMAL(10,2)';
        case 'checkbox':
            return 'BOOLEAN';
        default:
            return 'VARCHAR(255)';
    }
}
?>
