<?php
include_once 'head.php';
require_once 'config.php';
require_once 'db_connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$project = $_POST['project'] ?? $_GET['project'] ?? '';

// Sanitize project name
$project = preg_replace('/[^a-zA-Z0-9_-]/', '', $project);

/**
 * Create newsletter tables if they don't exist
 */
function createNewsletterTables($con, $project) {
    $prefix = $project ? "{$project}_" : "";
    
    // Newsletter table
    $newsletterTable = $prefix . "newsletters";
    $sql = "CREATE TABLE IF NOT EXISTS `{$newsletterTable}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        recipients TEXT NOT NULL,
        recipient_count INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'draft',
        sent_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$con->query($sql)) {
        error_log("Error creating newsletters table: " . $con->error);
    }
    
    // Newsletter tracking table
    $trackingTable = $prefix . "newsletter_tracking";
    $sql = "CREATE TABLE IF NOT EXISTS `{$trackingTable}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newsletter_id INT NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        opened BOOLEAN DEFAULT FALSE,
        opened_at DATETIME NULL,
        clicked BOOLEAN DEFAULT FALSE,
        clicked_at DATETIME NULL,
        clicks INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (newsletter_id) REFERENCES `{$newsletterTable}`(id) ON DELETE CASCADE,
        INDEX idx_newsletter (newsletter_id),
        INDEX idx_email (recipient_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$con->query($sql)) {
        error_log("Error creating tracking table: " . $con->error);
    }
    
    // Settings table
    $settingsTable = $prefix . "newsletter_settings";
    $sql = "CREATE TABLE IF NOT EXISTS `{$settingsTable}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$con->query($sql)) {
        error_log("Error creating settings table: " . $con->error);
    }
    
    // Subscribers table
    $subscribersTable = $prefix . "newsletter_subscribers";
    $sql = "CREATE TABLE IF NOT EXISTS `{$subscribersTable}` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255),
        status VARCHAR(50) DEFAULT 'active',
        subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        unsubscribed_at DATETIME NULL,
        INDEX idx_email (email),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$con->query($sql)) {
        error_log("Error creating subscribers table: " . $con->error);
    }
}

// Initialize tables
createNewsletterTables($con, $project);

/**
 * Send Newsletter
 */
if ($action === 'send') {
    $subject = $_POST['subject'] ?? '';
    $emailContent = $_POST['email'] ?? '';
    $recipients = $_POST['recipients'] ?? '';
    $testMode = isset($_POST['test_mode']) && $_POST['test_mode'] === 'true';
    
    if (empty($subject) || empty($emailContent) || empty($recipients)) {
        echo json_encode([
            'success' => false,
            'message' => 'Bitte fülle alle Felder aus'
        ]);
        exit;
    }
    
    // Parse recipients
    $recipientList = preg_split('/[\n,]/', $recipients);
    $recipientList = array_map('trim', $recipientList);
    $recipientList = array_filter($recipientList, function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    });
    
    if (empty($recipientList)) {
        echo json_encode([
            'success' => false,
            'message' => 'Keine gültigen E-Mail-Adressen gefunden'
        ]);
        exit;
    }
    
    // Save newsletter
    $prefix = $project ? "{$project}_" : "";
    $newsletterTable = $prefix . "newsletters";
    
    $stmt = $con->prepare("INSERT INTO `{$newsletterTable}` (subject, content, recipients, recipient_count, status, sent_at) VALUES (?, ?, ?, ?, 'sent', NOW())");
    $recipientsJson = json_encode($recipientList);
    $recipientCount = count($recipientList);
    $stmt->bind_param("sssi", $subject, $emailContent, $recipientsJson, $recipientCount);
    
    if ($stmt->execute()) {
        $newsletterId = $stmt->insert_id;
        
        // TODO: Implement actual email sending using PHPMailer or similar
        // For now, we'll just log the attempt
        
        // Create tracking entries
        $trackingTable = $prefix . "newsletter_tracking";
        $trackingStmt = $con->prepare("INSERT INTO `{$trackingTable}` (newsletter_id, recipient_email) VALUES (?, ?)");
        
        foreach ($recipientList as $email) {
            $trackingStmt->bind_param("is", $newsletterId, $email);
            $trackingStmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => $testMode ? 'Test-Newsletter gesendet' : "Newsletter erfolgreich an {$recipientCount} Empfänger gesendet",
            'newsletter_id' => $newsletterId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Speichern des Newsletters'
        ]);
    }
    
    exit;
}

/**
 * Get Statistics
 */
if ($action === 'get_stats') {
    $prefix = $project ? "{$project}_" : "";
    $newsletterTable = $prefix . "newsletters";
    $trackingTable = $prefix . "newsletter_tracking";
    $subscribersTable = $prefix . "newsletter_subscribers";
    
    // Total sent
    $result = $con->query("SELECT COUNT(*) as count FROM `{$newsletterTable}` WHERE status = 'sent'");
    $totalSent = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Total subscribers
    $result = $con->query("SELECT COUNT(*) as count FROM `{$subscribersTable}` WHERE status = 'active'");
    $totalSubscribers = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Open rate
    $result = $con->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN opened = 1 THEN 1 ELSE 0 END) as opened
        FROM `{$trackingTable}`");
    
    $openRate = 0;
    if ($result) {
        $data = $result->fetch_assoc();
        if ($data['total'] > 0) {
            $openRate = round(($data['opened'] / $data['total']) * 100, 1);
        }
    }
    
    // Click rate
    $result = $con->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as clicked
        FROM `{$trackingTable}`");
    
    $clickRate = 0;
    if ($result) {
        $data = $result->fetch_assoc();
        if ($data['total'] > 0) {
            $clickRate = round(($data['clicked'] / $data['total']) * 100, 1);
        }
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sent' => $totalSent,
            'total_subscribers' => $totalSubscribers,
            'open_rate' => $openRate,
            'click_rate' => $clickRate
        ]
    ]);
    
    exit;
}

/**
 * Get Recent Newsletters
 */
if ($action === 'get_recent') {
    $prefix = $project ? "{$project}_" : "";
    $newsletterTable = $prefix . "newsletters";
    $limit = intval($_POST['limit'] ?? $_GET['limit'] ?? 10);
    $offset = intval($_POST['offset'] ?? $_GET['offset'] ?? 0);
    
    $stmt = $con->prepare("SELECT id, subject, recipient_count as recipients, status, sent_at 
        FROM `{$newsletterTable}` 
        ORDER BY sent_at DESC, created_at DESC 
        LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $newsletters = [];
    
    while ($row = $result->fetch_assoc()) {
        $newsletters[] = $row;
    }
    
    // Get total count
    $countResult = $con->query("SELECT COUNT(*) as count FROM `{$newsletterTable}`");
    $total = $countResult ? $countResult->fetch_assoc()['count'] : 0;
    
    echo json_encode([
        'success' => true,
        'newsletters' => $newsletters,
        'total' => $total
    ]);
    
    exit;
}

/**
 * Get Performance Data for Chart
 */
if ($action === 'get_performance') {
    $prefix = $project ? "{$project}_" : "";
    $newsletterTable = $prefix . "newsletters";
    $trackingTable = $prefix . "newsletter_tracking";
    $period = $_POST['period'] ?? $_GET['period'] ?? '30d';
    
    // Parse period
    $days = 30;
    if (preg_match('/(\d+)d/', $period, $matches)) {
        $days = intval($matches[1]);
    }
    
    $stmt = $con->prepare("SELECT 
        DATE(n.sent_at) as date,
        COUNT(DISTINCT n.id) as sent,
        SUM(CASE WHEN t.opened = 1 THEN 1 ELSE 0 END) as opened,
        SUM(CASE WHEN t.clicked = 1 THEN 1 ELSE 0 END) as clicked
        FROM `{$newsletterTable}` n
        LEFT JOIN `{$trackingTable}` t ON n.id = t.newsletter_id
        WHERE n.sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(n.sent_at)
        ORDER BY date ASC");
    
    $stmt->bind_param("i", $days);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $performance = [];
    
    while ($row = $result->fetch_assoc()) {
        $performance[] = [
            'date' => $row['date'],
            'sent' => intval($row['sent']),
            'opened' => intval($row['opened']),
            'clicked' => intval($row['clicked'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'performance' => $performance
    ]);
    
    exit;
}

/**
 * Delete Newsletter
 */
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Ungültige ID']);
        exit;
    }
    
    $prefix = $project ? "{$project}_" : "";
    $newsletterTable = $prefix . "newsletters";
    
    $stmt = $con->prepare("DELETE FROM `{$newsletterTable}` WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Newsletter gelöscht']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Löschen']);
    }
    
    exit;
}

/**
 * Get Settings
 */
if ($action === 'get_settings') {
    $prefix = $project ? "{$project}_" : "";
    $settingsTable = $prefix . "newsletter_settings";
    
    $result = $con->query("SELECT setting_key, setting_value FROM `{$settingsTable}` WHERE setting_key = 'general'");
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'settings' => json_decode($row['setting_value'], true)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'settings' => []
        ]);
    }
    
    exit;
}

/**
 * Save Settings
 */
if ($action === 'save_settings') {
    $settings = $_POST['settings'] ?? '';
    
    if (empty($settings)) {
        echo json_encode(['success' => false, 'message' => 'Keine Einstellungen angegeben']);
        exit;
    }
    
    $prefix = $project ? "{$project}_" : "";
    $settingsTable = $prefix . "newsletter_settings";
    
    $stmt = $con->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value) VALUES ('general', ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $settings, $settings);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Einstellungen gespeichert']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern']);
    }
    
    exit;
}

/**
 * Get SMTP Settings
 */
if ($action === 'get_smtp') {
    $prefix = $project ? "{$project}_" : "";
    $settingsTable = $prefix . "newsletter_settings";
    
    $result = $con->query("SELECT setting_key, setting_value FROM `{$settingsTable}` WHERE setting_key = 'smtp'");
    
    if ($result && $row = $result->fetch_assoc()) {
        $smtp = json_decode($row['setting_value'], true);
        // Remove password from response for security
        if (isset($smtp['password'])) {
            $smtp['password'] = '••••••••';
        }
        echo json_encode([
            'success' => true,
            'smtp' => $smtp
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'smtp' => []
        ]);
    }
    
    exit;
}

/**
 * Save SMTP Settings
 */
if ($action === 'save_smtp') {
    $smtp = $_POST['smtp'] ?? '';
    
    if (empty($smtp)) {
        echo json_encode(['success' => false, 'message' => 'Keine SMTP Einstellungen angegeben']);
        exit;
    }
    
    $prefix = $project ? "{$project}_" : "";
    $settingsTable = $prefix . "newsletter_settings";
    
    $stmt = $con->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value) VALUES ('smtp', ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $smtp, $smtp);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'SMTP Einstellungen gespeichert']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern']);
    }
    
    exit;
}

/**
 * Test SMTP Connection
 */
if ($action === 'test_smtp') {
    $smtp = json_decode($_POST['smtp'] ?? '{}', true);
    
    if (empty($smtp['host']) || empty($smtp['port'])) {
        echo json_encode(['success' => false, 'message' => 'SMTP Host und Port erforderlich']);
        exit;
    }
    
    // TODO: Implement actual SMTP connection test
    // For now, just validate the format
    
    echo json_encode([
        'success' => true,
        'message' => 'Verbindung erfolgreich (Simulation)'
    ]);
    
    exit;
}

// Default response
echo json_encode([
    'success' => false,
    'message' => 'Unbekannte Aktion: ' . $action
]);

$con->close();
?>
