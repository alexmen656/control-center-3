<?php
include_once 'jwt_helper.php';
include_once 'config.php';
include_once 'head.php';

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
echo json_encode(["valid" => $valid]);
