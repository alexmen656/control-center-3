<?php
// github_token_status.php
// Gibt zurück, ob ein GitHub-Token für den User existiert
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/head.php';

header('Content-Type: application/json');

$userID = isset($_GET['userID']) ? intval($_GET['userID']) : 0;
if ($userID > 0) {
    $res = query("SELECT id FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        echo json_encode(['connected' => true]);
        exit;
    }
}
echo json_encode(['connected' => false]);
