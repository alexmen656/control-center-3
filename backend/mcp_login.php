<?php
ini_set('display_errors', false);
error_reporting(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'db_connection.php';
require_once 'helpers/jwt.php';
require_once 'config.php';
require_once 'functions.php';

$email = isset($_POST['email']) ? escape_string($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required.']);
    exit;
}

$select = query("SELECT * FROM control_center_users WHERE email='$email'");

if (!$select || mysqli_num_rows($select) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

$data = fetch_assoc($select);

if (!password_verify($password, $data['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid email or password.']);
    exit;
}

$payload = [
    'sub' => $data['userID'],
    'email' => $data['email'],
    'firstname' => $data['firstname'],
    'iat' => time(),
    'exp' => time() + 60 * 60 * 24 * 7,
];

$jwt = SimpleJWT::encode($payload, $jwt_secret);

echo json_encode([
    'token' => $jwt,
    'expires_in' => 60 * 60 * 24 * 7,
    'user' => [
        'id' => $data['userID'],
        'userID' => $data['userID'],
        'email' => $data['email'],
        'firstName' => $data['firstname'],
        'lastName' => $data['lastname'],
        'profileImg' => $data['profileImg'] ?? null,
    ],
]);
