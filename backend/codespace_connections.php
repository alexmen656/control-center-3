<?php
require_once 'config.php';
require_once 'head.php';

function getVercelFrameworkPreset($template)
{
    $frameworks = [
        'vanilla-js' => null, // Static HTML/CSS/JS
        'react' => 'vite',
        'vue' => 'vite', 
        'node' => null,
        'next' => 'nextjs',
        'nuxt' => 'nuxtjs',
        'angular' => 'angular',
        'svelte' => 'svelte'
    ];
    
    return $frameworks[$template] ?? null;
}

function getVercelBuildSettings($template)
{
    $settings = [
        'vanilla-js' => [
            'buildCommand' => null,
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ],
        'react' => [
            'buildCommand' => 'npm run build',
            'devCommand' => 'npm run dev',
            'installCommand' => 'npm install',
            'outputDirectory' => 'dist'
        ],
        'vue' => [
            'buildCommand' => 'npm run build',
            'devCommand' => 'npm run dev', 
            'installCommand' => 'npm install',
            'outputDirectory' => 'dist'
        ],
        'node' => [
            'buildCommand' => null,
            'devCommand' => 'npm run dev',
            'installCommand' => 'npm install',
            'outputDirectory' => null
        ],
        'next' => [
            'buildCommand' => null, // Next.js auto-detects
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ],
        'nuxt' => [
            'buildCommand' => null, // Nuxt auto-detects
            'devCommand' => null,
            'installCommand' => null,
            'outputDirectory' => null
        ]
    ];
    
    return $settings[$template] ?? $settings['vanilla-js'];
}

function updateVercelProjectFramework($vercel_project_id, $template, $user_id)
{
    // Vercel Token holen
    $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
    if (!($tokenRow = fetch_assoc($tokenResult))) {
        return false;
    }

    $vercel_token = $tokenRow['vercel_token'];
    
    // Framework-spezifische Einstellungen holen
    $framework = getVercelFrameworkPreset($template);
    $buildSettings = getVercelBuildSettings($template);

    // Nur aktualisieren wenn Framework oder Build-Settings vorhanden sind
    if (!$framework && !$buildSettings['buildCommand'] && !$buildSettings['devCommand'] && !$buildSettings['installCommand'] && !$buildSettings['outputDirectory']) {
        return true; // Nichts zu aktualisieren
    }

    $vercelApiUrl = "https://api.vercel.com/v9/projects/$vercel_project_id";
    
    $updateData = [];
    
    // Framework setzen falls verfügbar
    if ($framework) {
        $updateData['framework'] = $framework;
    }

    // Build-Settings hinzufügen
    if ($buildSettings['buildCommand']) {
        $updateData['buildCommand'] = $buildSettings['buildCommand'];
    }
    if ($buildSettings['devCommand']) {
        $updateData['devCommand'] = $buildSettings['devCommand'];
    }
    if ($buildSettings['installCommand']) {
        $updateData['installCommand'] = $buildSettings['installCommand'];
    }
    if ($buildSettings['outputDirectory']) {
        $updateData['outputDirectory'] = $buildSettings['outputDirectory'];
    }

    if (empty($updateData)) {
        return true; // Nichts zu aktualisieren
    }

    $opts = [
        'http' => [
            'method' => 'PATCH',
            'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
            'content' => json_encode($updateData)
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($vercelApiUrl, false, $context);
    
    return $response !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $codespace_id = (int)($_POST['codespace_id'] ?? 0);
    $user_id = escape_string($_POST['user_id'] ?? '');

    // Codespace laden und Berechtigung prüfen
    $codespace = null;
    if ($codespace_id > 0) {
        $codespaceResult = query("SELECT * FROM project_codespaces WHERE id='$codespace_id'");
        if ($codespace = fetch_assoc($codespaceResult)) {
            if (!checkUserProjectPermission($user_id, $codespace['project_id'])) {
                echo json_encode(['error' => 'No permission for this codespace']);
                exit;
            }
        } else {
            echo json_encode(['error' => 'Codespace not found']);
            exit;
        }
    }

    // GitHub Repository Aktionen
    if ($action === 'connect_github' && $codespace_id && $user_id && isset($_POST['repo'])) {
        $repo = json_decode($_POST['repo'], true);
        if (!$repo || !isset($repo['id'])) {
            echo json_encode(['error' => 'Invalid repo data']);
            exit;
        }

        $repo_id = escape_string($repo['id']);
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);

        // Prüfe, ob schon verbunden
        $exists = query("SELECT id FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'GitHub repo already connected to this codespace']);
            exit;
        }

        $insert = query("INSERT INTO codespace_github_repos (codespace_id, repo_id, repo_name, repo_full_name, user_id) VALUES ('$codespace_id', '$repo_id', '$repo_name', '$repo_full_name', '$user_id')");

        if ($insert) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to connect GitHub repo']);
        }
        exit;
    }

    if ($action === 'create_and_connect_github' && $codespace_id && $user_id) {
        // Token holen
        $tokenResult = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!($tokenRow = fetch_assoc($tokenResult))) {
            echo json_encode(['error' => 'No GitHub token found for user.']);
            exit;
        }

        $token = $tokenRow['github_token'];
        $repoName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $codespace['name']);

        $apiUrl = 'https://api.github.com/user/repos';
        $data = [
            'name' => $repoName,
            'description' => 'Codespace repository for ' . $codespace['name'],
            'private' => true
        ];

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($opts);
        $result = @file_get_contents($apiUrl, false, $context);
        $http_response_header = $http_response_header ?? [];
        $status = 0;

        foreach ($http_response_header as $header) {
            if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $header, $m)) {
                $status = (int)$m[1];
                break;
            }
        }

        if ($status !== 201 || !$result) {
            $error = @json_decode($result, true);
            echo json_encode(['error' => $error['message'] ?? 'GitHub API error', 'github_response' => $result]);
            exit;
        }

        $repo = json_decode($result, true);

        // In DB verbinden
        $repo_id = escape_string($repo['id']);
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);

        // Alte Verbindung löschen falls vorhanden
        query("DELETE FROM codespace_github_repos WHERE codespace_id='$codespace_id'");

        $insert = query("INSERT INTO codespace_github_repos (codespace_id, repo_id, repo_name, repo_full_name, user_id) VALUES ('$codespace_id', '$repo_id', '$repo_name', '$repo_full_name', '$user_id')");

        if ($insert) {
            // Initialen Commit erstellen und pushen
            createInitialCommitAndPush($codespace_id, $repo['full_name'], $token, $user_id);

            echo json_encode(['success' => true, 'repo' => $repo]);
        } else {
            echo json_encode(['error' => 'Failed to save repo connection after creation', 'repo' => $repo]);
        }
        exit;
    }

    if ($action === 'disconnect_github' && $codespace_id) {
        $delete = query("DELETE FROM codespace_github_repos WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect GitHub repo']);
        }
        exit;
    }

    if ($action === 'get_github' && $codespace_id) {
        $result = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['repo' => $row]);
        } else {
            echo json_encode(['repo' => null]);
        }
        exit;
    }

    // Vercel Project Aktionen
    if ($action === 'connect_vercel' && $codespace_id && $user_id) {
        $vercel_project_id = escape_string($_POST['vercel_project_id'] ?? '');
        $vercel_project_name = escape_string($_POST['vercel_project_name'] ?? '');

        if (!$vercel_project_id) {
            echo json_encode(['error' => 'No Vercel project selected']);
            exit;
        }

        // Template Info aus Monaco-Initialisierung lesen
        $template = 'vanilla-js'; // Default
        $projectResult = query("SELECT pc.slug, p.link as project_link FROM project_codespaces pc 
                               JOIN projects p ON pc.project_id = p.projectID 
                               WHERE pc.id='$codespace_id'");
        if ($projectRow = fetch_assoc($projectResult)) {
            $codespaceDir = __DIR__ . "/../data/projects/" . $user_id . "/" . $projectRow['project_link'] . "/" . $projectRow['slug'];
            $monacoInitFile = $codespaceDir . '/.monaco_initialized';
            
            if (file_exists($monacoInitFile)) {
                $monacoData = json_decode(file_get_contents($monacoInitFile), true);
                if (isset($monacoData['template'])) {
                    $template = $monacoData['template'];
                }
            }
        }

        // Vercel-Projekt mit Framework-Settings aktualisieren
        updateVercelProjectFramework($vercel_project_id, $template, $user_id);

        // Alte Verbindung löschen falls vorhanden
        query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$codespace_id'");

        $insert = query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$codespace_id', '$vercel_project_id', '$vercel_project_name', '$user_id')");

        if ($insert) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to connect Vercel project']);
        }
        exit;
    }

    if ($action === 'create_and_connect_vercel' && $codespace_id && $user_id) {
        // GitHub Repo Info holen
        $repoResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");
        if (!($repoRow = fetch_assoc($repoResult))) {
            echo json_encode(['error' => 'No GitHub repo connected. Connect a GitHub repo first.']);
            exit;
        }

        $repo_full_name = $repoRow['repo_full_name'];
        $repo_id = $repoRow['repo_id'];

        // Template Info aus Monaco-Initialisierung lesen
        $template = 'vanilla-js'; // Default
        $projectResult = query("SELECT pc.slug, p.link as project_link FROM project_codespaces pc 
                               JOIN projects p ON pc.project_id = p.projectID 
                               WHERE pc.id='$codespace_id'");
        if ($projectRow = fetch_assoc($projectResult)) {
            $codespaceDir = __DIR__ . "/../data/projects/" . $user_id . "/" . $projectRow['project_link'] . "/" . $projectRow['slug'];
            $monacoInitFile = $codespaceDir . '/.monaco_initialized';
            
            if (file_exists($monacoInitFile)) {
                $monacoData = json_decode(file_get_contents($monacoInitFile), true);
                if (isset($monacoData['template'])) {
                    $template = $monacoData['template'];
                }
            }
        }

        // Vercel Token holen
        $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!($tokenRow = fetch_assoc($tokenResult))) {
            echo json_encode(['error' => 'No Vercel token found for user.']);
            exit;
        }

        $vercel_token = $tokenRow['vercel_token'];
        $vercelApiUrl = 'https://api.vercel.com/v9/projects';

        // Framework-spezifische Einstellungen holen
        $framework = getVercelFrameworkPreset($template);
        $buildSettings = getVercelBuildSettings($template);

        $vercelData = [
            'name' => strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $codespace['name'])),
            'gitRepository' => [
                'type' => 'github',
                'repo' => $repo_full_name,
                'repoId' => (string)$repo_id
            ]
        ];

        // Framework und Build-Settings hinzufügen falls verfügbar
        if ($framework) {
            $vercelData['framework'] = $framework;
        }

        // Build-Settings hinzufügen
        if ($buildSettings['buildCommand']) {
            $vercelData['buildCommand'] = $buildSettings['buildCommand'];
        }
        if ($buildSettings['devCommand']) {
            $vercelData['devCommand'] = $buildSettings['devCommand'];
        }
        if ($buildSettings['installCommand']) {
            $vercelData['installCommand'] = $buildSettings['installCommand'];
        }
        if ($buildSettings['outputDirectory']) {
            $vercelData['outputDirectory'] = $buildSettings['outputDirectory'];
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
                'content' => json_encode($vercelData)
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($vercelApiUrl, false, $context);
        $data = $response ? json_decode($response, true) : null;

        if (!$response || (isset($data['error']) && isset($data['error']['message']))) {
            $errMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Vercel API error';
            echo json_encode(['error' => $errMsg]);
            exit;
        }

        if (!isset($data['id'])) {
            echo json_encode(['error' => 'Vercel API error: No project ID received.']);
            exit;
        }

        // Alte Verbindung löschen falls vorhanden
        query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$codespace_id'");

        $insert = query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$codespace_id', '" . escape_string($data['id']) . "', '" . escape_string($data['name']) . "', '$user_id')");

        if ($insert) {
            echo json_encode(['success' => true, 'vercel_project_id' => $data['id'], 'vercel_project_name' => $data['name']]);
        } else {
            echo json_encode(['error' => 'Failed to save Vercel project connection after creation']);
        }
        exit;
    }

    if ($action === 'disconnect_vercel' && $codespace_id) {
        $delete = query("DELETE FROM codespace_vercel_projects WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect Vercel project']);
        }
        exit;
    }

    if ($action === 'get_vercel' && $codespace_id) {
        $result = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['vercel_project_id' => $row['vercel_project_id'], 'vercel_project_name' => $row['vercel_project_name']]);
        } else {
            echo json_encode(['vercel_project_id' => null, 'vercel_project_name' => null]);
        }
        exit;
    }

    // Alle Verbindungen für einen Codespace abrufen
    if ($action === 'get_all_connections' && $codespace_id) {
        $github = null;
        $vercel = null;
        $domain = null;

        // GitHub Verbindung
        $githubResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($githubRow = fetch_assoc($githubResult)) {
            $github = $githubRow;
        }

        // Vercel Verbindung
        $vercelResult = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($vercelRow = fetch_assoc($vercelResult)) {
            $vercel = $vercelRow;
        }

        // Domain Verbindung
        $domainResult = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespace_id' LIMIT 1");
        if ($domainRow = fetch_assoc($domainResult)) {
            $domain = $domainRow;
        }

        echo json_encode([
            'github' => $github,
            'vercel' => $vercel,
            'domain' => $domain
        ]);
        exit;
    }

    // Domain Aktionen
    if ($action === 'connect_domain' && $codespace_id && $user_id) {
        $subdomain = strtolower(trim($_POST['subdomain'] ?? ''));
        $is_main = isset($_POST['is_main']) && $_POST['is_main'] === 'true';

        // Nur Subdomain validieren wenn nicht Haupt-Domain
        if (!$is_main && (!$subdomain || !preg_match('/^[a-z0-9-]+$/', $subdomain))) {
            echo json_encode(['error' => 'Ungültiges Subdomain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.']);
            exit;
        }

        // Projekt-Domain ermitteln
        $projectResult = query("SELECT project_id FROM project_codespaces WHERE id='$codespace_id'");
        if (!$projectRow = fetch_assoc($projectResult)) {
            echo json_encode(['error' => 'Codespace nicht gefunden']);
            exit;
        }

        $project_id = $projectRow['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            echo json_encode(['error' => 'Projekt nicht gefunden']);
            exit;
        }

        $project_link = $projectInfoRow['link'];

        // Prüfen ob Projekt eine Haupt-Domain hat
        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if (!$projectDomainRow = fetch_assoc($projectDomainResult)) {
            echo json_encode(['error' => 'Projekt hat keine Domain konfiguriert. Bitte zuerst in den Projekt-Einstellungen eine Domain einrichten.']);
            exit;
        }

        $base_domain = $projectDomainRow['domain']; // z.B. "myproject.sites.control-center.eu"

        // Domain basierend auf is_main erstellen
        if ($is_main) {
            $full_domain = $base_domain; // Haupt-Domain verwenden

            // Prüfen ob bereits eine Haupt-Domain für dieses Projekt existiert
            $existingMainResult = query("
                SELECT cd.id FROM codespace_domains cd 
                JOIN project_codespaces pc ON cd.codespace_id = pc.id 
                WHERE pc.project_id = '$project_id' AND cd.is_main = 1 AND cd.codespace_id != '$codespace_id'
            ");
            if (mysqli_num_rows($existingMainResult) > 0) {
                echo json_encode(['error' => 'Ein anderer Codespace verwendet bereits die Haupt-Domain. Bitte zuerst die Haupt-Domain des anderen Codespaces entfernen.']);
                exit;
            }
        } else {
            $full_domain = $subdomain . '.' . $base_domain; // Subdomain verwenden
        }

        // Prüfen ob Domain bereits vergeben
        $exists = query("SELECT id FROM codespace_domains WHERE domain='$full_domain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'Domain bereits vergeben.']);
            exit;
        }

        // Vercel-Projekt muss vorhanden sein
        $vercelResult = query("SELECT * FROM codespace_vercel_projects WHERE codespace_id='$codespace_id' LIMIT 1");
        if (!$vercelRow = fetch_assoc($vercelResult)) {
            echo json_encode(['error' => 'Kein Vercel-Projekt verbunden. Bitte zuerst ein Vercel-Projekt verbinden.']);
            exit;
        }

        $vercel_project_id = $vercelRow['vercel_project_id'];

        // Alte Domain für diesen Codespace löschen falls vorhanden
        query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");

        // Domain in DB speichern
        $insert = query("INSERT INTO codespace_domains (codespace_id, domain, is_main, user_id) VALUES ('$codespace_id', '$full_domain', " . ($is_main ? 1 : 0) . ", '$user_id')");

        if (!$insert) {
            echo json_encode(['error' => 'Fehler beim Speichern der Domain']);
            exit;
        }

        // Vercel-Token holen
        $vercelTokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!$vercelTokenRow = fetch_assoc($vercelTokenResult)) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");
            echo json_encode(['error' => 'Kein Vercel-Token gefunden']);
            exit;
        }

        $vercel_token = $vercelTokenRow['vercel_token'];

        // Domain zu Vercel-Projekt hinzufügen
        $vercelApiUrl = "https://api.vercel.com/v10/projects/{$vercel_project_id}/domains";
        $vercelData = ['name' => $full_domain];
        $vercelOpts = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
                'content' => json_encode($vercelData)
            ]
        ];

        $vercelContext = stream_context_create($vercelOpts);
        $vercelResponse = @file_get_contents($vercelApiUrl, false, $vercelContext);
        $vercelResult = $vercelResponse ? json_decode($vercelResponse, true) : null;

        if (!$vercelResponse || (isset($vercelResult['error']) && $vercelResult['error'])) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");
            $errMsg = isset($vercelResult['error']['message']) ? $vercelResult['error']['message'] : 'Vercel API Fehler';
            echo json_encode(['error' => 'Vercel: ' . $errMsg]);
            exit;
        }

        // CNAME-Target von Vercel holen
        $cnameTarget = null;
        $vercelDomainConfigUrl = "https://api.vercel.com/v6/domains/$full_domain/config";
        $vercelDomainConfigOpts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\n"
            ]
        ];

        $vercelDomainConfigContext = stream_context_create($vercelDomainConfigOpts);
        $vercelDomainConfigResponse = @file_get_contents($vercelDomainConfigUrl, false, $vercelDomainConfigContext);

        if ($vercelDomainConfigResponse) {
            $vercelDomainConfig = json_decode($vercelDomainConfigResponse, true);
            if (isset($vercelDomainConfig['recommendedCNAME'][0]['value'])) {
                $cnameTarget = $vercelDomainConfig['recommendedCNAME'][0]['value'];
            } elseif (isset($vercelDomainConfig['cnameTarget'])) {
                $cnameTarget = $vercelDomainConfig['cnameTarget'];
            } elseif (isset($vercelDomainConfig['domain']['cnameTarget'])) {
                $cnameTarget = $vercelDomainConfig['domain']['cnameTarget'];
            }
        }

        if (!$cnameTarget) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");
            echo json_encode(['error' => 'Kein CNAME-Target von Vercel erhalten']);
            exit;
        }

        // DNS-Eintrag über Cloudflare erstellen
        $cloudflare_zone_id = isset($cloudflare_zone_id) ? $cloudflare_zone_id : '';
        $cloudflare_api_token = isset($cloudflare_api_token) ? $cloudflare_api_token : '';
        $cloudflare_api_url = "https://api.cloudflare.com/client/v4/zones/$cloudflare_zone_id/dns_records";
        $cloudflare_post = json_encode([
            'type' => 'CNAME',
            'name' => $full_domain,
            'content' => $cnameTarget,
            'ttl' => 300,
            'proxied' => false
        ]);

        $cloudflareOpts = [
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer $cloudflare_api_token\r\nContent-Type: application/json\r\n",
                'content' => $cloudflare_post
            ]
        ];

        $cloudflareContext = stream_context_create($cloudflareOpts);
        $cloudflareResponse = @file_get_contents($cloudflare_api_url, false, $cloudflareContext);
        $cloudflareResult = $cloudflareResponse ? json_decode($cloudflareResponse, true) : null;

        if (!$cloudflareResponse || (isset($cloudflareResult['success']) && !$cloudflareResult['success'])) {
            query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");
            $errMsg = isset($cloudflareResult['errors'][0]['message']) ? $cloudflareResult['errors'][0]['message'] : 'Cloudflare API Fehler';
            echo json_encode(['error' => 'Cloudflare: ' . $errMsg]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'domain' => $full_domain,
            'is_main' => $is_main,
            'vercel' => $vercelResult,
            'cloudflare' => $cloudflareResult
        ]);
        exit;
    }

    if ($action === 'disconnect_domain' && $codespace_id) {
        $delete = query("DELETE FROM codespace_domains WHERE codespace_id='$codespace_id'");

        if ($delete) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to disconnect domain']);
        }
        exit;
    }

    if ($action === 'get_domain' && $codespace_id) {
        $result = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespace_id' LIMIT 1");

        if ($row = fetch_assoc($result)) {
            echo json_encode(['domain' => $row]);
        } else {
            echo json_encode(['domain' => null]);
        }
        exit;
    }

    if ($action === 'get_project_domain_info' && $codespace_id) {
        // Projekt-Info für Domain-Konfiguration abrufen
        $projectResult = query("SELECT project_id FROM project_codespaces WHERE id='$codespace_id'");
        if (!$projectRow = fetch_assoc($projectResult)) {
            echo json_encode(['error' => 'Codespace nicht gefunden']);
            exit;
        }

        $project_id = $projectRow['project_id'];
        $projectInfoResult = query("SELECT link FROM projects WHERE projectID='$project_id'");
        if (!$projectInfoRow = fetch_assoc($projectInfoResult)) {
            echo json_encode(['error' => 'Projekt nicht gefunden']);
            exit;
        }

        $project_link = $projectInfoRow['link'];

        // Projekt-Domain abrufen
        $projectDomainResult = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link' LIMIT 1");
        if ($projectDomainRow = fetch_assoc($projectDomainResult)) {
            $base_domain = $projectDomainRow['domain'];

            // Prüfen ob bereits eine Haupt-Domain für dieses Projekt existiert
            $existingMainResult = query("
                SELECT cd.id, pc.name as codespace_name FROM codespace_domains cd 
                JOIN project_codespaces pc ON cd.codespace_id = pc.id 
                WHERE pc.project_id = '$project_id' AND cd.is_main = 1
            ");

            $main_domain_taken = false;
            $main_domain_codespace = null;
            if ($mainRow = fetch_assoc($existingMainResult)) {
                $main_domain_taken = true;
                $main_domain_codespace = $mainRow['codespace_name'];
            }

            echo json_encode([
                'base_domain' => $base_domain,
                'main_domain_taken' => $main_domain_taken,
                'main_domain_codespace' => $main_domain_codespace
            ]);
        } else {
            echo json_encode(['error' => 'Projekt hat keine Domain konfiguriert']);
        }
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);

function createInitialCommitAndPush($codespaceId, $repoFullName, $githubToken, $userID)
{
    try {
        $codespaceResult = query("SELECT pc.*, p.link as project_link FROM project_codespaces pc 
                                 JOIN projects p ON pc.project_id = p.projectID 
                                 WHERE pc.id='$codespaceId'");
        $codespace = fetch_assoc($codespaceResult);

        if (!$codespace) {
            error_log("Codespace not found for initial commit: $codespaceId");
            return false;
        }

        $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $codespace['project_link'] . "/" . $codespace['slug'];

        if (!is_dir($codespaceDir)) {
            error_log("Codespace directory not found: $codespaceDir");
            return false;
        }

        // README.md erstellen falls nicht vorhanden
        $readmeFile = $codespaceDir . '/README.md';
        if (!file_exists($readmeFile)) {
            $readmeContent = "# " . $codespace['name'] . "\n\n";
            $readmeContent .= $codespace['description'] ?: "Codespace for " . $codespace['name'];
            $readmeContent .= "\n\nCreated with Control Center\n";
            file_put_contents($readmeFile, $readmeContent);
        }


        $content = base64_encode(file_get_contents($readmeFile));


        // Commit über GitHub API erstellen
        $apiUrl = "https://api.github.com/repos/$repoFullName/contents/";

        $data = [
            'message' => "Initial commit",
            'content' => $content,
            'branch' => 'main'
        ];

        $opts = [
            'http' => [
                'method' => 'PUT',
                'header' => "Authorization: token $githubToken\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\nContent-Type: application/json\r\n",
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($opts);
        $fileApiUrl = $apiUrl . "Readme.md";
        $result = @file_get_contents($fileApiUrl, false, $context);

        if (!$result) {
            error_log("Failed to create Readme.md in GitHub repo $repoFullName");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error creating initial commit: " . $e->getMessage());
        return false;
    }
}
