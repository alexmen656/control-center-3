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

$headers = getRequestHeaders();

if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload || empty($payload['sub'])) {
        echo "No valid token";
        exit;
    }
    $userID = intval($payload['sub']);
    $data = query("SELECT * FROM control_center_users WHERE userID='$userID'");
    if (mysqli_num_rows($data) == 1) {
        $data = fetch_assoc($data);
        if (isset($_REQUEST['firstName'])) {
            $firstName = escape_string($_REQUEST['firstName']);
            $lastName = escape_string($_REQUEST['lastName']);
            $email = escape_string($_REQUEST['email']);
            query("UPDATE control_center_users SET email='$email', firstname='$firstName', lastname='$lastName' WHERE userID='$userID'");
            echo "Profile updated";
        } elseif (isset($_REQUEST['updateProfileImage']) && isset($_REQUEST['data']) && isset($_REQUEST['name'])) {
            $baseData = escape_string($_REQUEST['data']);
            $fileName = escape_string($_REQUEST['name']);
            query("UPDATE control_center_users SET profileImg='$fileName' WHERE userID='$userID'");
            if (createFile('images/profileImages/' . $fileName, $baseData, 0777)) {
                echo "file created";
            }
        } elseif (isset($_REQUEST['updateLoginWithGoogle']) && isset($_REQUEST['newValue'])) {
            $newValue = escape_string($_REQUEST['newValue']);
            if (query("UPDATE control_center_users SET login_with_google='$newValue' WHERE userID='$userID'")) {
                echo "Success!";
            }
        } else {
            if ($data['profileImg'] != "avatar" && $data['profileImg'] != "google") {
                $data['profileImg'] = file_get_contents('images/profileImages/' . $data['profileImg']);
            } elseif ($data['profileImg'] == "google") {
                $userID = $data['userID'];
                $select = query("SELECT * FROM control_center_google_profile_images WHERE userID=$userID");
                if (mysqli_num_rows($select) == 1) {
                    $data['profileImg'] = fetch_assoc($select)['image'];
                }
            }
            $json['profileImg'] = $data['profileImg'];
            $json['firstName'] = $data['firstname'];
            $json['lastName'] = $data['lastname'];
            $json['email'] = $data['email'];
            $json['userID'] = $data['userID'];
            if ($data['login_with_google'] == 'true') {
                $json['login_with_google'] = true;
            } elseif ($data['login_with_google'] == 'false') {
                $json['login_with_google'] = false;
            } else {
                $json['login_with_google'] = $data['login_with_google'];
            }
            $json['accountStatus'] = $data['account_status'];
            echo preg_replace('/^\h*\v+/m', '', echoJson($json));
        }
    } else {
        echo "No valid token";
    }
} else if (isset($_POST['email'])) {
    // Fallback: check if email exists (for registration, password reset, etc.)
    $email = escape_string($_POST['email']);
    $data = query("SELECT * FROM control_center_users WHERE email='$email'");
    if (mysqli_num_rows($data) > 0) {
        $value['value'] = true;
    } else {
        $value['value'] = false;
    }
    echo echoJson($value);
}
