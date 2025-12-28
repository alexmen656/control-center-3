<?php
require_once __DIR__ . '/config.php';

$frontend_url_dev = 'http://localhost:5173/my-account/account-security';
$frontend_url_prod = 'https://deine-domain.de/my-account/account-security';
$frontend_url = (defined('APP_ENV') && APP_ENV === 'dev') ? $frontend_url_dev : $frontend_url_prod;

if (!isset($_GET['code'])) {
    die('No code provided');
}

$code = $_GET['code'];

$token_url = 'https://github.com/login/oauth/access_token';
$post_fields = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
        'method' => 'POST',
        'content' => http_build_query($post_fields),
    ],
];
$context = stream_context_create($options);
$response = file_get_contents($token_url, false, $context);

$data = json_decode($response, true);

if (isset($data['access_token'])) {
    $access_token = $data['access_token'];
    // User-ID aus state-Parameter extrahieren (z.B. state=user_12345)
    $userID = 0;
    if (isset($_GET['state']) && preg_match('/user_(\\d+)/', $_GET['state'], $matches)) {
        $userID = intval($matches[1]);
    }
    if ($userID > 0) {
        include_once 'jwt_helper.php';
        include_once 'config.php';

        $origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
        $allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost'];
        $request_host = parse_url($origin_url, PHP_URL_HOST);
        $host_domain = implode('.', array_slice(explode('.', $request_host), -2));
        ini_set('display_errors', true);
        session_start();

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: *');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        include "use_template_function.php";
        include "db_connection.php";
        include "functions.php";

        $access_token_esc = escape_string($access_token);
        $userID_esc = escape_string($userID);
        query("DELETE FROM control_center_github_tokens WHERE userID='$userID_esc'");
        query("INSERT INTO control_center_github_tokens (userID, github_token) VALUES ('$userID_esc', '$access_token_esc')");
        header('Location: ' . $frontend_url . '?github_connected=1');
        exit;
    } else {
        header('Location: ' . $frontend_url . '?github_error=nouser');
        exit;
    }
} else {
    header('Location: ' . $frontend_url . '?github_error=1');
    exit;
}
