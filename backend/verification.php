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
// OPTIONS-Requests (Preflight) direkt beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
include '/www/paxar/components/php_head.php';

function randomNumber(){
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json){
    return json_encode($json, JSON_PRETTY_PRINT);
}

if ($_POST['verificationToken']) {
    $token = escape_string($_POST['verificationToken']);
    $userData = fetch_assoc(query("SELECT *, control_center_login_log.token AS token2 FROM control_center_login_log JOIN control_center_users ON control_center_login_log.userID=control_center_users.userID WHERE control_center_login_log.token='$token'"));
    //echo $userData[];


    if ($_POST['verificationCode']) {
        $query = query("SELECT * FROM control_center_login_log WHERE token='$token'");
        if (mysqli_num_rows($query) == 1) {

            $logData = fetch_assoc($query);
            //         echo "I get this code: ".escape_string($_POST['verificationCode']).",";
            //echo $logData['verification_code'];
            //print_r($logData);
            if (str_replace(" ", "", escape_string($_POST['verificationCode'])) == $logData['verification_code']) {
                $loginToken = $userData['loginToken'];
                //  
                //  echo "Code is true";
                //verification_code
                $json['token'] = $loginToken;
                $updateLog = query("UPDATE control_center_login_log SET action='successfull' WHERE token='$token'");
                echo json_encode($json, JSON_PRETTY_PRINT);

            }
        }
    }


}

?>