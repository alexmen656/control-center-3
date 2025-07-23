<?php
// vercel_token_status.php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

$userID = isset($_GET['userID']) ? intval($_GET['userID']) : 0;
if ($userID > 0) {
    $res = query("SELECT id FROM control_center_vercel_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if (mysqli_num_rows($res) > 0) {
        echo json_encode(['connected' => true]);
        exit;
    }
}
echo json_encode(['connected' => false]);
