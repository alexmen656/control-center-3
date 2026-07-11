<?php
require_once 'helpers/jwt.php';
require_once 'config.php';
ini_set('display_errors', '0');
$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost'];
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', (string) $request_host), -2));
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
include './helpers/db_connection.php';
include 'functions.php';

if (!empty($_POST['verificationToken'])) {
    $token = escape_string($_POST['verificationToken']);
    $userData = fetch_assoc(query("SELECT *, control_center_login_log.token AS token2 FROM control_center_login_log JOIN control_center_users ON control_center_login_log.userID=control_center_users.userID WHERE control_center_login_log.token='$token'"));

    if (!empty($_POST['verificationCode'])) {
        $query = query("SELECT * FROM control_center_login_log WHERE token='$token'");
        if (mysqli_num_rows($query) == 1) {

            $logData = fetch_assoc($query);
            if (str_replace(" ", "", escape_string($_POST['verificationCode'])) == $logData['verification_code']) {
                $loginToken = $userData['loginToken'];
                $json['token'] = $loginToken;
                $updateLog = query("UPDATE control_center_login_log SET action='successfull' WHERE token='$token'");
                echo json_encode($json, JSON_PRETTY_PRINT);

            }
        }
    }


}

?>