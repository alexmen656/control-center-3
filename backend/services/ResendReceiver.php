<?php
namespace ControlCenter;

class ResendReceiver
{
    private $con;
    private $resend;
    private ?string $webhookSecret;

    public function __construct($dbConnection = null)
    {
        if ($dbConnection) {
            $this->con = $dbConnection;
        } else {
            global $con;
            $this->con = $con;
        }

        $this->loadCredentials();
        $this->ensureTablesExist();
    }

    private function loadCredentials(): void
    {
        $apiKey = getenv('RESEND_API_KEY') ?: '';
        $this->webhookSecret = getenv('RESEND_WEBHOOK_SECRET') ?: null;

        if (empty($apiKey)) {
            $credsPath = __DIR__ . '/../creds.php';
            if (file_exists($credsPath)) {
                include $credsPath;
                $apiKey = $resend_api_key ?? '';
                $this->webhookSecret = $resend_webhook_secret ?? null;
            }
        }

        if ($apiKey) {
            try {
                static $autoloaderLoaded = false;
                if (!$autoloaderLoaded) {
                    $autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php');
                    if ($autoloadPath && file_exists($autoloadPath)) {
                        require_once $autoloadPath;
                        $autoloaderLoaded = true;
                    }
                }

                if (class_exists('\\Resend')) {
                    $this->resend = \Resend::client($apiKey);
                } elseif (class_exists('\\Resend\\Client')) {
                    $this->resend = \Resend::client($apiKey);
                } elseif (class_exists('\\Resend\\Resend')) {
                    $this->resend = new \Resend\Resend($apiKey);
                }
            } catch (\Throwable $e) {
                error_log("Resend SDK initialization failed: " . $e->getMessage());
            }
        }
    }

    private function ensureTablesExist(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `incoming_emails` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` VARCHAR(255) UNIQUE,
            `resend_email_id` VARCHAR(255),
            `from_email` VARCHAR(255) NOT NULL,
            `from_name` VARCHAR(255),
            `to_email` VARCHAR(500) NOT NULL,
            `cc` TEXT,
            `bcc` TEXT,
            `reply_to` TEXT,
            `subject` VARCHAR(500),
            `body_text` LONGTEXT,
            `body_html` LONGTEXT,
            `headers` JSON,
            `raw_email_url` VARCHAR(1000),
            `raw_email_expires` DATETIME,
            `is_read` TINYINT(1) DEFAULT 0,
            `is_starred` TINYINT(1) DEFAULT 0,
            `is_archived` TINYINT(1) DEFAULT 0,
            `is_deleted` TINYINT(1) DEFAULT 0,
            `folder` VARCHAR(50) DEFAULT 'inbox',
            `labels` JSON,
            `received_at` DATETIME,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_from_email` (`from_email`),
            INDEX `idx_to_email` (`to_email`(255)),
            INDEX `idx_folder` (`folder`),
            INDEX `idx_is_read` (`is_read`),
            INDEX `idx_received_at` (`received_at`),
            INDEX `idx_resend_email_id` (`resend_email_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        mysqli_query($this->con, $sql);

        $sqlAttachments = "CREATE TABLE IF NOT EXISTS `email_attachments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email_id` INT NOT NULL,
            `resend_attachment_id` VARCHAR(255),
            `filename` VARCHAR(255) NOT NULL,
            `content_type` VARCHAR(100),
            `content_disposition` VARCHAR(50),
            `content_id` VARCHAR(255),
            `size` INT,
            `content` LONGBLOB,
            `download_url` VARCHAR(1000),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`email_id`) REFERENCES `incoming_emails`(`id`) ON DELETE CASCADE,
            INDEX `idx_email_id` (`email_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        mysqli_query($this->con, $sqlAttachments);
    }

    public function processWebhook(string $rawPayload, array $headers = []): array
    {
        try {
            $svixHeaders = [
                'svix-id' => $headers['svix-id'] ?? $headers['Svix-Id'] ?? null,
                'svix-timestamp' => $headers['svix-timestamp'] ?? $headers['Svix-Timestamp'] ?? null,
                'svix-signature' => $headers['svix-signature'] ?? $headers['Svix-Signature'] ?? null,
            ];

            $verified = $this->resend->webhooks->verify(
                $rawPayload,
                $svixHeaders,
                $this->webhookSecret
            );

            if (!$verified) {
                return ['success' => false, 'error' => 'Invalid webhook signature'];
            }

            $payload = json_decode($rawPayload, true);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Verification failed: ' . $e->getMessage()];
        }

        if (!$payload) {
            return ['success' => false, 'error' => 'Invalid JSON payload'];
        }

        $eventType = $payload['type'] ?? null;

        switch ($eventType) {
            case 'email.received':
                return $this->processReceivedEmail($payload);

            case 'email.bounced':
                return $this->processBounce($payload);

            case 'email.complained':
                return $this->processComplaint($payload);

            case 'email.delivered':
            case 'email.sent':
            case 'email.opened':
            case 'email.clicked':
                return ['success' => true, 'type' => $eventType];

            default:
                return ['success' => false, 'error' => 'Unknown event type: ' . $eventType];
        }
    }

    private function processReceivedEmail(array $payload): array
    {
        try {
            $data = $payload['data'] ?? [];
            $emailId = $data['email_id'] ?? uniqid('resend_');
            $messageId = $data['message_id'] ?? $emailId;
            $from = $data['from'] ?? '';
            $to = $data['to'] ?? [];
            $cc = $data['cc'] ?? [];
            $bcc = $data['bcc'] ?? [];
            $subject = $data['subject'] ?? '(No Subject)';
            $createdAt = $data['created_at'] ?? date('c');
            $attachments = $data['attachments'] ?? [];
            $fromParsed = $this->parseEmailAddress($from);
            $fullEmail = $this->fetchReceivedEmail($emailId);
            $bodyHtml = $fullEmail['html'] ?? '';
            $bodyText = $fullEmail['text'] ?? '';
            $headers = $fullEmail['headers'] ?? [];
            $rawEmailUrl = $fullEmail['raw']['download_url'] ?? null;
            $rawEmailExpires = $fullEmail['raw']['expires_at'] ?? null;
            $replyTo = $fullEmail['reply_to'] ?? [];

            $sql = "INSERT INTO `incoming_emails` 
                (message_id, resend_email_id, from_email, from_name, to_email, cc, bcc, reply_to,
                subject, body_text, body_html, headers, raw_email_url, raw_email_expires, received_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    body_text = VALUES(body_text),
                    body_html = VALUES(body_html),
                    headers = VALUES(headers),
                    updated_at = CURRENT_TIMESTAMP";

            $stmt = mysqli_prepare($this->con, $sql);

            $toStr = is_array($to) ? implode(', ', $to) : $to;
            $ccStr = is_array($cc) ? implode(', ', $cc) : ($cc ?: null);
            $bccStr = is_array($bcc) ? implode(', ', $bcc) : ($bcc ?: null);
            $replyToStr = is_array($replyTo) ? implode(', ', $replyTo) : ($replyTo ?: null);
            $headersJson = json_encode($headers);
            $receivedAt = date('Y-m-d H:i:s', strtotime($createdAt));
            $rawEmailExpiresFormatted = $rawEmailExpires ? date('Y-m-d H:i:s', strtotime($rawEmailExpires)) : null;

            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssssssss',
                $messageId,
                $emailId,
                $fromParsed['email'],
                $fromParsed['name'],
                $toStr,
                $ccStr,
                $bccStr,
                $replyToStr,
                $subject,
                $bodyText,
                $bodyHtml,
                $headersJson,
                $rawEmailUrl,
                $rawEmailExpiresFormatted,
                $receivedAt
            );

            $result = mysqli_stmt_execute($stmt);

            if (!$result) {
                throw new \Exception(mysqli_error($this->con));
            }

            $dbEmailId = mysqli_insert_id($this->con);
            if (!$dbEmailId) {
                $idResult = mysqli_query(
                    $this->con,
                    "SELECT id FROM incoming_emails WHERE message_id = '" .
                    mysqli_real_escape_string($this->con, $messageId) . "'"
                );
                $row = mysqli_fetch_assoc($idResult);
                $dbEmailId = $row['id'];
            }

            foreach ($attachments as $attachment) {
                $this->saveAttachment($dbEmailId, $attachment);
            }

            return [
                'success' => true,
                'email_id' => $dbEmailId,
                'resend_email_id' => $emailId,
                'message_id' => $messageId,
            ];

        } catch (\Exception $e) {
            error_log("Error processing Resend email: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function fetchReceivedEmail(string $emailId): array
    {
        try {
            return $this->resend->emails->get($emailId);
        } catch (\Exception $e) {
            error_log("Error fetching received email: " . $e->getMessage());
            return [];
        }
    }

    public function fetchAttachment(string $emailId, string $attachmentId): ?array
    {
        try {
            return $this->resend->emails->getAttachment($emailId, $attachmentId);
        } catch (\Exception $e) {
            error_log("Error fetching attachment: " . $e->getMessage());
            return null;
        }
    }

    private function saveAttachment(int $emailId, array $attachment): void
    {
        $sql = "INSERT INTO `email_attachments` 
            (email_id, resend_attachment_id, filename, content_type, content_disposition, content_id)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->con, $sql);

        $resendId = $attachment['id'] ?? null;
        $filename = $attachment['filename'] ?? 'attachment';
        $contentType = $attachment['content_type'] ?? 'application/octet-stream';
        $disposition = $attachment['content_disposition'] ?? 'attachment';
        $contentId = $attachment['content_id'] ?? null;

        mysqli_stmt_bind_param(
            $stmt,
            'isssss',
            $emailId,
            $resendId,
            $filename,
            $contentType,
            $disposition,
            $contentId
        );
        mysqli_stmt_execute($stmt);
    }

    private function parseEmailAddress(string $address): array
    {
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $address, $matches)) {
            return [
                'name' => trim($matches[1], '"\''),
                'email' => $matches[2],
            ];
        }
        return [
            'name' => '',
            'email' => $address,
        ];
    }

    private function processBounce(array $payload): array
    {
        error_log("Resend Bounce received: " . json_encode($payload));
        return ['success' => true, 'type' => 'bounce'];
    }

    private function processComplaint(array $payload): array
    {
        error_log("Resend Complaint received: " . json_encode($payload));
        return ['success' => true, 'type' => 'complaint'];
    }

    public function getEmails(array $options = []): array
    {
        $folder = $options['folder'] ?? 'inbox';
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        $includeDeleted = $options['include_deleted'] ?? false;
        $search = $options['search'] ?? null;

        $where = ["folder = '" . mysqli_real_escape_string($this->con, $folder) . "'"];

        if (!$includeDeleted) {
            $where[] = "is_deleted = 0";
        }

        if ($search) {
            $searchEsc = mysqli_real_escape_string($this->con, $search);
            $where[] = "(subject LIKE '%$searchEsc%' OR from_email LIKE '%$searchEsc%' OR from_name LIKE '%$searchEsc%' OR body_text LIKE '%$searchEsc%')";
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                id, message_id, resend_email_id, from_email, from_name, to_email, subject,
                LEFT(body_text, 200) as preview,
                is_read, is_starred, is_archived, folder, labels,
                received_at, created_at
            FROM incoming_emails
            WHERE $whereClause
            ORDER BY received_at DESC
            LIMIT $limit OFFSET $offset";

        $result = mysqli_query($this->con, $sql);
        $emails = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $row['labels'] = json_decode($row['labels'], true) ?? [];
            $row['has_attachments'] = $this->hasAttachments($row['id']);
            $emails[] = $row;
        }

        $countSql = "SELECT COUNT(*) as total FROM incoming_emails WHERE $whereClause";
        $countResult = mysqli_query($this->con, $countSql);
        $total = mysqli_fetch_assoc($countResult)['total'];

        return [
            'emails' => $emails,
            'total' => (int) $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function getEmail(int $id): ?array
    {
        $sql = "SELECT * FROM incoming_emails WHERE id = $id";
        $result = mysqli_query($this->con, $sql);
        $email = mysqli_fetch_assoc($result);

        if (!$email) {
            return null;
        }

        $email['headers'] = json_decode($email['headers'], true) ?? [];
        $email['labels'] = json_decode($email['labels'], true) ?? [];
        $email['attachments'] = $this->getAttachments($id);
        return $email;
    }

    private function hasAttachments(int $emailId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM email_attachments WHERE email_id = $emailId";
        $result = mysqli_query($this->con, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    public function getAttachments(int $emailId): array
    {
        $sql = "SELECT id, resend_attachment_id, filename, content_type, content_id, size, created_at 
                FROM email_attachments WHERE email_id = $emailId";
        $result = mysqli_query($this->con, $sql);
        $attachments = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $attachments[] = $row;
        }

        return $attachments;
    }

    public function getAttachmentContent(int $attachmentId): ?array
    {
        $sql = "SELECT ea.*, ie.resend_email_id 
                FROM email_attachments ea 
                JOIN incoming_emails ie ON ea.email_id = ie.id 
                WHERE ea.id = $attachmentId";
        $result = mysqli_query($this->con, $sql);
        $attachment = mysqli_fetch_assoc($result);

        if (!$attachment) {
            return null;
        }

        if (empty($attachment['content']) && $attachment['resend_attachment_id'] && $attachment['resend_email_id']) {
            $resendData = $this->fetchAttachment(
                $attachment['resend_email_id'],
                $attachment['resend_attachment_id']
            );

            if ($resendData && isset($resendData['content'])) {
                $attachment['content'] = base64_decode($resendData['content']);
                $attachment['size'] = strlen($attachment['content']);

                $updateSql = "UPDATE email_attachments SET content = ?, size = ? WHERE id = ?";
                $stmt = mysqli_prepare($this->con, $updateSql);
                mysqli_stmt_bind_param($stmt, 'sii', $attachment['content'], $attachment['size'], $attachmentId);
                mysqli_stmt_execute($stmt);
            }
        }

        return $attachment;
    }

    public function markAsRead(int $id, bool $read = true): bool
    {
        $readVal = $read ? 1 : 0;
        $sql = "UPDATE incoming_emails SET is_read = $readVal WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    public function markAsStarred(int $id, bool $starred = true): bool
    {
        $starredVal = $starred ? 1 : 0;
        $sql = "UPDATE incoming_emails SET is_starred = $starredVal WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    public function moveToFolder(int $id, string $folder): bool
    {
        $folder = mysqli_real_escape_string($this->con, $folder);
        $sql = "UPDATE incoming_emails SET folder = '$folder' WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    public function deleteEmail(int $id, bool $permanent = false): bool
    {
        if ($permanent) {
            $sql = "DELETE FROM incoming_emails WHERE id = $id";
        } else {
            $sql = "UPDATE incoming_emails SET is_deleted = 1, folder = 'trash' WHERE id = $id";
        }
        return mysqli_query($this->con, $sql);
    }

    public function getFolderStats(): array
    {
        $sql = "SELECT 
                folder,
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
            FROM incoming_emails
            WHERE is_deleted = 0
            GROUP BY folder";

        $result = mysqli_query($this->con, $sql);
        $stats = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $stats[$row['folder']] = [
                'total' => (int) $row['total'],
                'unread' => (int) $row['unread'],
            ];
        }

        return $stats;
    }
}
