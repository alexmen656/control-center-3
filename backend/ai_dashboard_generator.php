<?php
require_once "head.php";
require_once "ai_config.php";

class AIDashboardGenerator
{

    private $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    /**
     * Generiert Dashboard mit OpenAI GPT
     */
    public function generateDashboard($description, $availableTables, $project)
    {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleDashboard($availableTables);
        }

        $prompt = $this->buildPrompt($description, $availableTables);

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Dashboard-Experte. Analysiere verfügbare Tabellen und erstelle ein relevantes Dashboard mit passenden Charts basierend auf der Benutzerbeschreibung.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'dashboard_schema',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'dashboard_title' => [
                                'type' => 'string'
                            ],
                            'charts' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'chart_type' => [
                                            'type' => 'string',
                                            'enum' => ['pie_chart', 'donut_chart', 'bar_chart']
                                        ],
                                        'form' => [
                                            'type' => 'string'
                                        ],
                                        'label' => [
                                            'type' => 'string'
                                        ],
                                        'data' => [
                                            'type' => 'string'
                                        ]
                                    ],
                                    'required' => ['chart_type', 'form', 'label', 'data'],
                                    'additionalProperties' => false
                                ]
                            ]
                        ],
                        'required' => ['dashboard_title', 'charts'],
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

        return $this->generateSimpleDashboard($availableTables);
    }

    private function buildPrompt($description, $availableTables)
    {
        $formsInfo = "";
        foreach ($availableTables as $form) {
            // Prüfe verschiedene mögliche Strukturen
            $tableData = $form;
            if (isset($form['table'])) {
                $tableData = $form['table'];
            }

            if (!isset($tableData['title']) || !isset($tableData['inputs'])) {
                continue; // Überspringe ungültige Form-Daten
            }

            $formsInfo .= "FORMULAR: " . $tableData['title'] . "\n";
            $formsInfo .= "FELDER: ";
            foreach ($tableData['inputs'] as $field) {
                if (isset($field['name']) && isset($field['type']) && isset($field['label'])) {
                    $formsInfo .= $field['name'] . " (" . $field['type'] . ", " . $field['label'] . "), ";
                }
            }
            $formsInfo = rtrim($formsInfo, ', ') . "\n\n";
        }
        /* echo "AUFGABE: Erstelle ein Dashboard für: $description

 VERFÜGBARE FORMULARE UND FELDER:
 $formsInfo

 CHART-AUSWAHL REGELN:
 - pie_chart/donut_chart: Für Kategorien, Status, Bewertungen
 - bar_chart: Für Vergleiche zwischen Kategorien
 - date_bar_chart: Nur wenn 'created_at' oder Datumsfelder vorhanden sind

 FIELD-AUSWAHL REGELN:
 - LABEL-Feld: Text/Select-Felder für Beschriftungen (z.B. marke, kategorie, status)
 - DATA-Feld: NUR Number-Felder für Werte! Niemals Text-Felder als Data verwenden!
 - WICHTIG: Wenn kein Number-Feld vorhanden ist, dann zähle die Einträge (data = label, aber trotzdem wird gezählt)
 - Bei date_bar_chart: date_stamps = 'days' ist Standard

 CHART-LOGIK:
 - pie_chart/donut_chart: label = Kategorie-Feld, data = Anzahl der Einträge pro Kategorie
 - bar_chart: label = Kategorie-Feld, data = Anzahl der Einträge pro Kategorie  
 - Wenn Number-Feld vorhanden: data = Number-Feld (z.B. preis, anzahl, bewertung)
 - Wenn KEIN Number-Feld: data = label (System zählt automatisch Einträge)

 WICHTIG:
 - Verwende nur EXISTIERENDE Feldnamen aus den verfügbaren Tabellen
 - Erstelle 2-4 sinnvolle Charts
 - Jeder Chart muss ein anderes Tabelle oder andere Felder nutzen
 - Denke praktisch: Was würde ein Business Owner sehen wollen?

 BEISPIEL DASHBOARD für 'Auto-Datenbank':
 - pie_chart: Autos nach Marke (form: autos, label: marke, data: marke) → System zählt: Fiat=5, VW=3, Audi=2
 - bar_chart: Autos nach Farbe (form: autos, label: farbe, data: farbe) → System zählt Einträge
 - Wenn Preis-Feld existiert: bar_chart: Durchschnittspreis nach Marke (form: autos, label: marke, data: preis)";
 */
        return "AUFGABE: Erstelle ein Dashboard für: $description

VERFÜGBARE FORMULARE UND FELDER:
$formsInfo

CHART-AUSWAHL REGELN:
- pie_chart/donut_chart: Für Kategorien, Status, Bewertungen
- bar_chart: Für Vergleiche zwischen Kategorien
- date_bar_chart: Nur wenn 'created_at' oder Datumsfelder vorhanden sind

FIELD-AUSWAHL REGELN:
- LABEL-Feld: Text/Select-Felder für Beschriftungen (z.B. marke, kategorie, status)
- DATA-Feld: NUR Number-Felder für Werte! Niemals Text-Felder als Data verwenden!
- Bei date_bar_chart: date_stamps = 'days' ist Standard

CHART-LOGIK:
- pie_chart/donut_chart: label = Kategorie-Feld, data = Anzahl der Einträge pro Kategorie
- bar_chart: label = Kategorie-Feld, data = Anzahl der Einträge pro Kategorie  
- Wenn Number-Feld vorhanden: data = Number-Feld (z.B. preis, anzahl, bewertung)
- Wenn KEIN Number-Feld: data = label (System zählt automatisch Einträge)

WICHTIG:
- Verwende nur EXISTIERENDE Feldnamen aus den verfügbaren Tabellen
- Erstelle 2-4 sinnvolle Charts
- Denke praktisch: Was würde ein Business Owner sehen wollen?

BEISPIEL DASHBOARD für 'Auto-Datenbank':
- pie_chart: Autos nach Marke (form: autos, label: marke, data: marke) → System zählt: Fiat=5, VW=3, Audi=2
- bar_chart: Autos nach Farbe (form: autos, label: farbe, data: farbe) → System zählt Einträge
- Wenn Preis-Feld existiert: bar_chart: Durchschnittspreis nach Marke (form: autos, label: marke, data: preis)

🚫 ABSOLUT VERBOTEN: NIEMALS data = label verwenden!

BEISPIELE WAS VERBOTEN IST:
❌ label: 'marke', data: 'marke' 
❌ label: 'farbe', data: 'farbe'
❌ label: 'status', data: 'status'

BEISPIELE WAS ERLAUBT IST:
✅ label: 'marke', data: 'preis'
✅ label: 'marke', data: 'anzahl' 
✅ label: 'marke', data: 'model'
✅ label: 'status', data: 'marke'

REGEL: data-Feld MUSS IMMER anders sein als label-Feld!";

        /*
        - Jeder Chart muss ein anderes Tabelle oder andere Felder nutzen
        - WICHTIG: Wenn kein Number-Feld vorhanden ist, dann zähle die Einträge (data = label, aber trotzdem wird gezählt)

        NOCHMAL WICHTIG: 
        - data-Feld kann Text sein, aber System ZAEHLT die Einträge automatisch!
        - Marke-Feld mit Werten Fiat, VW, Audi wird zu Chart-Daten: Fiat=Anzahl, VW=Anzahl, Audi=Anzahl*/
    }

    private function makeOpenAIRequest($data)
    {
        $jsonData = json_encode($data);

        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey,
                    'User-Agent: Mozilla/5.0 (compatible; Dashboard-Generator)'
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

        if ($response === false) {
            error_log("Dashboard AI Error: API request failed");
            return null;
        }

        $decoded = json_decode($response, true);

        if (!$decoded || isset($decoded['error'])) {
            error_log("Dashboard AI Error: " . json_encode($decoded['error'] ?? 'Invalid response'));
            return null;
        }

        // Structured output
        if (isset($decoded['choices'][0]['message']['parsed'])) {
            return json_encode($decoded['choices'][0]['message']['parsed']);
        }

        // Fallback für content
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return null;
    }

    private function parseResponse($response)
    {
        $decoded = json_decode($response, true);

        if (!$decoded) {
            return null;
        }

        return $decoded;
    }

    private function generateSimpleDashboard($availableTables)
    {
        if (empty($availableTables)) {
            return null;
        }

        $charts = [];

        // Erstelle einfache Charts für die ersten 3 Tabellen
        foreach (array_slice($availableTables, 0, 3) as $form) {
            // Prüfe verschiedene mögliche Strukturen
            $tableData = $form;
            if (isset($form['table'])) {
                $tableData = $form['table'];
            }

            if (!isset($tableData['title']) || !isset($tableData['inputs'])) {
                continue; // Überspringe ungültige Form-Daten
            }

            $tableName = $this->toName($tableData['title']);
            $fields = $tableData['inputs'];

            if (empty($fields))
                continue;

            // Suche nach geeigneten Feldern
            $textField = null;
            $numberField = null;

            foreach ($fields as $field) {
                if (!isset($field['name']) || !isset($field['type']))
                    continue;

                if (in_array($field['type'], ['text', 'select']) && !$textField) {
                    $textField = $field['name'];
                }
                if ($field['type'] === 'number' && !$numberField) {
                    $numberField = $field['name'];
                }
            }

            // Erstelle Chart
            if ($textField) {
                $charts[] = [
                    'chart_type' => count($charts) % 2 === 0 ? 'pie_chart' : 'bar_chart',
                    'form' => $tableName,
                    'label' => $textField,
                    'data' => $numberField ?: $textField
                ];
            }
        }

        return [
            'dashboard_title' => 'Automatisch generiertes Dashboard',
            'charts' => $charts
        ];
    }

    private function toName($name)
    {
        return createTableName($name);
    }
}

// API Endpoints
if (isset($_POST['generate_dashboard']) && isset($_POST['project'])) {
    $description = escape_string($_POST['description'] ?? '');
    $project = escape_string($_POST['project']);

    // Lade verfügbare Tabellen - verwende dieselbe Query wie table.php
    $formsQuery = "SELECT * FROM table_settings WHERE project = '$project'";
    $formsResult = query($formsQuery);

    $availableTables = [];
    $i = 0;
    while ($row = fetch_assoc($formsResult)) {
        $availableTables[$i]['id'] = $row['table_id'];
        $availableTables[$i]['table'] = json_decode($row['table_json'], true);
        $availableTables[$i]['createdOn'] = $row['created_at'];
        $i++;
    }

    // Debug: Log wie viele Forms gefunden wurden und deren Struktur
    error_log("Dashboard Generator Debug - Found " . count($availableTables) . " forms");
    if (!empty($availableTables)) {
        error_log("Dashboard Generator Debug - First form structure: " . json_encode($availableTables[0]));
    }

    $generator = new AIDashboardGenerator();
    $dashboard = $generator->generateDashboard($description, $availableTables, $project);

    if ($dashboard) {
        echo json_encode($dashboard);
    } else {
        echo json_encode(['error' => 'Dashboard konnte nicht generiert werden']);
    }

} elseif (isset($_POST['create_ai_dashboard']) && isset($_POST['project']) && isset($_POST['dashboard_config'])) {
    // Dashboard tatsächlich erstellen
    $project = escape_string($_POST['project']);
    $dashboardConfig = json_decode($_POST['dashboard_config'], true);

    if (!$dashboardConfig || !isset($dashboardConfig['charts'])) {
        echo json_encode(['error' => 'Ungültige Dashboard-Konfiguration']);
        exit;
    }

    // Erstelle neues Dashboard
    $projectQuery = "SELECT * FROM projects WHERE link='$project'";
    $projectResult = query($projectQuery);

    if (mysqli_num_rows($projectResult) !== 1) {
        echo json_encode(['error' => 'Projekt nicht gefunden']);
        exit;
    }

    $projectID = fetch_assoc($projectResult)['projectID'];
    $dashboardName = "ai-dashboard-" . substr(md5(uniqid()), 0, 7);
    $dashboardTitle = $dashboardConfig['dashboard_title'] ?? 'AI Dashboard';

    // Charts als JSON speichern
    $chartsJson = json_encode($dashboardConfig['charts']);

    // Dashboard in DB erstellen
    $insertDashboard = query("INSERT INTO control_center_dashboards VALUES (0, '$dashboardName', '$chartsJson', '$project', NOW(), NOW())");

    if ($insertDashboard) {
        // Als Tool hinzufügen
        $link = createLink($dashboardTitle);
        $toolQuery = query("INSERT INTO project_tools VALUES (0,'bar-chart-outline','$dashboardTitle', '$link',0,'','$projectID')");

        if ($toolQuery) {
            // Seite erstellen
            $url = "project/" . createLink($project) . "/dashboard/" . $dashboardName;
            query("INSERT INTO control_center_pages VALUES (0,'$url', 'true','bar-chart-outline','$dashboardTitle', '', 0)");

            echo json_encode([
                'success' => true,
                'dashboard_name' => $dashboardName,
                'dashboard_url' => $url
            ]);
        } else {
            echo json_encode(['error' => 'Tool konnte nicht erstellt werden']);
        }
    } else {
        echo json_encode(['error' => 'Dashboard konnte nicht in Datenbank erstellt werden']);
    }
}
?>