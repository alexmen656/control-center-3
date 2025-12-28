<?php
include "head.php";
include "project_helper.php";
require_once __DIR__ . '/helpers/cloudflare.php';

function ensureWebBuilderDomainsTable()
{
    $sql = "CREATE TABLE IF NOT EXISTS web_builder_domains (
        id INT AUTO_INCREMENT PRIMARY KEY,
        projectID VARCHAR(255) NOT NULL,
        domain VARCHAR(255) NOT NULL UNIQUE,
        subdomain VARCHAR(100) NOT NULL,
        main_domain VARCHAR(255) NOT NULL,
        is_enabled BOOLEAN DEFAULT TRUE,
        cloudflare_record_id VARCHAR(100) DEFAULT NULL,
        ssl_status ENUM('pending', 'active', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_domain (domain),
        INDEX idx_projectID (projectID)
    )";
    query($sql);
}

ensureWebBuilderDomainsTable();
define('WEB_BUILDER_SERVER_IP', '92.5.112.145');
define('NGINX_WEBHOOK_URL', 'https://webhook.control-center.eu/web_builder_webhook.php');
define('WEBHOOK_SECRET', 'cc_web_builder_webhook_secret_2025');

/**
 * Erstellt einen A-Record für Web Builder Domain
 * Nutzt den zentralen Cloudflare Helper
 */
function createCloudflareARecord($domain)
{
    error_log("[WebBuilder] createCloudflareARecord aufgerufen für: $domain");
    $result = cloudflare_createARecord($domain, WEB_BUILDER_SERVER_IP, false);
    error_log("[WebBuilder] Cloudflare Result: " . json_encode($result));
    return $result;
}

/**
 * Löscht einen Cloudflare DNS Record
 * Nutzt den zentralen Cloudflare Helper
 */
function deleteCloudflareRecord($recordId)
{
    return cloudflare_deleteRecord($recordId);
}

/**
 * Erstellt einen Nginx Webhook für automatisches SSL Setup
 */
function triggerNginxSetup($domain, $projectID)
{
    $webhookData = [
        'domain' => $domain,
        'project' => $projectID,
        'type' => 'web_builder',
        'secret' => WEBHOOK_SECRET,
        'timestamp' => time()
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($webhookData),
            'timeout' => 5
        ]
    ];

    $context = stream_context_create($options);
    @file_get_contents(NGINX_WEBHOOK_URL, false, $context);
}

/**
 * GET - Abrufen der Subdomain eines Projekts
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get') {
    $project = escape_string($_POST['project'] ?? '');

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Projekt fehlt']);
        exit;
    }

    $result = query("SELECT * FROM web_builder_domains WHERE projectID='$project' LIMIT 1");

    if ($row = fetch_assoc($result)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $row['id'],
                'domain' => $row['domain'],
                'subdomain' => $row['subdomain'],
                'main_domain' => $row['main_domain'],
                'is_enabled' => (bool) $row['is_enabled'],
                'ssl_status' => $row['ssl_status'],
                'created_at' => $row['created_at']
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'data' => null]);
    }
    exit;
}

/**
 * SAVE - Speichern einer neuen Subdomain
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $project = escape_string($_POST['project'] ?? '');
    $subdomain = strtolower(trim(escape_string($_POST['subdomain'] ?? '')));
    $main_domain = escape_string($_POST['main_domain'] ?? '');
    $is_enabled = isset($_POST['is_enabled']) && $_POST['is_enabled'] === 'true';

    if (!$project || !$subdomain || !$main_domain) {
        echo json_encode(['success' => false, 'error' => 'Projekt, Subdomain und Main Domain sind erforderlich']);
        exit;
    }

    // Subdomain validieren
    if (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
        echo json_encode(['success' => false, 'error' => 'Subdomain darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten']);
        exit;
    }

    if (strlen($subdomain) < 3) {
        echo json_encode(['success' => false, 'error' => 'Subdomain muss mindestens 3 Zeichen lang sein']);
        exit;
    }

    // Vollständige Domain zusammenstellen: subdomain.main_domain
    $fullDomain = $subdomain . '.' . $main_domain;

    // Prüfen ob Subdomain bereits existiert
    $checkExisting = query("SELECT id, projectID FROM web_builder_domains WHERE subdomain='$subdomain'");
    if ($existingRow = fetch_assoc($checkExisting)) {
        if ($existingRow['projectID'] !== $project) {
            echo json_encode(['success' => false, 'error' => 'Diese Subdomain ist bereits vergeben']);
            exit;
        }
    }

    // Prüfen ob für dieses Projekt bereits eine Domain existiert
    $checkProject = query("SELECT id, cloudflare_record_id FROM web_builder_domains WHERE projectID='$project' LIMIT 1");

    if ($existingProject = fetch_assoc($checkProject)) {
        // Update existierende Domain

        // Alten Cloudflare Record löschen falls vorhanden
        if (!empty($existingProject['cloudflare_record_id'])) {
            deleteCloudflareRecord($existingProject['cloudflare_record_id']);
        }

        // Neuen Cloudflare A-Record erstellen
        $cloudflareResult = createCloudflareARecord($fullDomain);
        $cloudflareRecordId = $cloudflareResult['success'] && isset($cloudflareResult['data']['id'])
            ? $cloudflareResult['data']['id']
            : null;

        if (!$cloudflareResult['success']) {
            echo json_encode([
                'success' => false,
                'error' => 'Cloudflare DNS-Eintrag konnte nicht erstellt werden: ' . ($cloudflareResult['message'] ?? 'Unbekannter Fehler')
            ]);
            exit;
        }

        $updateSql = "UPDATE web_builder_domains 
                      SET subdomain='$subdomain', 
                          domain='$fullDomain',
                          main_domain='$main_domain',
                          is_enabled=" . ($is_enabled ? '1' : '0') . ",
                          cloudflare_record_id=" . ($cloudflareRecordId ? "'$cloudflareRecordId'" : "NULL") . ",
                          ssl_status='pending',
                          updated_at=NOW() 
                      WHERE projectID='$project'";

        if (query($updateSql)) {
            // Nginx Webhook triggern für automatisches SSL Setup
            triggerNginxSetup($fullDomain, $project);

            echo json_encode([
                'success' => true,
                'message' => 'Subdomain und DNS erfolgreich konfiguriert. SSL-Zertifikat wird automatisch erstellt.',
                'domain' => $fullDomain,
                'cloudflare' => $cloudflareResult
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Aktualisieren']);
        }
    } else {
        $cloudflareResult = createCloudflareARecord($fullDomain);
        $cloudflareRecordId = $cloudflareResult['success'] && isset($cloudflareResult['data']['id'])
            ? $cloudflareResult['data']['id']
            : null;

        if (!$cloudflareResult['success']) {
            echo json_encode([
                'success' => false,
                'error' => 'Cloudflare DNS-Eintrag konnte nicht erstellt werden: ' . ($cloudflareResult['message'] ?? 'Unbekannter Fehler')
            ]);
            exit;
        }

        $insertSql = "INSERT INTO web_builder_domains (projectID, subdomain, domain, main_domain, is_enabled, cloudflare_record_id, ssl_status) 
                      VALUES ('$project', '$subdomain', '$fullDomain', '$main_domain', " . ($is_enabled ? '1' : '0') . ", " . ($cloudflareRecordId ? "'$cloudflareRecordId'" : "NULL") . ", 'pending')";

        if (query($insertSql)) {
            triggerNginxSetup($fullDomain, $project);

            echo json_encode([
                'success' => true,
                'message' => 'Subdomain und DNS erfolgreich konfiguriert. SSL-Zertifikat wird automatisch erstellt.',
                'domain' => $fullDomain,
                'cloudflare' => $cloudflareResult
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Erstellen']);
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $project = escape_string($_POST['project'] ?? '');

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Projekt fehlt']);
        exit;
    }

    $getDomainSql = "SELECT cloudflare_record_id FROM web_builder_domains WHERE projectID='$project' LIMIT 1";
    $domainResult = query($getDomainSql);

    if ($domainRow = fetch_assoc($domainResult)) {
        if (!empty($domainRow['cloudflare_record_id'])) {
            deleteCloudflareRecord($domainRow['cloudflare_record_id']);
        }
    }

    $deleteSql = "DELETE FROM web_builder_domains WHERE projectID='$project'";

    if (query($deleteSql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Subdomain und DNS erfolgreich gelöscht'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehler beim Löschen']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'list') {
    $result = query("SELECT * FROM web_builder_domains ORDER BY created_at DESC");

    $domains = [];
    while ($row = fetch_assoc($result)) {
        $domains[] = [
            'id' => $row['id'],
            'project' => $row['projectID'],
            'domain' => $row['domain'],
            'subdomain' => $row['subdomain'],
            'main_domain' => $row['main_domain'],
            'is_enabled' => (bool) $row['is_enabled'],
            'ssl_status' => $row['ssl_status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode(['success' => true, 'domains' => $domains]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ssl_status') {
    $project = escape_string($_POST['project'] ?? '');
    $domain = escape_string($_POST['domain'] ?? '');
    $ssl_status = escape_string($_POST['ssl_status'] ?? '');

    if (!$project || !$domain || !$ssl_status) {
        echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
        exit;
    }

    // Validiere SSL Status
    $validStatuses = ['pending', 'active', 'failed'];
    if (!in_array($ssl_status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Ungültiger SSL Status']);
        exit;
    }

    $updateSql = "UPDATE web_builder_domains 
                  SET ssl_status='$ssl_status', 
                      updated_at=NOW() 
                  WHERE projectID='$project' AND domain='$domain'";

    if (query($updateSql)) {
        echo json_encode([
            'success' => true,
            'message' => 'SSL Status aktualisiert'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehler beim Aktualisieren']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage']);
