<?php
// project_vercel.php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $project = escape_string($_POST['project'] ?? '');
    $user_id = escape_string($_POST['user_id'] ?? '');
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
