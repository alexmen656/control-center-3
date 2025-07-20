<?php
include "head.php";
include "ai_config.php"; // AI API Keys laden

/**
 * ChatGPT-basierte Schema-Generierung
 * Nutzt OpenAI GPT mit structured outputs für zuverlässige Ergebnisse
 */

class AISchemaGenerator {
    
    private $openaiApiKey;
    
    public function __construct() {
        // API Key aus Umgebungsvariablen oder Config
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }
    
    /**
     * Generiert Schema mit OpenAI GPT mit Structured Output
     */
    public function generateSchema($description, $checkForms='false', $context = '', $project = '') {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleSchema($description);
        }
        
        $existingForms = [];
        if ($checkForms === 'true' && !empty($project)) {
            $existingForms = $this->getExistingForms($project);
        }
        
        $prompt = $this->buildPrompt($description, $context, $existingForms);
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Datenbankdesign und Formularerstellung. Analysiere die Benutzerbeschreibung und erstelle ein passendes Datenbankschema mit sinnvollen Feldern, Typen und Optionen.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'form_schema',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Der Titel des Formulars'
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Beschreibung was das Formular macht'
                            ],
                            'inputs' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'description' => 'Datenbankfeldname (lowercase, underscore), keine Sonderzeichen außer Unterstrichen erlaubt'
                                        ],
                                        'type' => [
                                            'type' => 'string',
                                            'enum' => ['text', 'email', 'number', 'textarea', 'select', 'select2', 'checkbox', 'date', 'time'],
                                            'description' => 'Feldtyp - select2 für Referenzen zu anderen Formularen'
                                        ],
                                        'label' => [
                                            'type' => 'string',
                                            'description' => 'Anzeige-Label für den Benutzer'
                                        ],
                                        'required' => [
                                            'type' => 'boolean',
                                            'description' => 'Ob das Feld Pflicht ist'
                                        ],
                                        'options' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string'
                                            ],
                                            'description' => 'Optionen für select-Felder oder Form-Name für select2'
                                        ]
                                    ],
                                    'required' => ['name', 'type', 'label', 'required', 'options'],
                                    'additionalProperties' => false
                                ]
                            ]
                        ],
                        'required' => ['title', 'description', 'inputs'],
                        'additionalProperties' => false
                    ]
                ]
            ],
            'temperature' => 0.3
        ];
        
        $response = $this->makeOpenAIRequest($data);
        
        if ($response) {
            return $this->parseResponse($response);
        }
        
        return $this->generateSimpleSchema($description);
    }
    
    private function buildPrompt($description, $context, $existingForms = []) {
        $formsContext = "";
        
        if (!empty($existingForms)) {
            $formsContext = "\n\nBEREITS VORHANDENE FORMULARE/TABELLEN IN DIESEM PROJEKT:\n";
            foreach ($existingForms as $form) {
                $formsContext .= "TABELLE: " . $form['title'] . "\n";
                $formsContext .= "FELDER: ";
                foreach ($form['inputs'] as $field) {
                    $formsContext .= $field['name'] . " (" . $field['type'] . ", " . $field['label'] . "), ";
                }
                $formsContext = rtrim($formsContext, ', ') . "\n\n";
            }
            
            $formsContext .= "WICHTIG: Nutze NUR existierende Tabellen für SELECT2!\n";
            $formsContext .= "- Verfügbare Tabellen für select2-Referenzen: " . implode(', ', array_column($existingForms, 'title')) . "\n";
            $formsContext .= "- SELECT2 Format: \"tabellenname\" als einzige Option (String, nicht Object!)\n";
            $formsContext .= "- NIEMALS nicht-existierende Tabellen referenzieren!\n";
            $formsContext .= "- Wenn keine passende Tabelle existiert → verwende normales SELECT oder TEXT\n\n";
        } else {
            $formsContext = "\n\nKEINE BESTEHENDEN FORMULARE VORHANDEN!\n";
            $formsContext .= "- Verwende KEIN SELECT2, da keine Tabellen zum Referenzieren existieren\n";
            $formsContext .= "- Nutze nur: text, email, number, textarea, select, checkbox, date, time\n\n";
        }
       
        
        return "Analysiere diese Beschreibung und erstelle ein VOLLSTÄNDIGES, PRAXISTAUGLICHES Datenbankschema:

BESCHREIBUNG: $description" . ($context ? "\n\nZUSÄTZLICHER KONTEXT: $context" : "") . $formsContext . "

WICHTIGE REGELN:
1. Denke an ALLE Felder die ein echtes Business braucht - nicht nur das Minimum!
2. Bei E-Commerce/Shop: Name, Preis, Beschreibung, Kategorie, Bilder, Lagerbestand, SKU/Artikelnummer
3. Bei Kunden: Vor-/Nachname, Email, Telefon, Adresse, Kundentyp, Registrierungsdatum
4. Bei Produkten: Detaillierte Eigenschaften je nach Produkttyp
5. Verwende realistische deutsche Select-Optionen
6. Mindestens 4-8 Felder für praktische Nutzung
7. Datenbankfeldnamen: lowercase_mit_underscores

FELDTYPEN VERSTEHEN:
- text, email, number, textarea, date, time = Standard-Eingabefelder
- select = Dropdown mit festen Optionen (z.B. Kategorien: Elektronik, Kleidung, Bücher)
- select2 = Referenz zu einem anderen Formular (Foreign Key) - VERWENDE DAS für Verknüpfungen!
- checkbox = Ja/Nein Felder

SELECT2 BEISPIELE (nur wenn passende Tabelle existiert):
- Wenn Autos-Tabelle existiert → auto_id (select2, options: [\"autos\"])
- select2 options sind IMMER strings: [\"tabellenname1\", \"tabellenname2\"]
- Wenn Kunden-Tabelle existiert → kunde_id (select2, options: [\"kunden\"])
- NIEMALS erfundene Tabellen verwenden!

SELECT2 JSON FORMAT:
- Typ: \"select2\"  
- Options: [\"exact_table_name\"] (nur ein String mit Tabellennamen)
- Beispiel: {\"name\": \"auto_id\", \"type\": \"select2\", \"options\": [\"autos\"]}

BEISPIEL FÜR 'BANKNOTEN VERKAUFEN':
- name (Banknotenname)
- denomination (Nennwert) 
- currency (Währung - select: EUR, USD, CHF, etc.)
- year (Ausgabejahr)
- condition (Zustand - select: Kassenfrisch, Sehr gut, Gut, etc.)
- price (Verkaufspreis)
- quantity (Verfügbare Anzahl)
- description (Detailbeschreibung)
- image_url (Bildpfad)
- serial_number (Seriennummer)
- category (Kategorie - select: Historisch, Modern, Sammler, etc.)

AUFGABE:
Erstelle ein Schema mit MINDESTENS 6-10 sinnvollen Feldern.
Denke wie ein Geschäftsinhaber: Was brauche ich wirklich zum Verwalten?
Sei großzügig mit nützlichen Feldern - lieber zu viele als zu wenige!

Feldtypen: text, email, number, textarea, select, select2, checkbox, date, time

WICHTIG: Wenn bestehende Formulare vorhanden sind, verwende SELECT2 für Verknüpfungen!
Beispiel: Lagerbestand braucht Produktreferenz → verwende select2 statt alle Produktdaten zu duplizieren";
    }
    
    private function makeOpenAIRequest($data) {
        $jsonData = json_encode($data);
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey,
                    'User-Agent: Mozilla/5.0 (compatible; ChatGPT-Schema-Generator)'
                ],
                'method' => 'POST',
                'content' => $jsonData,
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);
        
        // Debug Info
        if ($response === false) {
            $error = error_get_last();
            error_log("OpenAI API Error: Failed to make request - " . ($error ? $error['message'] : 'Unknown error'));
            error_log("OpenAI API Debug: allow_url_fopen=" . (ini_get('allow_url_fopen') ? 'true' : 'false'));
            error_log("OpenAI API Debug: openssl_loaded=" . (extension_loaded('openssl') ? 'true' : 'false'));
            return null;
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            error_log("OpenAI API Error: Invalid JSON response: " . substr($response, 0, 500));
            return null;
        }
        
        if (isset($decoded['error'])) {
            error_log("OpenAI API Error: " . json_encode($decoded['error']));
            return null;
        }
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }
        
        // Structured output
        if (isset($decoded['choices'][0]['message']['parsed'])) {
            return json_encode($decoded['choices'][0]['message']['parsed']);
        }
        
        error_log("OpenAI API Error: Unexpected response format: " . json_encode($decoded));
        return null;
    }
    
    private function parseResponse($response) {
        $decoded = json_decode($response, true);
        
        if ($decoded && isset($decoded['title']) && isset($decoded['inputs'])) {
            return $this->validateSchema($decoded);
        }
        
        return null;
    }
    
    private function validateSchema($schema) {
        // Validiere und bereinige das Schema
        if (!isset($schema['title'])) $schema['title'] = 'Neues Formular';
        if (!isset($schema['description'])) $schema['description'] = 'ChatGPT-generiertes Formular';
        if (!isset($schema['inputs']) || !is_array($schema['inputs'])) {
            $schema['inputs'] = [];
        }
        
        // Validiere jedes Input-Feld
        $validTypes = ['text', 'email', 'number', 'textarea', 'select', 'select2', 'checkbox', 'date', 'time'];
        
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
            
            // Validiere select2 spezifisch
            if ($input['type'] === 'select2') {
                if (!isset($input['options']) || !is_array($input['options']) || empty($input['options'])) {
                    // Kein options Array oder leer → konvertiere zu text
                    error_log("Schema Validation: select2 ohne gültige options → konvertiert zu text");
                    $input['type'] = 'text';
                    $input['options'] = [];
                } else {
                    // Prüfe ob Form existiert (jetzt als String, nicht Object)
                    $referenced_form = $input['options'][0] ?? '';
                    if (!$this->formExists($referenced_form)) {
                        error_log("Schema Validation: select2 referenziert nicht-existierende Form '$referenced_form' → konvertiert zu text");
                        $input['type'] = 'text';
                        $input['options'] = [];
                    } else {
                        // Konvertiere String zu Object für Frontend
                        $input['options'] = [['value' => $referenced_form]];
                    }
                }
            }
            
            // Bereinige den Namen für Datenbank-Kompatibilität
            $input['name'] = preg_replace('/[^a-z0-9_]/', '_', strtolower($input['name']));
        }
        
        return $schema;
    }
    
    private function formExists($formName) {
        // Implementiere Form-Existenz-Check
        // Für jetzt return true, kann später erweitert werden
        return true;
    }
    
    /**
     * Lade bestehende Formulare des Projekts
     */
    private function getExistingForms($project) {
        $formsQuery = "SELECT * FROM form_settings WHERE project = '" . escape_string($project) . "'";
        $formsResult = query($formsQuery);
        
        $existingForms = [];
        while ($row = fetch_assoc($formsResult)) {
            $formData = json_decode($row['form_json'], true);
            if ($formData && isset($formData['title']) && isset($formData['inputs'])) {
                $existingForms[] = $formData;
            }
        }
        
        return $existingForms;
    }
    
    /**
     * Einfaches Fallback-Schema wenn kein API Key vorhanden
     */
    private function generateSimpleSchema($description) {
        $description = strtolower($description);
        $title = $this->extractTitle($description);
        
        // Basis-Felder je nach erkannten Keywords
        $inputs = [];
        
        if (strpos($description, 'produkt') !== false || strpos($description, 'artikel') !== false) {
            $inputs = [
                ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'price', 'type' => 'number', 'label' => 'Preis', 'required' => true],
                ['name' => 'category', 'type' => 'select', 'label' => 'Kategorie', 'required' => false, 'options' => ['Elektronik', 'Kleidung', 'Bücher', 'Sport']],
                ['name' => 'description', 'type' => 'textarea', 'label' => 'Beschreibung', 'required' => false]
            ];
        } elseif (strpos($description, 'kunde') !== false || strpos($description, 'kontakt') !== false) {
            $inputs = [
                ['name' => 'first_name', 'type' => 'text', 'label' => 'Vorname', 'required' => true],
                ['name' => 'last_name', 'type' => 'text', 'label' => 'Nachname', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'label' => 'E-Mail', 'required' => true],
                ['name' => 'phone', 'type' => 'text', 'label' => 'Telefon', 'required' => false]
            ];
        } elseif (strpos($description, 'mitarbeiter') !== false || strpos($description, 'personal') !== false) {
            $inputs = [
                ['name' => 'employee_id', 'type' => 'text', 'label' => 'Mitarbeiter-ID', 'required' => true],
                ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'department', 'type' => 'select', 'label' => 'Abteilung', 'required' => true, 'options' => ['IT', 'Marketing', 'Verkauf', 'HR']],
                ['name' => 'start_date', 'type' => 'date', 'label' => 'Startdatum', 'required' => false]
            ];
        } else {
            // Standard-Schema
            $inputs = [
                ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'description', 'type' => 'textarea', 'label' => 'Beschreibung', 'required' => false],
                ['name' => 'created_date', 'type' => 'date', 'label' => 'Datum', 'required' => false]
            ];
        }
        
        return [
            'title' => $title,
            'description' => 'Schema für: ' . $description,
            'inputs' => $inputs
        ];
    }
    
    private function extractTitle($description) {
        if (strpos($description, 'produkt') !== false) return 'Produktverwaltung';
        if (strpos($description, 'kunde') !== false) return 'Kundenverwaltung';
        if (strpos($description, 'mitarbeiter') !== false) return 'Mitarbeiterverwaltung';
        if (strpos($description, 'veranstaltung') !== false) return 'Veranstaltungsverwaltung';
        if (strpos($description, 'aufgabe') !== false) return 'Aufgabenverwaltung';
        
        return 'Formularverwaltung';
    }
}

// API Endpoints
if (isset($_POST['generate_ai_schema'])) {
    $description = escape_string($_POST['description'] ?? '');
    $context = escape_string($_POST['context'] ?? '');
    $checkForms = escape_string($_POST['checkForms'] ?? '');
    $project = escape_string($_POST['project'] ?? '');

    
    if (empty($description)) {
        echo json_encode([
            'success' => false,
            'message' => 'Beschreibung ist erforderlich'
        ]);
        exit;
    }
    
    $generator = new AISchemaGenerator();
    $schema = $generator->generateSchema($description, $checkForms, $context, $project);
    
    if ($schema) {
        echo json_encode([
            'success' => true,
            'schema' => $schema
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
                'message' => 'ChatGPT-generiertes Formular erfolgreich erstellt!',
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
        case 'select2':
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
