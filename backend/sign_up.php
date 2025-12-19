<?php
include_once 'jwt_helper.php';
include_once 'config.php';

$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost'];
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
//  header('HTTP/1.0 403 Forbidden');
//  die('You are not allowed to access this.');     
//}
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

if (isset($_POST['first_name']) && isset($_POST['email_adress']) && isset($_POST['password']) && isset($_POST['login_with_google'])) {
    $first_name = escape_string($_POST['first_name']);
    $last_name = escape_string($_POST['last_name']);
    $email_adress = escape_string($_POST['email_adress']);
    $password = escape_string($_POST['password']);
    $password = password_hash($password, PASSWORD_DEFAULT);
    $login_with_google = escape_string($_POST['login_with_google']);
    $token = bin2hex(random_bytes(72));
    $img = 'avatar';
    if ($login_with_google == 'true') {
        $img = 'google';
    } else if ($login_with_google == 'microsoft') {
        $img = 'avatar';
    }

    if (query("INSERT INTO control_center_users VALUES(0, '$img', '$first_name', '$last_name', '$email_adress', '$password', '$login_with_google', '$token', 'pending_verification')")) {
        $json_token['token'] = $token;
        echo echoJSON($json_token);
        $userID_query = query("SELECT * FROM control_center_users WHERE email = '$email_adress'");
        if (mysqli_num_rows($userID_query) == 1) {
            $userID = fetch_assoc($userID_query)['userID'];
            if ($login_with_google == 'true') {
                $profile_img = escape_string($_POST['profile_img']);
                query("INSERT INTO control_center_google_profile_images VALUES(0, '$profile_img', $userID)");
            }
        }
    }
}
