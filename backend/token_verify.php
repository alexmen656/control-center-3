<?php
include_once 'jwt_helper.php';
include_once 'config.php';
include '/www/paxar/components/php_head.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

$headers = getallheaders();
$auth = isset($headers['Authorization']) ? $headers['Authorization'] : null;

function verify_jwt($jwt, $secret) {
    if (!$jwt) return false;
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    list($header64, $payload64, $sig64) = $parts;
    $header = json_decode(base64_decode(strtr($header64, '-_', '+/')), true);
    $payload = json_decode(base64_decode(strtr($payload64, '-_', '+/')), true);
    $sig = base64_decode(strtr($sig64, '-_', '+/'));
    if (!$header || !$payload || !$sig) return false;
    if (empty($payload['exp']) || time() > $payload['exp']) return false;
    $valid_sig = SimpleJWT::sign("$header64.$payload64", $secret, $header['alg']);
    if (!hash_equals($valid_sig, $sig)) return false;
    return true;
}

$valid = verify_jwt($auth, $jwt_secret);

// If valid, also return user data for MCP server
if ($valid && $auth) {
    $payload = SimpleJWT::verify($auth, $jwt_secret);
    if ($payload && !empty($payload['sub'])) {
        $userId = intval($payload['sub']);
        $userResult = query("SELECT userID, email, firstname, lastname, profileImg FROM control_center_users WHERE userID = $userId");
        if ($userResult && mysqli_num_rows($userResult) > 0) {
            $userData = fetch_assoc($userResult);
            echo json_encode([
                "valid" => true,
                "user" => [
                    "id" => $userData['userID'],
                    "userID" => $userData['userID'],
                    "email" => $userData['email'],
                    "firstName" => $userData['firstname'],
                    "lastName" => $userData['lastname'],
                    "profileImg" => $userData['profileImg']
                ]
            ]);
            exit;
        }
    }
}

echo json_encode(["valid" => $valid]);
