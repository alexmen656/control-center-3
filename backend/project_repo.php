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
}
echo json_encode(['error' => 'Invalid request']);
