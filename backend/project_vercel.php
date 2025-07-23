<?php
// project_vercel.php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $project = escape_string($_POST['project'] ?? '');
    $user_id = escape_string($_POST['user_id'] ?? '');

    if ($action === 'create_vercel_project' && $project && $user_id) {
        $repo_full_name = $_POST['repo_full_name'] ?? '';
        $repo_id = $_POST['repo_id'] ?? '';
        $tokenRes = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!($tokenRow = fetch_assoc($tokenRes))) {
            echo json_encode(['error' => 'Kein Vercel-Token für User gefunden.']);
            exit;
        }
        $vercel_token = $tokenRow['vercel_token'];
        $vercelApiUrl = 'https://api.vercel.com/v9/projects';
        $vercelData = [
            'name' => $project,
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
            $errMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Vercel API Fehler';
            echo json_encode(['error' => $errMsg]);
            exit;
        }
        if (!isset($data['id'])) {
            echo json_encode(['error' => 'Vercel API Fehler: Keine Projekt-ID erhalten.']);
            exit;
        }
        query("DELETE FROM control_center_project_vercel_projects WHERE project='$project'");
        $insert = query("INSERT INTO control_center_project_vercel_projects (project, vercel_project_id, vercel_project_name, user_id) VALUES ('$project', '" . escape_string($data['id']) . "', '" . escape_string($data['name']) . "', '$user_id')");
        echo json_encode(['success' => true, 'vercel_project_id' => $data['id'], 'vercel_project_name' => $data['name']]);
        exit;
    }

    if ($action === 'connect' && $project && $user_id) {
        $vercel_project_id = escape_string($_POST['vercel_project_id'] ?? '');
        $vercel_project_name = escape_string($_POST['vercel_project_name'] ?? '');
        if (!$vercel_project_id) {
            echo json_encode(['error' => 'Kein Vercel-Projekt gewählt.']);
            exit;
        }
        // Nur ein Vercel-Projekt pro ControlCenter-Projekt
        query("DELETE FROM control_center_project_vercel_projects WHERE project='$project'");
        $insert = query("INSERT INTO control_center_project_vercel_projects (project, vercel_project_id, vercel_project_name, user_id) VALUES ('$project', '$vercel_project_id', '$vercel_project_name', '$user_id')");
        if ($insert) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Insert failed']);
        }
        exit;
    }
    if ($action === 'get' && $project) {
        $res = query("SELECT * FROM control_center_project_vercel_projects WHERE project='$project' LIMIT 1");
        if ($row = fetch_assoc($res)) {
            echo json_encode(['vercel_project_id' => $row['vercel_project_id'], 'vercel_project_name' => $row['vercel_project_name']]);
        } else {
            echo json_encode(['vercel_project_id' => null, 'vercel_project_name' => null]);
        }
        exit;
    }
}
echo json_encode(['error' => 'Invalid request']);
