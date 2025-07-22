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
        'header'  => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
        'method'  => 'POST',
        'content' => http_build_query($post_fields),
    ],
];
$context  = stream_context_create($options);
$response = file_get_contents($token_url, false, $context);

$data = json_decode($response, true);

if (isset($data['access_token'])) {
    $access_token = $data['access_token'];
    // TODO: Speichere das Token in der Datenbank oder Session für den User
    // Beispiel: $_SESSION['github_access_token'] = $access_token;
    // Weiterleitung ins Frontend mit Status
    header('Location: ' . $frontend_url . '?github_connected=1');
    exit;
} else {
    header('Location: ' . $frontend_url . '?github_error=1');
    exit;
}
