<?php
include "head.php";
include "project_helper.php";

if (isset($_POST['createCodespace']) && isset($_POST['project']) && isset($_POST['name'])) {
    $projectID = getProjectID(escape_string($_POST['project']));
    $name = escape_string($_POST['name']);
    $description = escape_string($_POST['description'] ?? '');
    $icon = escape_string($_POST['icon'] ?? 'code-outline');
    $language = escape_string($_POST['language'] ?? 'javascript');
    $template = escape_string($_POST['template'] ?? 'default');
    
    // Slug generieren
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    
    // Prüfen ob Projekt existiert und Benutzer Berechtigung hat
    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse("No permission for this project", false);
        exit;
    }
    
    // Prüfen ob Slug bereits existiert
    $existingCheck = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='$slug'");
    if (mysqli_num_rows($existingCheck) > 0) {
        echo jsonResponse("A codespace with this name already exists", false);
        exit;
    }
    
    // Order Index ermitteln
    $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$projectID'");
    $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
    $newOrder = $maxOrder + 1;
    
    // Codespace erstellen
    $result = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index) 
                    VALUES ('$name', '$slug', '$description', '$icon', '$language', '$template', '$projectID', '$userID', '$newOrder')");
    
    if ($result) {
        $codespaceId = mysqli_insert_id($GLOBALS['con']);
        
        // Monaco-Verzeichnis erstellen
        $project = getProjectByID($projectID);
        if ($project) {
            createMonacoCodespaceDirectory($project['link'], $slug, $name, $userID, $projectID);
        }
        
        // Auto-create GitHub repo if requested
        if (isset($_POST['createGithubRepo']) && $_POST['createGithubRepo'] === 'true') {
            createCodespaceGithubRepo($codespaceId, $name, $userID);
        }
        
        // Auto-create Vercel project if requested (requires GitHub repo)
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
    
} else {
    echo jsonResponse("Invalid request", false);
}

/**
 * Löscht ein Verzeichnis rekursiv
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}

/**
 * Erstellt automatisch ein GitHub Repository für einen Codespace
 */
function createCodespaceGithubRepo($codespaceId, $name, $userID) {
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
        
        return $repo;
    }
    
    return false;
}

/**
 * Erstellt automatisch ein Vercel Project für einen Codespace
 */
function createCodespaceVercelProject($codespaceId, $name, $userID) {
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
    
    $vercel_token = $tokenRow['vercel_token'];
    $vercelApiUrl = 'https://api.vercel.com/v9/projects';
    
    $vercelData = [
        'name' => strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $name)),
        'gitRepository' => [
            'type' => 'github',
            'repo' => $repo_full_name,
            'repoId' => (string)$repo_id
        ]
    ];
    
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
    
    print_r($vercelApiUrl);
    print_r($opts);
    if ($response && isset($data['id'])) {
        query("INSERT INTO codespace_vercel_projects (codespace_id, vercel_project_id, vercel_project_name, user_id) VALUES ('$codespaceId', '" . escape_string($data['id']) . "', '" . escape_string($data['name']) . "', '$userID')");
        
        return $data;
    }
    
    return false;
}
?>
