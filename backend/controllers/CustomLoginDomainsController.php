<?php

require_once __DIR__ . '/../helpers/project.php';
require_once __DIR__ . '/../helpers/cloudflare.php';

class CustomLoginDomainsController
{
    const LOGIN_SERVER_IP = '92.5.112.145';
    const INTERNAL_BASE_DOMAIN = 'control-center.eu';
    const NGINX_WEBHOOK_URL = 'https://webhook.control-center.eu/custom_login_webhook.php';
    const WEBHOOK_SECRET = 'cc_custom_login_webhook_secret_2025';

    public function __construct()
    {
        $this->ensureCustomLoginDomainsTable();
    }

    private function ensureCustomLoginDomainsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS custom_login_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            projectID VARCHAR(255) NOT NULL,
            domain VARCHAR(255) NOT NULL UNIQUE,
            domain_type ENUM('internal', 'external') DEFAULT 'internal',
            is_enabled BOOLEAN DEFAULT FALSE,
            primary_color VARCHAR(20) DEFAULT '#e53e3e',
            logo_url VARCHAR(500) DEFAULT NULL,
            company_name VARCHAR(255) DEFAULT NULL,
            cloudflare_record_id VARCHAR(100) DEFAULT NULL,
            ssl_status ENUM('pending', 'active', 'failed', 'manual') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_domain (domain),
            INDEX idx_projectID (projectID)
        )";
        query($sql);

        $checkColumn = query("SHOW COLUMNS FROM custom_login_domains LIKE 'domain_type'");
        if (mysqli_num_rows($checkColumn) == 0) {
            query("ALTER TABLE custom_login_domains ADD COLUMN domain_type ENUM('internal', 'external') DEFAULT 'internal' AFTER domain");
        }
    }

    private function isInternalDomain($domain)
    {
        return str_ends_with($domain, '.' . self::INTERNAL_BASE_DOMAIN);
    }

    private function triggerNginxWebhook($domain, $action = 'add')
    {
        if (!$this->isInternalDomain($domain)) {
            return ['skipped' => true, 'message' => 'External domain - manual setup required'];
        }

        $data = json_encode([
            'action' => $action,
            'domain' => $domain
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Webhook-Secret: " . self::WEBHOOK_SECRET . "\r\n",
                'content' => $data,
                'timeout' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents(self::NGINX_WEBHOOK_URL, false, $context);

        if ($response) {
            $result = json_decode($response, true);
            error_log("Nginx webhook response for $domain ($action): " . $response);
            return $result;
        }

        error_log("Nginx webhook failed for $domain ($action)");
        return null;
    }

    private function createCloudflareARecord($domain)
    {
        if (!$this->isInternalDomain($domain)) {
            return ['success' => true, 'data' => ['id' => null], 'external' => true];
        }

        return cloudflare_createARecord($domain, self::LOGIN_SERVER_IP, true);
    }

    private function deleteCloudflareRecord($recordId)
    {
        return cloudflare_deleteRecord($recordId);
    }

    public function get(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $projectData = getProjectByLink($project);

        if (!$projectData) {
            $response->json(['error' => 'Projekt nicht gefunden']);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectData['projectID'])) {
            $response->json(['error' => 'Keine Berechtigung']);
            return;
        }

        $result = query("SELECT * FROM custom_login_domains WHERE projectID='{$projectData['projectID']}'");

        if ($row = fetch_assoc($result)) {
            $domainType = $row['domain_type'] ?? ($this->isInternalDomain($row['domain']) ? 'internal' : 'external');
            $isInternal = $domainType === 'internal';

            $responseData = [
                'id' => $row['id'],
                'domain' => $row['domain'],
                'domain_type' => $domainType,
                'is_internal' => $isInternal,
                'is_enabled' => (bool)$row['is_enabled'],
                'primary_color' => $row['primary_color'],
                'logo_url' => $row['logo_url'],
                'company_name' => $row['company_name'],
                'ssl_status' => $row['ssl_status'],
                'created_at' => $row['created_at']
            ];

            if (!$isInternal) {
                $responseData['setup_instructions'] = [
                    'dns' => [
                        'type' => 'A',
                        'name' => $row['domain'],
                        'value' => self::LOGIN_SERVER_IP,
                        'info' => 'Erstelle einen A-Record der auf ' . self::LOGIN_SERVER_IP . ' zeigt'
                    ],
                    'nginx' => 'Nginx muss manuell auf deinem Server konfiguriert werden',
                    'ssl' => 'SSL muss manuell eingerichtet werden (z.B. via Certbot oder Cloudflare)'
                ];
            }

            $response->json([
                'success' => true,
                'data' => $responseData
            ]);
        } else {
            $response->json(['success' => true, 'data' => null]);
        }
    }

    public function save(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));
        $domain = escape_string($request->input('domain', ''));
        $is_enabled = ($request->input('is_enabled', 'false')) === 'true' ? 1 : 0;
        $primary_color = escape_string($request->input('primary_color', '#e53e3e'));
        $logo_url = escape_string($request->input('logo_url', ''));
        $company_name = escape_string($request->input('company_name', ''));

        $projectData = getProjectByLink($project);

        if (!$projectData) {
            $response->json(['error' => 'Projekt nicht gefunden']);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectData['projectID'])) {
            $response->json(['error' => 'Keine Berechtigung']);
            return;
        }

        $projectID = $projectData['projectID'];

        if (empty($domain)) {
            $response->json(['error' => 'Domain ist erforderlich']);
            return;
        }

        $isInternal = $this->isInternalDomain($domain);
        $domainType = $isInternal ? 'internal' : 'external';
        $sslStatus = $isInternal ? 'pending' : 'manual';

        $existingDomain = query("SELECT * FROM custom_login_domains WHERE domain='$domain' AND projectID != '$projectID'");
        if (fetch_assoc($existingDomain)) {
            $response->json(['error' => 'Diese Domain wird bereits von einem anderen Projekt verwendet']);
            return;
        }

        $existing = query("SELECT * FROM custom_login_domains WHERE projectID='$projectID'");
        $existingRow = fetch_assoc($existing);

        if ($existingRow) {
            if ($existingRow['domain'] !== $domain) {
                $oldDomain = $existingRow['domain'];
                $wasInternal = $this->isInternalDomain($oldDomain);

                if ($wasInternal && $existingRow['cloudflare_record_id']) {
                    $this->deleteCloudflareRecord($existingRow['cloudflare_record_id']);
                }

                if ($wasInternal) {
                    $this->triggerNginxWebhook($oldDomain, 'remove');
                }

                $cloudflareResult = $this->createCloudflareARecord($domain);

                if (!$cloudflareResult['success']) {
                    $response->json(['error' => 'Cloudflare Fehler: ' . $cloudflareResult['message']]);
                    return;
                }

                $cloudflare_record_id = $cloudflareResult['data']['id'] ?? null;
                $cloudflare_record_id_sql = $cloudflare_record_id ? "'$cloudflare_record_id'" : "NULL";

                $sql = "UPDATE custom_login_domains SET
                    domain='$domain',
                    domain_type='$domainType',
                    is_enabled=$is_enabled,
                    primary_color='$primary_color',
                    logo_url='$logo_url',
                    company_name='$company_name',
                    cloudflare_record_id=$cloudflare_record_id_sql,
                    ssl_status='$sslStatus'
                    WHERE projectID='$projectID'";

                if (query($sql)) {
                    if ($isInternal) {
                        $this->triggerNginxWebhook($domain, 'add');
                    }

                    $result = [
                        'success' => true,
                        'message' => $isInternal ? 'Custom Login aktualisiert' : 'Custom Login aktualisiert - DNS muss manuell konfiguriert werden',
                        'domain' => $domain,
                        'is_internal' => $isInternal
                    ];

                    if (!$isInternal) {
                        $result['setup_instructions'] = [
                            'dns' => [
                                'type' => 'A',
                                'name' => $domain,
                                'value' => self::LOGIN_SERVER_IP
                            ]
                        ];
                    }

                    $response->json($result);
                } else {
                    $response->json(['error' => 'Fehler beim Aktualisieren']);
                }
            } else {
                $sql = "UPDATE custom_login_domains SET
                    is_enabled=$is_enabled,
                    primary_color='$primary_color',
                    logo_url='$logo_url',
                    company_name='$company_name'
                    WHERE projectID='$projectID'";

                if (query($sql)) {
                    $response->json([
                        'success' => true,
                        'message' => 'Custom Login aktualisiert',
                        'domain' => $domain
                    ]);
                } else {
                    $response->json(['error' => 'Fehler beim Aktualisieren']);
                }
            }
        } else {
            $cloudflareResult = $this->createCloudflareARecord($domain);

            if (!$cloudflareResult['success']) {
                $response->json(['error' => 'Cloudflare Fehler: ' . $cloudflareResult['message']]);
                return;
            }

            $cloudflare_record_id = $cloudflareResult['data']['id'] ?? null;
            $cloudflare_record_id_sql = $cloudflare_record_id ? "'$cloudflare_record_id'" : "NULL";

            $sql = "INSERT INTO custom_login_domains
                (projectID, domain, domain_type, is_enabled, primary_color, logo_url, company_name, cloudflare_record_id, ssl_status)
                VALUES ('$projectID', '$domain', '$domainType', $is_enabled, '$primary_color', '$logo_url', '$company_name', $cloudflare_record_id_sql, '$sslStatus')";

            if (query($sql)) {
                if ($isInternal) {
                    $this->triggerNginxWebhook($domain, 'add');
                }

                $result = [
                    'success' => true,
                    'message' => $isInternal ? 'Custom Login erstellt' : 'Custom Login erstellt - DNS muss manuell konfiguriert werden',
                    'domain' => $domain,
                    'is_internal' => $isInternal
                ];

                if (!$isInternal) {
                    $result['setup_instructions'] = [
                        'dns' => [
                            'type' => 'A',
                            'name' => $domain,
                            'value' => self::LOGIN_SERVER_IP
                        ]
                    ];
                }

                $response->json($result);
            } else {
                if ($cloudflare_record_id) {
                    $this->deleteCloudflareRecord($cloudflare_record_id);
                }
                $response->json(['error' => 'Fehler beim Erstellen']);
            }
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project', ''));

        $projectData = getProjectByLink($project);

        if (!$projectData) {
            $response->json(['error' => 'Projekt nicht gefunden']);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectData['projectID'])) {
            $response->json(['error' => 'Keine Berechtigung']);
            return;
        }

        $projectID = $projectData['projectID'];

        $existing = query("SELECT * FROM custom_login_domains WHERE projectID='$projectID'");
        $existingRow = fetch_assoc($existing);

        if ($existingRow) {
            $domainToDelete = $existingRow['domain'];
            $wasInternal = $this->isInternalDomain($domainToDelete);

            if ($wasInternal && $existingRow['cloudflare_record_id']) {
                $this->deleteCloudflareRecord($existingRow['cloudflare_record_id']);
            }

            if ($wasInternal) {
                $this->triggerNginxWebhook($domainToDelete, 'remove');
            }

            query("DELETE FROM custom_login_domains WHERE projectID='$projectID'");

            $response->json(['success' => true, 'message' => 'Custom Login Domain gelöscht']);
        } else {
            $response->json(['error' => 'Keine Custom Login Domain gefunden']);
        }
    }
}
