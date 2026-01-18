<?php
require_once "head.php";
require_once __DIR__ . '/helpers/cloudflare.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: [];
$action = $data['action'] ?? $_POST['action'] ?? '';

function ensureDomainsTable()
{
    $sql = "CREATE TABLE IF NOT EXISTS domains (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        domain VARCHAR(255) NOT NULL,
        registrar VARCHAR(255) DEFAULT NULL,
        buy_date DATE DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        cloudflare_zone_id VARCHAR(100) DEFAULT NULL,
        auto_renew BOOLEAN DEFAULT FALSE,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_domain (domain),
        INDEX idx_expiry_date (expiry_date)
    )";
    query($sql);
}

ensureDomainsTable();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'list') {
    $result = query("SELECT * FROM domains WHERE user_id='$userID' ORDER BY domain ASC");
    $domains = [];

    while ($row = fetch_assoc($result)) {
        $domains[] = [
            'id' => $row['id'],
            'domain' => $row['domain'],
            'registrar' => $row['registrar'],
            'buy_date' => $row['buy_date'],
            'expiry_date' => $row['expiry_date'],
            'cloudflare_zone_id' => $row['cloudflare_zone_id'],
            'auto_renew' => (bool) $row['auto_renew'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode(['success' => true, 'domains' => $domains]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'fetch_cloudflare') {
    global $cloudflare_api_token;

    if (empty($cloudflare_api_token)) {
        echo json_encode(['success' => false, 'error' => 'Cloudflare API Token nicht konfiguriert']);
        exit;
    }

    $url = 'https://api.cloudflare.com/client/v4/zones?per_page=50';

    $headers = [
        "Authorization: Bearer {$cloudflare_api_token}",
        "Content-Type: application/json"
    ];

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode(['success' => false, 'error' => 'Cloudflare API Request fehlgeschlagen']);
        exit;
    }

    $result = json_decode($response, true);

    if (!isset($result['success']) || !$result['success']) {
        $error = $result['errors'][0]['message'] ?? 'Unbekannter Cloudflare Fehler';
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $zones = $result['result'] ?? [];
    $domains = [];
    $imported = 0;
    $skipped = 0;

    foreach ($zones as $zone) {
        $domainName = $zone['name'] ?? '';
        $zoneId = $zone['id'] ?? '';

        if (empty($domainName))
            continue;

        $escapedDomain = escape_string($domainName);
        $existing = query("SELECT id FROM domains WHERE user_id='$userID' AND domain='$escapedDomain' LIMIT 1");

        if (fetch_assoc($existing)) {
            $skipped++;
            continue;
        }

        $escapedZoneId = escape_string($zoneId);
        query("INSERT INTO domains (user_id, domain, cloudflare_zone_id) 
               VALUES ('$userID', '$escapedDomain', '$escapedZoneId')");

        $domains[] = $domainName;
        $imported++;
    }

    echo json_encode([
        'success' => true,
        'message' => "$imported neue Domain(s) importiert" . ($skipped > 0 ? ", $skipped bereits vorhanden" : ""),
        'imported' => $domains,
        'total_zones' => count($zones),
        'imported_count' => $imported,
        'skipped_count' => $skipped
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {


    $id = isset($data['id']) ? intval($data['id']) : null;
    $domain = escape_string($data['domain'] ?? '');
    $registrar = escape_string($data['registrar'] ?? '');
    $buyDate = $data['buy_date'] ?? null;
    $expiryDate = $data['expiry_date'] ?? null;
    $autoRenew = isset($data['auto_renew']) ? ($data['auto_renew'] ? 1 : 0) : 0;
    $notes = escape_string($data['notes'] ?? '');

    if (!$domain) {
        echo json_encode(['success' => false, 'error' => 'Domain fehlt']);
        exit;
    }

    if ($buyDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $buyDate)) {
        $buyDate = null;
    }

    if ($expiryDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
        $expiryDate = null;
    }

    if ($id) {
        $buyDateSQL = $buyDate ? "'$buyDate'" : "NULL";
        $expiryDateSQL = $expiryDate ? "'$expiryDate'" : "NULL";

        query("UPDATE domains SET 
               domain='$domain',
               registrar='$registrar',
               buy_date=$buyDateSQL,
               expiry_date=$expiryDateSQL,
               auto_renew='$autoRenew',
               notes='$notes'
               WHERE id='$id' AND user_id='$userID'");

        echo json_encode(['success' => true, 'message' => 'Domain aktualisiert']);
    } else {
        $buyDateSQL = $buyDate ? "'$buyDate'" : "NULL";
        $expiryDateSQL = $expiryDate ? "'$expiryDate'" : "NULL";

        query("INSERT INTO domains (user_id, domain, registrar, buy_date, expiry_date, auto_renew, notes) 
               VALUES ('$userID', '$domain', '$registrar', $buyDateSQL, $expiryDateSQL, '$autoRenew', '$notes')");

        echo json_encode(['success' => true, 'message' => 'Domain hinzugefügt']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {


    $id = isset($data['id']) ? intval($data['id']) : 0;

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Domain ID fehlt']);
        exit;
    }

    query("DELETE FROM domains WHERE id='$id' AND user_id='$userID'");

    echo json_encode(['success' => true, 'message' => 'Domain gelöscht']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'expiring') {


    $result = query("SELECT * FROM domains 
                     WHERE user_id='$userID' 
                     AND expiry_date IS NOT NULL 
                     AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                     AND expiry_date >= CURDATE()
                     ORDER BY expiry_date ASC");

    $domains = [];
    while ($row = fetch_assoc($result)) {
        $domains[] = [
            'id' => $row['id'],
            'domain' => $row['domain'],
            'registrar' => $row['registrar'],
            'expiry_date' => $row['expiry_date'],
            'auto_renew' => (bool) $row['auto_renew']
        ];
    }

    echo json_encode(['success' => true, 'domains' => $domains]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'list_available') {
    // Nur Super Admin (userID 152) darf Domains verbinden
    if ($userID != 152) {
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        exit;
    }

    $result = query("SELECT * FROM domains WHERE user_id='$userID' AND cloudflare_zone_id IS NOT NULL ORDER BY domain ASC");
    $domains = [];

    while ($row = fetch_assoc($result)) {
        $domains[] = [
            'id' => $row['id'],
            'domain' => $row['domain'],
            'cloudflare_zone_id' => $row['cloudflare_zone_id']
        ];
    }

    echo json_encode([
        'success' => true, 
        'domains' => $domains, 
        'is_super_admin' => true,
        'features' => [
            'custom_domains' => true,
            'subdomains' => true,
            'main_domain_usage' => true
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
