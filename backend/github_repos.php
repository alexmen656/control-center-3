<?php
// github_repos.php
// Gibt alle Repositories des verbundenen GitHub-Users zurück

require_once 'config.php';
require_once 'head.php';

header('Content-Type: application/json');

$userID = isset($_GET['userID']) ? $_GET['userID'] : null;
if (!$userID) {
    echo json_encode(['error' => 'No userID provided']);
    exit;
}

$res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
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
