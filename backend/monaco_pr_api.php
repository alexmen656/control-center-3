<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

echo json_encode([
    'success' => false,
    'data' => null,
    'message' => 'Pull requests are not supported by the built-in git server',
]);
