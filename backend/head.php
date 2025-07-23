<?php
include_once 'jwt_helper.php';
include_once 'config.php';

$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
//  header('HTTP/1.0 403 Forbidden');
//  die('You are not allowed to access this.');     
//}
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';

// JWT prüfen
$headers = function_exists('getallheaders') ? getallheaders() : [];
if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'No valid token']);
        exit;
    }
} else {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'No valid token']);
    exit;
}

function randomNumber(){
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json){
    return json_encode($json, JSON_PRETTY_PRINT);
}
