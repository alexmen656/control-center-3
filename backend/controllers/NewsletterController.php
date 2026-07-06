<?php

class NewsletterController
{
    private function projectPrefix(Request $request): string
    {
        $project = $request->input('project', '');
        $project = preg_replace('/[^a-zA-Z0-9_-]/', '', $project);
        return $project ? "{$project}_" : "";
    }

    private function createNewsletterTables($con, string $prefix): void
    {
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

    public function send(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $subject = $request->input('subject', '');
        $emailContent = $request->input('email', '');
        $recipients = $request->input('recipients', '');
        $testMode = $request->input('test_mode') === true || $request->input('test_mode') === 'true';

        if (empty($subject) || empty($emailContent) || empty($recipients)) {
            $response->error('Bitte fülle alle Felder aus', 400);
            return;
        }

        $recipientList = preg_split('/[\n,]/', $recipients);
        $recipientList = array_map('trim', $recipientList);
        $recipientList = array_filter($recipientList, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (empty($recipientList)) {
            $response->error('Keine gültigen E-Mail-Adressen gefunden', 400);
            return;
        }

        $newsletterTable = $prefix . "newsletters";

        $stmt = $con->prepare("INSERT INTO `{$newsletterTable}` (subject, content, recipients, recipient_count, status, sent_at) VALUES (?, ?, ?, ?, 'sent', NOW())");
        $recipientsJson = json_encode($recipientList);
        $recipientCount = count($recipientList);
        $stmt->bind_param("sssi", $subject, $emailContent, $recipientsJson, $recipientCount);

        if ($stmt->execute()) {
            $newsletterId = $stmt->insert_id;

            $trackingTable = $prefix . "newsletter_tracking";
            $trackingStmt = $con->prepare("INSERT INTO `{$trackingTable}` (newsletter_id, recipient_email) VALUES (?, ?)");

            foreach ($recipientList as $email) {
                $trackingStmt->bind_param("is", $newsletterId, $email);
                $trackingStmt->execute();
            }

            $mailResult = sendBulkMail($recipientList, $subject, $emailContent);

            if ($mailResult['success']) {
                $response->json([
                    'success' => true,
                    'message' => $testMode ? 'Test-Newsletter gesendet' : "Newsletter erfolgreich an {$mailResult['sent']} Empfänger gesendet",
                    'newsletter_id' => $newsletterId,
                    'sent' => $mailResult['sent'],
                    'failed' => $mailResult['failed']
                ]);
            } else {
                $response->json([
                    'success' => true,
                    'message' => "Newsletter teilweise gesendet: {$mailResult['sent']} erfolgreich, {$mailResult['failed']} fehlgeschlagen",
                    'newsletter_id' => $newsletterId,
                    'sent' => $mailResult['sent'],
                    'failed' => $mailResult['failed'],
                    'errors' => $mailResult['errors']
                ]);
            }
        } else {
            $response->error('Fehler beim Speichern des Newsletters', 500);
        }
    }

    public function getStats(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $newsletterTable = $prefix . "newsletters";
        $trackingTable = $prefix . "newsletter_tracking";
        $subscribersTable = $prefix . "newsletter_subscribers";

        $result = $con->query("SELECT COUNT(*) as count FROM `{$newsletterTable}` WHERE status = 'sent'");
        $totalSent = $result ? $result->fetch_assoc()['count'] : 0;

        $result = $con->query("SELECT COUNT(*) as count FROM `{$subscribersTable}` WHERE status = 'active'");
        $totalSubscribers = $result ? $result->fetch_assoc()['count'] : 0;

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

        $response->json([
            'success' => true,
            'stats' => [
                'total_sent' => $totalSent,
                'total_subscribers' => $totalSubscribers,
                'open_rate' => $openRate,
                'click_rate' => $clickRate
            ]
        ]);
    }

    public function getRecent(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $newsletterTable = $prefix . "newsletters";
        $limit = intval($request->input('limit', 10));
        $offset = intval($request->input('offset', 0));

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

        $countResult = $con->query("SELECT COUNT(*) as count FROM `{$newsletterTable}`");
        $total = $countResult ? $countResult->fetch_assoc()['count'] : 0;

        $response->json([
            'success' => true,
            'newsletters' => $newsletters,
            'total' => $total
        ]);
    }

    public function getPerformance(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $newsletterTable = $prefix . "newsletters";
        $trackingTable = $prefix . "newsletter_tracking";
        $period = $request->input('period', '30d');

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

        $response->json([
            'success' => true,
            'performance' => $performance
        ]);
    }

    public function delete(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $id = intval($request->params['id'] ?? 0);

        if ($id <= 0) {
            $response->error('Ungültige ID', 400);
            return;
        }

        $newsletterTable = $prefix . "newsletters";

        $stmt = $con->prepare("DELETE FROM `{$newsletterTable}` WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $response->json(['success' => true, 'message' => 'Newsletter gelöscht']);
        } else {
            $response->error('Fehler beim Löschen', 500);
        }
    }

    public function getSettings(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $settingsTable = $prefix . "newsletter_settings";

        $result = $con->query("SELECT setting_key, setting_value FROM `{$settingsTable}` WHERE setting_key = 'general'");

        if ($result && $row = $result->fetch_assoc()) {
            $response->json([
                'success' => true,
                'settings' => json_decode($row['setting_value'], true)
            ]);
        } else {
            $response->json([
                'success' => true,
                'settings' => []
            ]);
        }
    }

    public function saveSettings(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $settings = $request->input('settings', '');

        if (empty($settings)) {
            $response->error('Keine Einstellungen angegeben', 400);
            return;
        }

        $settingsTable = $prefix . "newsletter_settings";

        $stmt = $con->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value) VALUES ('general', ?)
            ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $settings, $settings);

        if ($stmt->execute()) {
            $response->json(['success' => true, 'message' => 'Einstellungen gespeichert']);
        } else {
            $response->error('Fehler beim Speichern', 500);
        }
    }

    public function getSmtp(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $settingsTable = $prefix . "newsletter_settings";

        $result = $con->query("SELECT setting_key, setting_value FROM `{$settingsTable}` WHERE setting_key = 'smtp'");

        if ($result && $row = $result->fetch_assoc()) {
            $smtp = json_decode($row['setting_value'], true);
            if (isset($smtp['password'])) {
                $smtp['password'] = '••••••••';
            }
            $response->json([
                'success' => true,
                'smtp' => $smtp
            ]);
        } else {
            $response->json([
                'success' => true,
                'smtp' => []
            ]);
        }
    }

    public function saveSmtp(Request $request, Response $response): void
    {
        global $con;
        $prefix = $this->projectPrefix($request);
        $this->createNewsletterTables($con, $prefix);

        $smtp = $request->input('smtp', '');

        if (empty($smtp)) {
            $response->error('Keine SMTP Einstellungen angegeben', 400);
            return;
        }

        $settingsTable = $prefix . "newsletter_settings";

        $stmt = $con->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value) VALUES ('smtp', ?)
            ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $smtp, $smtp);

        if ($stmt->execute()) {
            $response->json(['success' => true, 'message' => 'SMTP Einstellungen gespeichert']);
        } else {
            $response->error('Fehler beim Speichern', 500);
        }
    }

    public function testSmtp(Request $request, Response $response): void
    {
        $smtp = json_decode($request->input('smtp', '{}'), true);

        if (empty($smtp['host']) || empty($smtp['port'])) {
            $response->error('SMTP Host und Port erforderlich', 400);
            return;
        }

        $response->json([
            'success' => true,
            'message' => 'Verbindung erfolgreich (Simulation)'
        ]);
    }
}
