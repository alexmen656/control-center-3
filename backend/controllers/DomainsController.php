<?php

require_once __DIR__ . '/../helpers/cloudflare.php';

class DomainsController
{
    public function __construct()
    {
        $this->ensureDomainsTable();
    }

    private function ensureDomainsTable(): void
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

    public function list(Request $request, Response $response): void
    {
        $userID = $request->userID;
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

        $response->json(['success' => true, 'domains' => $domains]);
    }

    public function fetchCloudflare(Request $request, Response $response): void
    {
        global $cloudflare_api_token;
        $userID = $request->userID;

        if (empty($cloudflare_api_token)) {
            $response->error('Cloudflare API Token nicht konfiguriert', 400);
            return;
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
        $cfResponse = @file_get_contents($url, false, $context);

        if ($cfResponse === false) {
            $response->error('Cloudflare API Request fehlgeschlagen', 500);
            return;
        }

        $result = json_decode($cfResponse, true);

        if (!isset($result['success']) || !$result['success']) {
            $error = $result['errors'][0]['message'] ?? 'Unbekannter Cloudflare Fehler';
            $response->error($error, 502);
            return;
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

        $response->json([
            'success' => true,
            'message' => "$imported neue Domain(s) importiert" . ($skipped > 0 ? ", $skipped bereits vorhanden" : ""),
            'imported' => $domains,
            'total_zones' => count($zones),
            'imported_count' => $imported,
            'skipped_count' => $skipped
        ]);
    }

    public function save(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $id = $request->input('id') ? intval($request->input('id')) : null;
        $domain = escape_string($request->input('domain', ''));
        $registrar = escape_string($request->input('registrar', ''));
        $buyDate = $request->input('buy_date');
        $expiryDate = $request->input('expiry_date');
        $autoRenew = $request->input('auto_renew') ? 1 : 0;
        $notes = escape_string($request->input('notes', ''));

        if (!$domain) {
            $response->error('Domain fehlt', 400);
            return;
        }

        if ($buyDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $buyDate)) {
            $buyDate = null;
        }

        if ($expiryDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            $expiryDate = null;
        }

        $buyDateSQL = $buyDate ? "'$buyDate'" : "NULL";
        $expiryDateSQL = $expiryDate ? "'$expiryDate'" : "NULL";

        if ($id) {
            query("UPDATE domains SET
                   domain='$domain',
                   registrar='$registrar',
                   buy_date=$buyDateSQL,
                   expiry_date=$expiryDateSQL,
                   auto_renew='$autoRenew',
                   notes='$notes'
                   WHERE id='$id' AND user_id='$userID'");

            $response->success([], 'Domain aktualisiert');
        } else {
            query("INSERT INTO domains (user_id, domain, registrar, buy_date, expiry_date, auto_renew, notes)
                   VALUES ('$userID', '$domain', '$registrar', $buyDateSQL, $expiryDateSQL, '$autoRenew', '$notes')");

            $response->success([], 'Domain hinzugefügt');
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $id = intval($request->params['id']);

        if (!$id) {
            $response->error('Domain ID fehlt', 400);
            return;
        }

        query("DELETE FROM domains WHERE id='$id' AND user_id='$userID'");
        $response->success([], 'Domain gelöscht');
    }

    public function subdomains(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $id = intval($request->params['id']);

        if (!$id) {
            $response->error('Domain ID fehlt', 400);
            return;
        }

        $domainResult = query("SELECT domain FROM domains WHERE id='$id' AND user_id='$userID' LIMIT 1");
        $domainRow = fetch_assoc($domainResult);

        if (!$domainRow) {
            $response->error('Domain nicht gefunden', 404);
            return;
        }

        $mainDomain = $domainRow['domain'];
        $escapedMain = escape_string($mainDomain);
        $suffixLen = strlen('.' . $mainDomain);
        $subdomains = [];
        $seen = [];

        $cdResult = query("
            SELECT cd.domain, p.link AS project_link, p.name AS project_name, pc.name AS codespace_name
            FROM codespace_domains cd
            JOIN project_codespaces pc ON cd.codespace_id = pc.id
            JOIN projects p ON p.projectID = pc.project_id
            WHERE cd.domain LIKE '%.$escapedMain'
            ORDER BY cd.domain ASC
        ");

        while ($cdResult && $row = fetch_assoc($cdResult)) {
            $full = $row['domain'];
            if (isset($seen[$full])) {
                continue;
            }
            $seen[$full] = true;
            $label = rtrim(substr($full, 0, -$suffixLen), '.');
            $subdomains[] = [
                'subdomain' => $label !== '' ? $label : $full,
                'domain' => $full,
                'project_link' => $row['project_link'],
                'project_name' => $row['project_name'] ?: $row['project_link'],
                'codespace_name' => $row['codespace_name'],
                'is_enabled' => true,
                'ssl_status' => null,
                'source' => 'codespace_domain'
            ];
        }

        $response->json(['success' => true, 'subdomains' => $subdomains]);
    }

    public function expiring(Request $request, Response $response): void
    {
        $userID = $request->userID;
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

        $response->json(['success' => true, 'domains' => $domains]);
    }

    public function listAvailable(Request $request, Response $response): void
    {
        $userID = $request->userID;

        if ($userID != 152) {
            $response->error('Keine Berechtigung', 403);
            return;
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

        $response->json([
            'success' => true,
            'domains' => $domains,
            'is_super_admin' => true,
            'features' => [
                'custom_domains' => true,
                'subdomains' => true,
                'main_domain_usage' => true
            ]
        ]);
    }
}
