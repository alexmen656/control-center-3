<?php
include "head.php";
include "project_helper.php";

function slugExists($projectID, $slug)
{
    $result = query("SELECT id FROM project_codespaces WHERE project_id='" . escape_string($projectID) . "' AND slug='" . escape_string($slug) . "'");
    return mysqli_num_rows($result) > 0;
}

if (isset($_POST['createCodespace']) && isset($_POST['project']) && isset($_POST['name'])) {
    $projectID = getProjectID(escape_string($_POST['project']));
    $name = escape_string($_POST['name']);
    $description = escape_string($_POST['description'] ?? '');
    $icon = escape_string($_POST['icon'] ?? 'code-outline');
    $language = escape_string($_POST['language'] ?? 'javascript');
    $template = escape_string($_POST['template'] ?? 'default');
    $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');

    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    if (slugExists($projectID, $slug)) {
        echo jsonResponse("A codespace with this name already exists", false);
        exit;
    }

    // Order Index ermitteln
    $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$projectID'");
    $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
    $newOrder = $maxOrder + 1;

    $result = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index) 
                    VALUES ('$name', '$slug', '$description', '$icon', '$language', '$template', '$projectID', '$userID', '$newOrder')");

    if ($result) {
        $codespaceId = mysqli_insert_id($GLOBALS['con']);
        $project = getProjectByID($projectID);

        if ($project) {
            createMonacoCodespaceDirectory($project['link'], $slug, $name, $userID, $template, $projectID);
        }

        if (isset($_POST['createGithubRepo']) && $_POST['createGithubRepo'] === 'true') {
            createCodespaceGithubRepo($codespaceId, $name, $userID);
        }

        if (isset($_POST['createVercelProject']) && $_POST['createVercelProject'] === 'true') {
            createCodespaceVercelProject($codespaceId, $name, $userID);
        }

        echo jsonResponse([
            'message' => 'Codespace created successfully',
            'codespace' => [
                'id' => $codespaceId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'language' => $language,
                'status' => 'active'
            ]
        ]);
    } else {
        echo jsonResponse("Failed to create codespace", false);
    }
} elseif (isset($_POST['updateCodespace']) && isset($_POST['codespaceID'])) {
    $codespaceID = (int)$_POST['codespaceID'];
    $name = escape_string($_POST['name'] ?? '');
    $description = escape_string($_POST['description'] ?? '');
    $icon = escape_string($_POST['icon'] ?? '');
    $language = escape_string($_POST['language'] ?? '');
    $status = escape_string($_POST['status'] ?? '');

    // Codespace laden und Berechtigung prüfen
    $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
    if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
        echo jsonResponse("Codespace not found or no permission", false);
        exit;
    }

    $updates = [];
    if (!empty($name)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        $updates[] = "name='$name'";
        $updates[] = "slug='$slug'";
    }
    if (!empty($description)) $updates[] = "description='$description'";
    if (!empty($icon)) $updates[] = "icon='$icon'";
    if (!empty($language)) $updates[] = "language='$language'";
    if (!empty($status)) $updates[] = "status='$status'";

    if (!empty($updates)) {
        $updateQuery = "UPDATE project_codespaces SET " . implode(', ', $updates) . " WHERE id='$codespaceID'";
        $result = query($updateQuery);

        if ($result) {
            echo jsonResponse("Codespace updated successfully");
        } else {
            echo jsonResponse("Failed to update codespace", false);
        }
    } else {
        echo jsonResponse("No fields to update", false);
    }
} elseif (isset($_POST['deleteCodespace']) && isset($_POST['codespaceID'])) {
    $codespaceID = (int)$_POST['codespaceID'];

    // Codespace laden und Berechtigung prüfen
    $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
    if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
        echo jsonResponse("Codespace not found or no permission", false);
        exit;
    }

    // Verzeichnis löschen
    $project = getProjectByID($codespace['project_id']);
    if ($project) {
        $codespaceDir = __DIR__ . "/../data/projects/" . $userID . "/" . $project['link'] . "/" . $codespace['slug'];
        if (is_dir($codespaceDir)) {
            deleteDirectory($codespaceDir);
        }
    }

    // Aus Datenbank löschen
    $result = query("DELETE FROM project_codespaces WHERE id='$codespaceID'");

    if ($result) {
        echo jsonResponse("Codespace deleted successfully");
    } else {
        echo jsonResponse("Failed to delete codespace", false);
    }
} elseif (isset($_POST['reorderCodespaces']) && isset($_POST['projectID']) && isset($_POST['codespaces'])) {
    $projectID = escape_string($_POST['projectID']);
    $codespaces = $_POST['codespaces'];

    // Berechtigung prüfen
    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    // Reihenfolge aktualisieren
    foreach ($codespaces as $index => $codespaceData) {
        $codespaceID = (int)$codespaceData['id'];
        $orderIndex = (int)$index;
        query("UPDATE project_codespaces SET order_index='$orderIndex' WHERE id='$codespaceID' AND project_id='$projectID'");
    }

    echo jsonResponse("Codespaces reordered successfully");
} elseif (isset($_POST['getCodespaces']) && isset($_POST['project'])) {
    $projectID = getProjectID(escape_string($_POST['project']));

    // Berechtigung prüfen
    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }

    // Codespaces laden
    $codespaces = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");
    $result = [];

    foreach ($codespaces as $codespace) {
        $result[] = [
            'id' => $codespace['id'],
            'name' => $codespace['name'],
            'slug' => $codespace['slug'],
            'description' => $codespace['description'],
            'icon' => $codespace['icon'],
            'language' => $codespace['language'],
            'template' => $codespace['template'],
            'status' => $codespace['status'],
            'created_at' => $codespace['created_at'],
            'updated_at' => $codespace['updated_at'],
            'order_index' => $codespace['order_index']
        ];
    }

    echo jsonResponse(['codespaces' => $result]);
} elseif (isset($_POST['getAvailableTemplates'])) {
    // Verfügbare Templates aus dem Dateisystem laden
    $templatesDir = __DIR__ . "/templates/codespace/";
    $templates = [];
    
    if (is_dir($templatesDir)) {
        $templateDirs = array_filter(scandir($templatesDir), function($item) use ($templatesDir) {
            return $item != '.' && $item != '..' && is_dir($templatesDir . $item);
        });
        
        foreach ($templateDirs as $templateDir) {
            $templatePath = $templatesDir . $templateDir;
            $templateInfo = [
                'id' => $templateDir,
                'name' => ucfirst(str_replace(['-', '_'], ' ', $templateDir)),
                'description' => getTemplateDescription($templateDir),
                'icon' => getTemplateIcon($templateDir)
            ];
            $templates[] = $templateInfo;
        }
    }
    
    // Fallback Template hinzufügen falls keine Templates gefunden wurden
    if (empty($templates)) {
        $templates[] = [
            'id' => 'vanilla-js',
            'name' => 'Vanilla JavaScript',
            'description' => 'Basic HTML, CSS and JavaScript setup',
            'icon' => 'logo-javascript'
        ];
    }
    
    echo jsonResponse(['templates' => $templates]);
} else {
    echo jsonResponse("Invalid request", false);
}

function deleteDirectory($dir)
{
    if (!is_dir($dir)) return false;

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }

    return rmdir($dir);
}

function getTemplateDescription($templateDir)
{
    $descriptions = [
        'vanilla-js' => 'Basic HTML, CSS and JavaScript setup',
        'react' => 'React application with Vite build tool',
        'vue' => 'Vue.js application with Vite build tool', 
        'node' => 'Node.js server with Express framework',
        'angular' => 'Angular application with TypeScript',
        'svelte' => 'Svelte application with modern tooling',
        'next' => 'Next.js React framework',
        'nuxt' => 'Nuxt.js Vue framework'
    ];
    
    return $descriptions[$templateDir] ?? 'Custom development environment';
}

function getTemplateIcon($templateDir)
{
    $icons = [
        'vanilla-js' => 'logo-javascript',
        'react' => 'logo-react',
        'vue' => 'logo-vue', 
        'node' => 'logo-nodejs',
        'angular' => 'logo-angular',
        'svelte' => 'logo-web-component',
        'next' => 'logo-react',
        'nuxt' => 'logo-vue'
    ];
    
    return $icons[$templateDir] ?? 'code-outline';
}

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

function createCodespaceGithubRepo($codespaceId, $name, $userID)
{
    // Token holen
    $tokenResult = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if (!($tokenRow = fetch_assoc($tokenResult))) {
        return false;
    }

    $token = $tokenRow['github_token'];
    $repoName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $name);

    $apiUrl = 'https://api.github.com/user/repos';
    $data = [
        'name' => $repoName,
        'description' => 'Codespace repository for ' . $name,
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

    if ($status === 201 && $result) {
        $repo = json_decode($result, true);

        // In DB verbinden
        $repo_id = escape_string($repo['id']);
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);

        query("INSERT INTO codespace_github_repos (codespace_id, repo_id, repo_name, repo_full_name, user_id) VALUES ('$codespaceId', '$repo_id', '$repo_name', '$repo_full_name', '$userID')");

        // Initialen Commit erstellen und pushen
        createInitialCommitAndPush($codespaceId, $repo_full_name, $token, $userID);

        return $repo;
    }

    return false;
}

function createCodespaceVercelProject($codespaceId, $name, $userID)
{
    // GitHub Repo Info holen
    $repoResult = query("SELECT * FROM codespace_github_repos WHERE codespace_id='$codespaceId' LIMIT 1");
    if (!($repoRow = fetch_assoc($repoResult))) {
        return false; // Kein GitHub Repo verbunden
    }

    $repo_full_name = $repoRow['repo_full_name'];
    $repo_id = $repoRow['repo_id'];

    // Vercel Token holen
    $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if (!($tokenRow = fetch_assoc($tokenResult))) {
        return false;
    }

    // Template Info aus Codespace holen
    $codespaceResult = query("SELECT template FROM project_codespaces WHERE id='$codespaceId' LIMIT 1");
    $template = 'vanilla-js'; // Default
    if ($codespaceRow = fetch_assoc($codespaceResult)) {
        $template = $codespaceRow['template'] ?? 'vanilla-js';
    }

    $vercel_token = $tokenRow['vercel_token'];
    $vercelApiUrl = 'https://api.vercel.com/v9/projects';

    // Framework-spezifische Einstellungen holen
    $framework = getVercelFrameworkPreset($template);
    $buildSettings = getVercelBuildSettings($template);

    $vercelData = [
        'name' => strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $name)),
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

    if ($response && isset($data['id'])) {
        query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$codespaceId', '" . escape_string($data['id']) . "', '" . escape_string($data['name']) . "', '$userID')");

        return $data;
    }

    return false;
}

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
