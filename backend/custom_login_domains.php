<?php
require_once "head.php";
require_once "helpers/project.php";
require_once __DIR__ . '/helpers/cloudflare.php';

function ensureCustomLoginDomainsTable() {
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

ensureCustomLoginDomainsTable();

define('LOGIN_SERVER_IP', '92.5.112.145');
define('INTERNAL_BASE_DOMAIN', 'control-center.eu');
define('NGINX_WEBHOOK_URL', 'https://webhook.control-center.eu/custom_login_webhook.php');
define('WEBHOOK_SECRET', 'cc_custom_login_webhook_secret_2025');


function isInternalDomain($domain) {
    return str_ends_with($domain, '.' . INTERNAL_BASE_DOMAIN);
}

function triggerNginxWebhook($domain, $action = 'add') {
    
    if (!isInternalDomain($domain)) {
        return ['skipped' => true, 'message' => 'External domain - manual setup required'];
    }
    
    $data = json_encode([
        'action' => $action,
        'domain' => $domain
    ]);
    
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nX-Webhook-Secret: " . WEBHOOK_SECRET . "\r\n",
            'content' => $data,
            'timeout' => 10
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = @file_get_contents(NGINX_WEBHOOK_URL, false, $context);
    
    if ($response) {
        $result = json_decode($response, true);
        error_log("Nginx webhook response for $domain ($action): " . $response);
        return $result;
    }
    
    error_log("Nginx webhook failed for $domain ($action)");
    return null;
}


function createCloudflareARecord($domain) {
    
    if (!isInternalDomain($domain)) {
        return ['success' => true, 'data' => ['id' => null], 'external' => true];
    }
    
    return cloudflare_createARecord($domain, LOGIN_SERVER_IP, true); 
}

function deleteCloudflareRecord($recordId) {
    return cloudflare_deleteRecord($recordId);
}


$action = $_POST['action'] ?? $_GET['action'] ?? '';


if ($action === 'getConfig' && isset($_GET['domain'])) {
    $domain = escape_string($_GET['domain']);
    
    $result = query("SELECT * FROM custom_login_domains WHERE domain='$domain' AND is_enabled=1");
    
    if ($row = fetch_assoc($result)) {
        
        $projectResult = query("SELECT name FROM projects WHERE projectID='{$row['projectID']}'");
        $project = fetch_assoc($projectResult);
        
        echo json_encode([
            'success' => true,
            'config' => [
                'domain' => $row['domain'],
                'primary_color' => $row['primary_color'],
                'logo_url' => $row['logo_url'],
                'company_name' => $row['company_name'] ?: ($project['name'] ?? 'Fringelo'),
                'project_name' => $project['name'] ?? 'Fringelo'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Domain nicht gefunden oder nicht aktiviert']);
    }
    exit;
}


if (!$userID) {
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}


if ($action === 'get' && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $projectData = getProjectByLink($project);
    
    if (!$projectData) {
        echo json_encode(['error' => 'Projekt nicht gefunden']);
        exit;
    }
    
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo json_encode(['error' => 'Keine Berechtigung']);
        exit;
    }
    
    $result = query("SELECT * FROM custom_login_domains WHERE projectID='{$projectData['projectID']}'");
    
    if ($row = fetch_assoc($result)) {
        $domainType = $row['domain_type'] ?? (isInternalDomain($row['domain']) ? 'internal' : 'external');
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
                    'value' => LOGIN_SERVER_IP,
                    'info' => 'Erstelle einen A-Record der auf ' . LOGIN_SERVER_IP . ' zeigt'
                ],
                'nginx' => 'Nginx muss manuell auf deinem Server konfiguriert werden',
                'ssl' => 'SSL muss manuell eingerichtet werden (z.B. via Certbot oder Cloudflare)'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $responseData
        ]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
    exit;
}


if ($action === 'save' && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $domain = escape_string($_POST['domain'] ?? '');
    $is_enabled = ($_POST['is_enabled'] ?? 'false') === 'true' ? 1 : 0;
    $primary_color = escape_string($_POST['primary_color'] ?? '#e53e3e');
    $logo_url = escape_string($_POST['logo_url'] ?? '');
    $company_name = escape_string($_POST['company_name'] ?? '');
    
    $projectData = getProjectByLink($project);
    
    if (!$projectData) {
        echo json_encode(['error' => 'Projekt nicht gefunden']);
        exit;
    }
    
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo json_encode(['error' => 'Keine Berechtigung']);
        exit;
    }
    
    $projectID = $projectData['projectID'];
    
    
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain ist erforderlich']);
        exit;
    }
    
    
    $isInternal = isInternalDomain($domain);
    $domainType = $isInternal ? 'internal' : 'external';
    $sslStatus = $isInternal ? 'pending' : 'manual';
    
    
    $existingDomain = query("SELECT * FROM custom_login_domains WHERE domain='$domain' AND projectID != '$projectID'");
    if (fetch_assoc($existingDomain)) {
        echo json_encode(['error' => 'Diese Domain wird bereits von einem anderen Projekt verwendet']);
        exit;
    }
    
    
    $existing = query("SELECT * FROM custom_login_domains WHERE projectID='$projectID'");
    $existingRow = fetch_assoc($existing);
    
    if ($existingRow) {
        
        if ($existingRow['domain'] !== $domain) {
            $oldDomain = $existingRow['domain'];
            $wasInternal = isInternalDomain($oldDomain);
            
            
            if ($wasInternal && $existingRow['cloudflare_record_id']) {
                deleteCloudflareRecord($existingRow['cloudflare_record_id']);
            }
            
            
            if ($wasInternal) {
                triggerNginxWebhook($oldDomain, 'remove');
            }
            
            
            $cloudflareResult = createCloudflareARecord($domain);
            
            if (!$cloudflareResult['success']) {
                echo json_encode(['error' => 'Cloudflare Fehler: ' . $cloudflareResult['message']]);
                exit;
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
                    triggerNginxWebhook($domain, 'add');
                }
                
                $response = [
                    'success' => true, 
                    'message' => $isInternal ? 'Custom Login aktualisiert' : 'Custom Login aktualisiert - DNS muss manuell konfiguriert werden',
                    'domain' => $domain,
                    'is_internal' => $isInternal
                ];
                
                
                if (!$isInternal) {
                    $response['setup_instructions'] = [
                        'dns' => [
                            'type' => 'A',
                            'name' => $domain,
                            'value' => LOGIN_SERVER_IP
                        ]
                    ];
                }
                
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Fehler beim Aktualisieren']);
            }
        } else {
            
            $sql = "UPDATE custom_login_domains SET 
                is_enabled=$is_enabled, 
                primary_color='$primary_color', 
                logo_url='$logo_url', 
                company_name='$company_name'
                WHERE projectID='$projectID'";
            
            if (query($sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Custom Login aktualisiert',
                    'domain' => $domain
                ]);
            } else {
                echo json_encode(['error' => 'Fehler beim Aktualisieren']);
            }
        }
    } else {
        
        
        $cloudflareResult = createCloudflareARecord($domain);
        
        if (!$cloudflareResult['success']) {
            echo json_encode(['error' => 'Cloudflare Fehler: ' . $cloudflareResult['message']]);
            exit;
        }
        
        $cloudflare_record_id = $cloudflareResult['data']['id'] ?? null;
        $cloudflare_record_id_sql = $cloudflare_record_id ? "'$cloudflare_record_id'" : "NULL";
        
        $sql = "INSERT INTO custom_login_domains 
            (projectID, domain, domain_type, is_enabled, primary_color, logo_url, company_name, cloudflare_record_id, ssl_status) 
            VALUES ('$projectID', '$domain', '$domainType', $is_enabled, '$primary_color', '$logo_url', '$company_name', $cloudflare_record_id_sql, '$sslStatus')";
        
        if (query($sql)) {
            
            if ($isInternal) {
                triggerNginxWebhook($domain, 'add');
            }
            
            $response = [
                'success' => true, 
                'message' => $isInternal ? 'Custom Login erstellt' : 'Custom Login erstellt - DNS muss manuell konfiguriert werden',
                'domain' => $domain,
                'is_internal' => $isInternal
            ];
            
            
            if (!$isInternal) {
                $response['setup_instructions'] = [
                    'dns' => [
                        'type' => 'A',
                        'name' => $domain,
                        'value' => LOGIN_SERVER_IP
                    ]
                ];
            }
            
            echo json_encode($response);
        } else {
            
            if ($cloudflare_record_id) {
                deleteCloudflareRecord($cloudflare_record_id);
            }
            echo json_encode(['error' => 'Fehler beim Erstellen']);
        }
    }
    exit;
}


if ($action === 'delete' && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    
    $projectData = getProjectByLink($project);
    
    if (!$projectData) {
        echo json_encode(['error' => 'Projekt nicht gefunden']);
        exit;
    }
    
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo json_encode(['error' => 'Keine Berechtigung']);
        exit;
    }
    
    $projectID = $projectData['projectID'];
    
    
    $existing = query("SELECT * FROM custom_login_domains WHERE projectID='$projectID'");
    $existingRow = fetch_assoc($existing);
    
    if ($existingRow) {
        $domainToDelete = $existingRow['domain'];
        $wasInternal = isInternalDomain($domainToDelete);
        
        
        if ($wasInternal && $existingRow['cloudflare_record_id']) {
            deleteCloudflareRecord($existingRow['cloudflare_record_id']);
        }
        
        
        if ($wasInternal) {
            triggerNginxWebhook($domainToDelete, 'remove');
        }
        
        
        query("DELETE FROM custom_login_domains WHERE projectID='$projectID'");
        
        echo json_encode(['success' => true, 'message' => 'Custom Login Domain gelöscht']);
    } else {
        echo json_encode(['error' => 'Keine Custom Login Domain gefunden']);
    }
    exit;
}


if ($action === 'updateSslStatus' && isset($_POST['domain']) && isset($_POST['status'])) {
    $domain = escape_string($_POST['domain']);
    $status = escape_string($_POST['status']);
    
    if (!in_array($status, ['pending', 'active', 'failed'])) {
        echo json_encode(['error' => 'Ungültiger Status']);
        exit;
    }
    
    $sql = "UPDATE custom_login_domains SET ssl_status='$status' WHERE domain='$domain'";
    
    if (query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Fehler beim Aktualisieren']);
    }
    exit;
}

echo json_encode(['error' => 'Ungültige Aktion']);
