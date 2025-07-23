<?php
// vercel_projects.php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id > 0) {
    $tokenRes = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
    if ($tokenRow = fetch_assoc($tokenRes)) {
        $token = $tokenRow['vercel_token'];
        $apiUrl = 'https://api.vercel.com/v9/projects';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($apiUrl, false, $context);
        if ($result) {
            $data = json_decode($result, true);
            echo json_encode(['projects' => $data['projects'] ?? []]);
            exit;
        }
    }
}
echo json_encode(['projects' => []]);
