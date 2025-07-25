<?php
include_once 'config.php';
include_once 'head.php';
require 'ECSign.php';

$key = <<<EOF
$private_key
EOF;

$header = [
    'alg' => 'ES256',
    'kid' => $key_id,
    'typ' => 'JWT',
];

$payload = [
    'iss' => $issuer_id,
    'exp' => time() + 600,
    'aud' => 'appstoreconnect-v1'
];

$token =  ECSign::sign($payload, $header, $key);

//echo "tokenus: $token";
//echo "privatus: ".$private_key;
// Daten abrufen mit file_get_contents
$reportDate = date('Y-m-d', strtotime('-2 day'));
$url = 'https://api.appstoreconnect.apple.com/v1/salesReports?filter[reportType]=SALES&filter[reportSubType]=SUMMARY&filter[frequency]=DAILY&filter[vendorNumber]=' . $vendor_number . '&filter[reportDate]=' . $reportDate;
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token\r\nAccept: application/a-gzip\r\n"
    ]
];
$context = stream_context_create($opts);
$response = file_get_contents($url, false, $context);
if ($response === false) {
    echo json_encode([]);
    exit;
}

// Beispiel: Entpacken und parsen (hier Dummy-Daten, da echtes Parsing komplex ist)
// $data = ...
$data = [
    ['date' => '2025-07-20', 'count' => 123],
    ['date' => '2025-07-21', 'count' => 98],
    ['date' => '2025-07-22', 'count' => 110],
];
echo json_encode($data);
