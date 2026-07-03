<?php
require_once __DIR__ . '/config.php';

$frontend_url_dev = 'http://localhost:5173/my-account/account-security';
$frontend_url_prod = 'https://app.fringelo.com/my-account/account-security';
$frontend_url = (defined('APP_ENV') && APP_ENV === 'dev') ? $frontend_url_dev : $frontend_url_prod;

header('Location: ' . $frontend_url . '?github_disabled=1');
exit;
