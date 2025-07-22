<?php
// github_token_info.php
// Gibt GitHub-Account-Infos zum gespeicherten Token zurück
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/head.php';

header('Content-Type: application/json');

$userID = isset($_GET['userID']) ? intval($_GET['userID']) : 0;
if ($userID > 0) {
    $res = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($userID) . "' LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $token = $row['github_token'];
        // GitHub API-Request für User-Info
        $opts = [
            'http' => [
                'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $info = @file_get_contents('https://api.github.com/user', false, $context);
        if ($info) {
            echo $info;
            exit;
        }
    }
}
echo json_encode(['error' => 'not_connected']);
