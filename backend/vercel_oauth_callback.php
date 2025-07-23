<?php
// vercel_oauth_callback.php
require_once __DIR__ . '/config.php';

$frontend_url_dev = 'http://localhost:5173/my-account/account-security';
$frontend_url_prod = 'https://deine-domain.de/my-account/account-security';
$frontend_url = (defined('APP_ENV') && APP_ENV === 'dev') ? $frontend_url_dev : $frontend_url_prod;

if (!isset($_GET['code'])) {
    die('No code provided');
}

$code = $_GET['code'];

// Vercel OAuth Token Exchange
$token_url = 'https://api.vercel.com/v2/oauth/access_token';
$post_fields = [
    'client_id' => $vercel_client_id, // in config.php definieren
    'client_secret' => $vercel_client_secret, // in config.php definieren
    'code' => $code,
    'redirect_uri' => $vercel_redirect_uri // in config.php definieren
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
    // User-ID aus state-Parameter extrahieren (z.B. state=user_12345)
    $userID = 0;
    if (isset($_GET['state']) && preg_match('/user_(\\d+)/', $_GET['state'], $matches)) {
        $userID = intval($matches[1]);
    }
    if ($userID > 0) {
        require_once __DIR__ . '/head.php';
        $access_token_esc = escape_string($access_token);
        $userID_esc = escape_string($userID);
        query("DELETE FROM control_center_vercel_tokens WHERE userID='$userID_esc'");
        query("INSERT INTO control_center_vercel_tokens (userID, vercel_token) VALUES ('$userID_esc', '$access_token_esc')");
        header('Location: ' . $frontend_url . '?vercel_connected=1');
        exit;
    } else {
        header('Location: ' . $frontend_url . '?vercel_error=nouser');
        exit;
    }
} else {
    header('Location: ' . $frontend_url . '?vercel_error=1');
    exit;
}
