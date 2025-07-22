<?php
// project_repo.php
// API für das Verbinden und Anzeigen von Projekten mit GitHub-Repos

require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $project = escape_string($_POST['project'] ?? '');
    $user_id = escape_string($_POST['user_id'] ?? '');

    if ($action === 'connect' && $project && $user_id && isset($_POST['repo'])) {
        $repo = json_decode($_POST['repo'], true);
        if (!$repo || !isset($repo['id'])) {
            echo json_encode(['error' => 'Invalid repo data']);
            exit;
        }
        $repo_id = (int)$repo['id'];
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);
        // Prüfe, ob schon verbunden
        $exists = query("SELECT id FROM control_center_project_repos WHERE project='$project' AND repo_id='$repo_id' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'Repo already connected']);
            exit;
        }
        $insert = query("INSERT INTO control_center_project_repos (project, repo_id, repo_name, repo_full_name, user_id) VALUES ('$project', '$repo_id', '$repo_name', '$repo_full_name', '$user_id')");
        if ($insert) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Insert failed']);
        }
        exit;
    }
    if ($action === 'get' && $project) {
        $res = query("SELECT * FROM control_center_project_repos WHERE project='$project' LIMIT 1");
        if ($row = fetch_assoc($res)) {
            echo json_encode(['repo' => $row]);
        } else {
            echo json_encode(['repo' => null]);
        }
        exit;
    }
    if ($action === 'create_github_repo' && $project && $user_id) {
        // Token holen
        $res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
        if (!($row = fetch_assoc($res))) {
            echo json_encode(['error' => 'No GitHub token found for user.']);
            exit;
        }
        $token = $row['github_token'];
        $repoName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $project);
        $apiUrl = 'https://api.github.com/user/repos';
        $data = [
            'name' => $repoName,
            'description' => 'Projekt-Repo für ' . $project,
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
        $repo_id = (int)$repo['id'];
        $repo_name = escape_string($repo['name']);
        $repo_full_name = escape_string($repo['full_name']);
        $insert = query("INSERT INTO control_center_project_repos (project, repo_id, repo_name, repo_full_name, user_id) VALUES ('$project', '$repo_id', '$repo_name', '$repo_full_name', '$user_id')");
        if ($insert) {
            echo json_encode(['success' => true, 'repo' => $repo]);
        } else {
            echo json_encode(['error' => 'Insert failed after repo creation', 'repo' => $repo]);
        }
        exit;
    }
}
echo json_encode(['error' => 'Invalid request']);
