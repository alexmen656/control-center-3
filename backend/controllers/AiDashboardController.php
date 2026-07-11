<?php

class AiDashboardController
{
    private string $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    public function generate(Request $request, Response $response): void
    {
        $description = escape_string($request->input('description', ''));
        $project = escape_string($request->input('project', ''));

        if ($project === '') {
            $response->error('project is required', 400);
            return;
        }

        $result = query("SELECT * FROM table_settings WHERE project = '$project'");
        $availableTables = [];
        $i = 0;
        while ($row = fetch_assoc($result)) {
            $availableTables[$i]['id'] = $row['table_id'];
            $availableTables[$i]['table'] = json_decode($row['table_json'], true);
            $availableTables[$i]['createdOn'] = $row['created_at'];
            $i++;
        }

        $dashboard = $this->generateDashboard($description, $availableTables, $project);

        if ($dashboard) {
            $response->json($dashboard);
        } else {
            $response->json(['error' => 'Dashboard konnte nicht generiert werden']);
        }
    }

    public function create(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $dashboardConfig = json_decode((string) $request->input('dashboard_config', ''), true);

        if (!$dashboardConfig || !isset($dashboardConfig['charts'])) {
            $response->json(['error' => 'Ungültige Dashboard-Konfiguration']);
            return;
        }

        $projectResult = query("SELECT * FROM projects WHERE link='$project'");
        if (mysqli_num_rows($projectResult) !== 1) {
            $response->json(['error' => 'Projekt nicht gefunden']);
            return;
        }

        $projectID = fetch_assoc($projectResult)['projectID'];
        $dashboardName = 'ai-dashboard-' . substr(md5(uniqid()), 0, 7);
        $dashboardTitle = $dashboardConfig['dashboard_title'] ?? 'AI Dashboard';

        $chartsJson = escape_string(json_encode($dashboardConfig['charts']));
        $nameEsc = escape_string($dashboardName);
        $titleEsc = escape_string($dashboardTitle);

        $insertDashboard = query("INSERT INTO control_center_dashboards VALUES (0, '$nameEsc', '$chartsJson', '$project', NOW(), NOW())");
        if (!$insertDashboard) {
            $response->json(['error' => 'Dashboard konnte nicht in Datenbank erstellt werden']);
            return;
        }

        $link = escape_string(createLink($dashboardTitle));
        $toolQuery = query("INSERT INTO project_tools VALUES (0,'bar-chart-outline','$titleEsc', '$link',0,'','$projectID')");
        if (!$toolQuery) {
            $response->json(['error' => 'Tool konnte nicht erstellt werden']);
            return;
        }

        $url = 'project/' . createLink($project) . '/dashboard/' . $dashboardName;
        $urlEsc = escape_string($url);
        query("INSERT INTO control_center_pages VALUES (0,'$urlEsc', 'true','bar-chart-outline','$titleEsc', '', 0)");

        $response->json([
            'success' => true,
            'dashboard_name' => $dashboardName,
            'dashboard_url' => $url
        ]);
    }

    private function generateDashboard($description, $availableTables, $project)
    {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleDashboard($availableTables);
        }

        $prompt = $this->buildPrompt($description, $availableTables);

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Du bist ein Dashboard-Experte. Analysiere verfügbare Tabellen und erstelle ein relevantes Dashboard mit passenden Charts basierend auf der Benutzerbeschreibung.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'dashboard_schema',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'dashboard_title' => ['type' => 'string'],
                            'charts' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'chart_type' => ['type' => 'string', 'enum' => ['pie_chart', 'donut_chart', 'bar_chart']],
                                        'form' => ['type' => 'string'],
                                        'label' => ['type' => 'string'],
                                        'data' => ['type' => 'string']
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

        $result = $this->makeOpenAIRequest($data);
        if ($result) {
            return $this->parseResponse($result);
        }
        return $this->generateSimpleDashboard($availableTables);
    }

    private function buildPrompt($description, $availableTables)
    {
        $formsInfo = '';
        foreach ($availableTables as $form) {
            $tableData = $form;
            if (isset($form['table'])) {
                $tableData = $form['table'];
            }
            if (!isset($tableData['title']) || !isset($tableData['inputs'])) {
                continue;
            }
            $formsInfo .= 'FORMULAR: ' . $tableData['title'] . "\n";
            $formsInfo .= 'FELDER: ';
            foreach ($tableData['inputs'] as $field) {
                if (isset($field['name']) && isset($field['type']) && isset($field['label'])) {
                    $formsInfo .= $field['name'] . ' (' . $field['type'] . ', ' . $field['label'] . '), ';
                }
            }
            $formsInfo = rtrim($formsInfo, ', ') . "\n\n";
        }

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
            error_log('Dashboard AI Error: API request failed');
            return null;
        }

        $decoded = json_decode($response, true);

        if (!$decoded || isset($decoded['error'])) {
            error_log('Dashboard AI Error: ' . json_encode($decoded['error'] ?? 'Invalid response'));
            return null;
        }

        if (isset($decoded['choices'][0]['message']['parsed'])) {
            return json_encode($decoded['choices'][0]['message']['parsed']);
        }

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

        foreach (array_slice($availableTables, 0, 3) as $form) {
            $tableData = $form;
            if (isset($form['table'])) {
                $tableData = $form['table'];
            }
            if (!isset($tableData['title']) || !isset($tableData['inputs'])) {
                continue;
            }

            $tableName = $this->toName($tableData['title']);
            $fields = $tableData['inputs'];
            if (empty($fields)) {
                continue;
            }

            $textField = null;
            $numberField = null;
            foreach ($fields as $field) {
                if (!isset($field['name']) || !isset($field['type'])) {
                    continue;
                }
                if (in_array($field['type'], ['text', 'select']) && !$textField) {
                    $textField = $field['name'];
                }
                if ($field['type'] === 'number' && !$numberField) {
                    $numberField = $field['name'];
                }
            }

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
