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


// Entpacken und parsen des Reports (GZIP/TSV)
$downloads = [];
if ($response !== false) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'appstore_report_');
    file_put_contents($tmpFile, $response);
    $unzipped = gzopen($tmpFile, 'r');
    $tsv = '';
    if ($unzipped) {
        while (!gzeof($unzipped)) {
            $tsv .= gzread($unzipped, 4096);
        }
        gzclose($unzipped);
    }
    unlink($tmpFile);

    $lines = explode("\n", $tsv);
    $header = [];
    foreach ($lines as $i => $line) {
        $fields = explode("\t", $line);
        if ($i === 0) {
            $header = $fields;
            continue;
        }
        if (count($fields) < 5) continue; // skip empty/invalid lines

        // Mapping: Datum, Anzahl, Version, Land, Plattform
        // Apple-typische Felder: 'Begin Date', 'Units', 'App Version', 'Country Code', 'Platform'
        $dateIdx = array_search('Begin Date', $header);
        $countIdx = array_search('Units', $header);
        $versionIdx = array_search('App Version', $header);
        $countryIdx = array_search('Country Code', $header);
        $platformIdx = array_search('Platform', $header);
        if ($dateIdx === false || $countIdx === false) continue;

        $downloads[] = [
            'date' => $fields[$dateIdx],
            'count' => (int)$fields[$countIdx],
            'version' => $versionIdx !== false ? $fields[$versionIdx] : '',
            'country' => $countryIdx !== false ? $fields[$countryIdx] : '',
            'platform' => $platformIdx !== false ? $fields[$platformIdx] : ''
        ];
    }
}

// Statistiken berechnen
$total = count($downloads) ? array_sum(array_column($downloads, 'count')) : 0;
$average = count($downloads) ? $total / count($downloads) : 0;
$topDay = null;
$topCount = 0;
foreach ($downloads as $item) {
    if ($item['count'] > $topCount) {
        $topCount = $item['count'];
        $topDay = $item['date'];
    }
}

$result = [
    'downloads' => $downloads,
    'stats' => [
        'total' => $total,
        'average' => round($average, 2),
        'topDay' => $topDay,
        'topCount' => $topCount
    ]
];
echo json_encode($result);
