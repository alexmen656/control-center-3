<?php
require_once 'config.php';
require_once 'head.php';

function checkUserProjectPermission($userID, $projectID)
{
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
    return mysqli_num_rows($check) == 1;
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
        
        // Vercel Token holen
        $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!($tokenRow = fetch_assoc($tokenResult))) {
            echo json_encode(['error' => 'No Vercel token found for user.']);
            exit;
        }
        
        $vercel_token = $tokenRow['vercel_token'];
        $vercelApiUrl = 'https://api.vercel.com/v9/projects';
        
        $vercelData = [
            'name' => strtolower(preg_replace('/[^a-zA-Z0-9-_]/', '-', $codespace['name'])),
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
        
        echo json_encode([
            'github' => $github,
            'vercel' => $vercel
        ]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid request']);
?>
