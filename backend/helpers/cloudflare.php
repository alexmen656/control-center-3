<?php
class CloudflareHelper
{
    private $zone_id;
    private $api_token;
    private $base_url = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        global $cloudflare_zone_id, $cloudflare_api_token;
        $this->zone_id = $cloudflare_zone_id ?? '';
        $this->api_token = $cloudflare_api_token ?? '';
    }

    public function isConfigured(): bool
    {
        return !empty($this->zone_id) && !empty($this->api_token);
    }

    private function request(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Cloudflare API nicht konfiguriert'];
        }

        $url = "{$this->base_url}/zones/{$this->zone_id}$endpoint";

        $headers = [
            "Authorization: Bearer {$this->api_token}",
            "Content-Type: application/json"
        ];

        $opts = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ];

        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $opts['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['success' => false, 'message' => 'Cloudflare API Request fehlgeschlagen'];
        }

        $result = json_decode($response, true);

        if (!isset($result['success']) || !$result['success']) {
            $error = $result['errors'][0]['message'] ?? 'Unbekannter Cloudflare Fehler';
            return ['success' => false, 'message' => $error, 'errors' => $result['errors'] ?? []];
        }

        return ['success' => true, 'data' => $result['result'] ?? null];
    }

    /**
     * Erstellt einen A-Record
     * 
     * @param string $domain Vollständiger Domain-Name
     * @param string $ip IP-Adresse für den A-Record
     * @param bool $proxied Cloudflare Proxy aktivieren (Orange Cloud)
     * @param int $ttl TTL in Sekunden (1 = Auto wenn proxied)
     */
    public function createARecord(string $domain, string $ip, bool $proxied = false, int $ttl = 300): array
    {
        $data = [
            'type' => 'A',
            'name' => $domain,
            'content' => $ip,
            'ttl' => $proxied ? 1 : $ttl,
            'proxied' => $proxied
        ];

        return $this->request('/dns_records', 'POST', $data);
    }

    /**
     * Erstellt einen CNAME-Record
     * 
     * @param string $domain Vollständiger Domain-Name
     * @param string $target Ziel für den CNAME
     * @param bool $proxied Cloudflare Proxy aktivieren
     * @param int $ttl TTL in Sekunden
     */
    public function createCNAMERecord(string $domain, string $target, bool $proxied = false, int $ttl = 300): array
    {
        $data = [
            'type' => 'CNAME',
            'name' => $domain,
            'content' => $target,
            'ttl' => $proxied ? 1 : $ttl,
            'proxied' => $proxied
        ];

        return $this->request('/dns_records', 'POST', $data);
    }

    public function deleteRecord(string $recordId): array
    {
        if (empty($recordId)) {
            return ['success' => false, 'message' => 'Record ID fehlt'];
        }

        return $this->request("/dns_records/$recordId", 'DELETE');
    }

    /**
     * Findet DNS-Records anhand des Domain-Namens
     * 
     * @param string $domain Domain-Name
     * @param string|null $type Optional: Record-Typ (A, CNAME, etc.)
     */
    public function findRecords(string $domain, ?string $type = null): array
    {
        $query = "?name=" . urlencode($domain);
        if ($type) {
            $query .= "&type=" . urlencode($type);
        }

        return $this->request("/dns_records$query", 'GET');
    }

    /**
     * Löscht einen DNS-Record anhand des Domain-Namens
     * Nützlich wenn keine Record-ID gespeichert wurde
     * 
     * @param string $domain Domain-Name
     * @param string|null $type Optional: Record-Typ zum Filtern
     */
    public function deleteRecordByDomain(string $domain, ?string $type = null): array
    {
        $findResult = $this->findRecords($domain, $type);

        if (!$findResult['success']) {
            return $findResult;
        }

        if (empty($findResult['data'])) {
            return ['success' => false, 'message' => "Domain $domain nicht in Cloudflare Zone gefunden"];
        }

        $recordId = $findResult['data'][0]['id'];
        return $this->deleteRecord($recordId);
    }

    public function updateRecord(string $recordId, array $data): array
    {
        if (empty($recordId)) {
            return ['success' => false, 'message' => 'Record ID fehlt'];
        }

        return $this->request("/dns_records/$recordId", 'PATCH', $data);
    }

    public function listRecords(int $page = 1, int $perPage = 100): array
    {
        return $this->request("/dns_records?page=$page&per_page=$perPage", 'GET');
    }
}

// ============================================
// Standalone Helper-Funktionen für Kompatibilität
// ============================================

/**
 * Erstellt einen Cloudflare A-Record
 * 
 * @param string $domain Domain-Name
 * @param string $ip IP-Adresse
 * @param bool $proxied Cloudflare Proxy aktivieren
 */
function cloudflare_createARecord(string $domain, string $ip, bool $proxied = false): array
{
    $cf = new CloudflareHelper();
    return $cf->createARecord($domain, $ip, $proxied);
}

/**
 * Erstellt einen Cloudflare CNAME-Record
 * 
 * @param string $domain Domain-Name  
 * @param string $target CNAME-Ziel
 * @param bool $proxied Cloudflare Proxy aktivieren
 */
function cloudflare_createCNAMERecord(string $domain, string $target, bool $proxied = false): array
{
    $cf = new CloudflareHelper();
    return $cf->createCNAMERecord($domain, $target, $proxied);
}

/**
 * Löscht einen Cloudflare DNS-Record anhand der ID
 */
function cloudflare_deleteRecord(string $recordId): array
{
    $cf = new CloudflareHelper();
    return $cf->deleteRecord($recordId);
}

/**
 * Löscht einen Cloudflare DNS-Record anhand des Domain-Namens
 */
function cloudflare_deleteRecordByDomain(string $domain, ?string $type = null): array
{
    $cf = new CloudflareHelper();
    return $cf->deleteRecordByDomain($domain, $type);
}

/**
 * Findet Cloudflare DNS-Records anhand des Domain-Namens
 */
function cloudflare_findRecords(string $domain, ?string $type = null): array
{
    $cf = new CloudflareHelper();
    return $cf->findRecords($domain, $type);
}
