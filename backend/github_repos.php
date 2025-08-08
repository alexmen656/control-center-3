<?php
require_once 'config.php';
require_once 'head.php';

header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
if (!$user_id) {
    echo json_encode(['error' => 'No user_id provided']);
    exit;
}

$res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
if ($row = fetch_assoc($res)) {
    $token = $row['github_token'];
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $repos = @file_get_contents('https://api.github.com/user/repos?per_page=100', false, $context);
    if ($repos !== false) {
        echo $repos;
    } else {
        echo json_encode(['error' => 'Could not fetch repos']);
    }
} else {
    echo json_encode(['error' => 'No token found']);
}
