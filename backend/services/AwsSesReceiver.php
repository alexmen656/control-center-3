<?php
namespace ControlCenter;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * AWS SES Email Receiver Service
 *
 * Empfängt und verarbeitet eingehende E-Mails von AWS SES.
 * E-Mails werden über SNS-Notifications empfangen und in der DB gespeichert.
 * 
 * Setup-Anleitung:
 * 1. AWS SES Receipt Rule erstellen die E-Mails an SNS oder S3 sendet
 * 2. SNS Topic mit HTTP/HTTPS Endpoint (webhook) konfigurieren
 * 3. Webhook-URL: https://yourdomain.com/backend/webhooks/ses_incoming.php
 */
class AwsSesReceiver
{
    private $con;
    private ?S3Client $s3Client = null;
    private string $s3Bucket;
    private string $s3Prefix;

    public function __construct($dbConnection = null)
    {
        if ($dbConnection) {
            $this->con = $dbConnection;
        } else {
            global $con;
            $this->con = $con;
        }

        $this->ensureTablesExist();
        $this->initS3Client();
    }

    /**
     * Initialisiere S3 Client für E-Mail-Abruf aus S3
     */
    private function initS3Client(): void
    {
        include __DIR__ . '/../creds.php';
        
        $this->s3Bucket = getenv('AWS_SES_S3_BUCKET') ?: 'ses-incoming-emails';
        $this->s3Prefix = getenv('AWS_SES_S3_PREFIX') ?: 'emails/';

        try {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
        } catch (\Exception $e) {
            error_log("S3 Client init failed: " . $e->getMessage());
        }
    }

    /**
     * Erstelle benötigte Datenbank-Tabellen
     */
    private function ensureTablesExist(): void
    {
        // Haupt-Tabelle für E-Mails
        $sql = "CREATE TABLE IF NOT EXISTS `incoming_emails` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` VARCHAR(255) UNIQUE,
            `from_email` VARCHAR(255) NOT NULL,
            `from_name` VARCHAR(255),
            `to_email` VARCHAR(255) NOT NULL,
            `cc` TEXT,
            `bcc` TEXT,
            `subject` VARCHAR(500),
            `body_text` LONGTEXT,
            `body_html` LONGTEXT,
            `headers` JSON,
            `raw_email` LONGTEXT,
            `spam_verdict` VARCHAR(50),
            `virus_verdict` VARCHAR(50),
            `spf_verdict` VARCHAR(50),
            `dkim_verdict` VARCHAR(50),
            `dmarc_verdict` VARCHAR(50),
            `is_read` TINYINT(1) DEFAULT 0,
            `is_starred` TINYINT(1) DEFAULT 0,
            `is_archived` TINYINT(1) DEFAULT 0,
            `is_deleted` TINYINT(1) DEFAULT 0,
            `folder` VARCHAR(50) DEFAULT 'inbox',
            `labels` JSON,
            `s3_bucket` VARCHAR(255),
            `s3_key` VARCHAR(500),
            `received_at` DATETIME,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_from_email` (`from_email`),
            INDEX `idx_to_email` (`to_email`),
            INDEX `idx_folder` (`folder`),
            INDEX `idx_is_read` (`is_read`),
            INDEX `idx_received_at` (`received_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        mysqli_query($this->con, $sql);

        // Tabelle für E-Mail-Anhänge
        $sqlAttachments = "CREATE TABLE IF NOT EXISTS `email_attachments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email_id` INT NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `content_type` VARCHAR(100),
            `content_id` VARCHAR(255),
            `size` INT,
            `content` LONGBLOB,
            `s3_bucket` VARCHAR(255),
            `s3_key` VARCHAR(500),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`email_id`) REFERENCES `incoming_emails`(`id`) ON DELETE CASCADE,
            INDEX `idx_email_id` (`email_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        mysqli_query($this->con, $sqlAttachments);
    }

    /**
     * Verarbeite SNS Notification (von AWS SES)
     * 
     * @param string $rawPayload Raw JSON payload vom SNS
     * @return array Ergebnis der Verarbeitung
     */
    public function processSnsNotification(string $rawPayload): array
    {
        $payload = json_decode($rawPayload, true);
        
        if (!$payload) {
            return ['success' => false, 'error' => 'Invalid JSON payload'];
        }

        // SNS Subscription Confirmation
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            return $this->confirmSnsSubscription($payload);
        }

        // SNS Notification
        if (isset($payload['Type']) && $payload['Type'] === 'Notification') {
            $message = json_decode($payload['Message'], true);
            
            if (!$message) {
                return ['success' => false, 'error' => 'Invalid message in notification'];
            }

            // SES Notification Type prüfen
            $notificationType = $message['notificationType'] ?? $message['eventType'] ?? null;

            switch ($notificationType) {
                case 'Received':
                    return $this->processReceivedEmail($message);
                case 'Bounce':
                    return $this->processBounce($message);
                case 'Complaint':
                    return $this->processComplaint($message);
                default:
                    // Direkte E-Mail-Daten (wenn kein notificationType)
                    if (isset($message['mail'])) {
                        return $this->processReceivedEmail($message);
                    }
                    return ['success' => false, 'error' => 'Unknown notification type: ' . $notificationType];
            }
        }

        return ['success' => false, 'error' => 'Unknown payload type'];
    }

    /**
     * Bestätige SNS Subscription
     */
    private function confirmSnsSubscription(array $payload): array
    {
        $subscribeUrl = $payload['SubscribeURL'] ?? null;
        
        if ($subscribeUrl) {
            // URL aufrufen um Subscription zu bestätigen
            $response = file_get_contents($subscribeUrl);
            return ['success' => true, 'message' => 'Subscription confirmed'];
        }

        return ['success' => false, 'error' => 'No SubscribeURL found'];
    }

    /**
     * Verarbeite empfangene E-Mail
     */
    private function processReceivedEmail(array $message): array
    {
        try {
            $mail = $message['mail'] ?? [];
            $receipt = $message['receipt'] ?? [];
            $content = $message['content'] ?? null;

            // E-Mail-Daten extrahieren
            $messageId = $mail['messageId'] ?? uniqid('email_');
            $source = $mail['source'] ?? '';
            $destination = $mail['destination'] ?? [];
            $timestamp = $mail['timestamp'] ?? date('c');
            $headers = $mail['headers'] ?? [];
            $commonHeaders = $mail['commonHeaders'] ?? [];

            // Header als Key-Value Map
            $headerMap = [];
            foreach ($headers as $header) {
                $headerMap[$header['name']] = $header['value'];
            }

            // Absender-Informationen
            $fromEmail = $source;
            $fromName = '';
            if (isset($commonHeaders['from'][0])) {
                $parsed = $this->parseEmailAddress($commonHeaders['from'][0]);
                $fromEmail = $parsed['email'];
                $fromName = $parsed['name'];
            }

            // E-Mail-Content verarbeiten
            $bodyText = '';
            $bodyHtml = '';
            $rawEmail = '';
            $attachments = [];

            // Wenn Content direkt mitgeliefert wurde
            if ($content) {
                $parsed = $this->parseMimeContent($content);
                $bodyText = $parsed['text'];
                $bodyHtml = $parsed['html'];
                $rawEmail = $content;
                $attachments = $parsed['attachments'];
            }
            // Wenn E-Mail in S3 gespeichert wurde
            elseif (isset($receipt['action']['bucketName'])) {
                $s3Bucket = $receipt['action']['bucketName'];
                $s3Key = $receipt['action']['objectKey'];
                
                $rawEmail = $this->fetchEmailFromS3($s3Bucket, $s3Key);
                if ($rawEmail) {
                    $parsed = $this->parseMimeContent($rawEmail);
                    $bodyText = $parsed['text'];
                    $bodyHtml = $parsed['html'];
                    $attachments = $parsed['attachments'];
                }
            }

            // Verdicts extrahieren
            $spamVerdict = $receipt['spamVerdict']['status'] ?? null;
            $virusVerdict = $receipt['virusVerdict']['status'] ?? null;
            $spfVerdict = $receipt['spfVerdict']['status'] ?? null;
            $dkimVerdict = $receipt['dkimVerdict']['status'] ?? null;
            $dmarcVerdict = $receipt['dmarcVerdict']['status'] ?? null;

            // In Datenbank speichern
            $sql = "INSERT INTO `incoming_emails` 
                (message_id, from_email, from_name, to_email, cc, subject, 
                body_text, body_html, headers, raw_email,
                spam_verdict, virus_verdict, spf_verdict, dkim_verdict, dmarc_verdict,
                s3_bucket, s3_key, received_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    body_text = VALUES(body_text),
                    body_html = VALUES(body_html),
                    updated_at = CURRENT_TIMESTAMP";

            $stmt = mysqli_prepare($this->con, $sql);
            
            $toEmail = implode(', ', $destination);
            $ccEmail = $commonHeaders['cc'] ?? null;
            $ccEmailStr = $ccEmail ? implode(', ', (array)$ccEmail) : null;
            $subject = $commonHeaders['subject'] ?? '(No Subject)';
            $headersJson = json_encode($headerMap);
            $s3Bucket = $receipt['action']['bucketName'] ?? null;
            $s3Key = $receipt['action']['objectKey'] ?? null;
            $receivedAt = date('Y-m-d H:i:s', strtotime($timestamp));

            mysqli_stmt_bind_param($stmt, 'ssssssssssssssssss',
                $messageId, $fromEmail, $fromName, $toEmail, $ccEmailStr, $subject,
                $bodyText, $bodyHtml, $headersJson, $rawEmail,
                $spamVerdict, $virusVerdict, $spfVerdict, $dkimVerdict, $dmarcVerdict,
                $s3Bucket, $s3Key, $receivedAt
            );

            $result = mysqli_stmt_execute($stmt);
            
            if (!$result) {
                throw new \Exception(mysqli_error($this->con));
            }

            $emailId = mysqli_insert_id($this->con);
            if (!$emailId) {
                // Bei ON DUPLICATE KEY UPDATE die ID holen
                $idResult = mysqli_query($this->con, 
                    "SELECT id FROM incoming_emails WHERE message_id = '" . 
                    mysqli_real_escape_string($this->con, $messageId) . "'"
                );
                $row = mysqli_fetch_assoc($idResult);
                $emailId = $row['id'];
            }

            // Anhänge speichern
            foreach ($attachments as $attachment) {
                $this->saveAttachment($emailId, $attachment);
            }

            return [
                'success' => true,
                'email_id' => $emailId,
                'message_id' => $messageId,
            ];

        } catch (\Exception $e) {
            error_log("Error processing email: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse MIME-formatierten E-Mail-Inhalt
     */
    public function parseMimeContent(string $rawEmail): array
    {
        $result = [
            'text' => '',
            'html' => '',
            'attachments' => [],
            'headers' => [],
        ];

        // Header und Body trennen
        $parts = preg_split('/\r?\n\r?\n/', $rawEmail, 2);
        $headerSection = $parts[0] ?? '';
        $bodySection = $parts[1] ?? '';

        // Headers parsen
        $result['headers'] = $this->parseHeaders($headerSection);
        
        // Content-Type ermitteln
        $contentType = $result['headers']['Content-Type'] ?? 'text/plain';
        
        // Multipart E-Mail
        if (preg_match('/multipart/i', $contentType)) {
            $boundary = $this->extractBoundary($contentType);
            if ($boundary) {
                $this->parseMultipart($bodySection, $boundary, $result);
            }
        }
        // Einfache E-Mail
        else {
            $encoding = $result['headers']['Content-Transfer-Encoding'] ?? '7bit';
            $charset = $this->extractCharset($contentType);
            $decodedBody = $this->decodeContent($bodySection, $encoding, $charset);
            
            if (stripos($contentType, 'text/html') !== false) {
                $result['html'] = $decodedBody;
            } else {
                $result['text'] = $decodedBody;
            }
        }

        return $result;
    }

    /**
     * Parse Headers in Array
     */
    private function parseHeaders(string $headerSection): array
    {
        $headers = [];
        $lines = preg_split('/\r?\n/', $headerSection);
        $currentHeader = '';
        $currentValue = '';

        foreach ($lines as $line) {
            // Fortsetzungszeile (beginnt mit Whitespace)
            if (preg_match('/^\s+/', $line)) {
                $currentValue .= ' ' . trim($line);
            }
            // Neue Header-Zeile
            elseif (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                if ($currentHeader) {
                    $headers[$currentHeader] = $currentValue;
                }
                $currentHeader = $matches[1];
                $currentValue = $matches[2];
            }
        }

        // Letzten Header speichern
        if ($currentHeader) {
            $headers[$currentHeader] = $currentValue;
        }

        return $headers;
    }

    /**
     * Extrahiere Boundary aus Content-Type
     */
    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary=["\']?([^"\';]+)["\']?/i', $contentType, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extrahiere Charset aus Content-Type
     */
    private function extractCharset(string $contentType): string
    {
        if (preg_match('/charset=["\']?([^"\';]+)["\']?/i', $contentType, $matches)) {
            return $matches[1];
        }
        return 'UTF-8';
    }

    /**
     * Parse Multipart Content
     */
    private function parseMultipart(string $body, string $boundary, array &$result): void
    {
        $parts = preg_split('/--' . preg_quote($boundary, '/') . '(--)?\r?\n?/', $body);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }

            // Part-Header und Body trennen
            $sections = preg_split('/\r?\n\r?\n/', $part, 2);
            $partHeaders = $this->parseHeaders($sections[0] ?? '');
            $partBody = $sections[1] ?? '';

            $partContentType = $partHeaders['Content-Type'] ?? 'text/plain';
            $encoding = $partHeaders['Content-Transfer-Encoding'] ?? '7bit';
            $disposition = $partHeaders['Content-Disposition'] ?? '';
            $charset = $this->extractCharset($partContentType);

            // Verschachtelte Multipart
            if (preg_match('/multipart/i', $partContentType)) {
                $subBoundary = $this->extractBoundary($partContentType);
                if ($subBoundary) {
                    $this->parseMultipart($partBody, $subBoundary, $result);
                }
                continue;
            }

            // Attachment oder Inline
            if (preg_match('/attachment|inline/i', $disposition) || 
                (!stripos($partContentType, 'text/') === 0 && 
                 !stripos($partContentType, 'multipart/') === 0)) {
                
                $filename = $this->extractFilename($disposition, $partContentType);
                $contentId = $partHeaders['Content-ID'] ?? null;
                if ($contentId) {
                    $contentId = trim($contentId, '<>');
                }

                $result['attachments'][] = [
                    'filename' => $filename,
                    'content_type' => preg_replace('/;.*/', '', $partContentType),
                    'content_id' => $contentId,
                    'content' => $this->decodeContent($partBody, $encoding),
                    'size' => strlen($this->decodeContent($partBody, $encoding)),
                ];
            }
            // Text-Content
            elseif (stripos($partContentType, 'text/html') !== false) {
                $result['html'] = $this->decodeContent($partBody, $encoding, $charset);
            }
            elseif (stripos($partContentType, 'text/plain') !== false) {
                $result['text'] = $this->decodeContent($partBody, $encoding, $charset);
            }
        }
    }

    /**
     * Extrahiere Dateiname aus Content-Disposition oder Content-Type
     */
    private function extractFilename(string $disposition, string $contentType): string
    {
        // Aus Content-Disposition
        if (preg_match('/filename=["\']?([^"\';]+)["\']?/i', $disposition, $matches)) {
            return $this->decodeRfc2047($matches[1]);
        }
        // Aus Content-Type (name parameter)
        if (preg_match('/name=["\']?([^"\';]+)["\']?/i', $contentType, $matches)) {
            return $this->decodeRfc2047($matches[1]);
        }
        return 'attachment_' . uniqid();
    }

    /**
     * Dekodiere RFC 2047 encoded-word
     */
    private function decodeRfc2047(string $value): string
    {
        // =?charset?encoding?encoded_text?=
        $value = preg_replace_callback(
            '/=\?([^?]+)\?([BQ])\?([^?]*)\?=/i',
            function ($matches) {
                $charset = $matches[1];
                $encoding = strtoupper($matches[2]);
                $text = $matches[3];

                if ($encoding === 'B') {
                    $decoded = base64_decode($text);
                } else {
                    $decoded = quoted_printable_decode(str_replace('_', ' ', $text));
                }

                return mb_convert_encoding($decoded, 'UTF-8', $charset);
            },
            $value
        );
        return $value;
    }

    /**
     * Dekodiere Content basierend auf Transfer-Encoding
     */
    private function decodeContent(string $content, string $encoding, string $charset = 'UTF-8'): string
    {
        $encoding = strtolower(trim($encoding));
        
        switch ($encoding) {
            case 'base64':
                $decoded = base64_decode($content);
                break;
            case 'quoted-printable':
                $decoded = quoted_printable_decode($content);
                break;
            case '7bit':
            case '8bit':
            case 'binary':
            default:
                $decoded = $content;
        }

        // Charset konvertieren
        if (strtoupper($charset) !== 'UTF-8') {
            $decoded = @mb_convert_encoding($decoded, 'UTF-8', $charset);
        }

        return $decoded;
    }

    /**
     * Hole E-Mail aus S3
     */
    private function fetchEmailFromS3(string $bucket, string $key): ?string
    {
        if (!$this->s3Client) {
            return null;
        }

        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            return (string) $result['Body'];
        } catch (AwsException $e) {
            error_log("S3 fetch error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Speichere Anhang in DB
     */
    private function saveAttachment(int $emailId, array $attachment): void
    {
        $sql = "INSERT INTO `email_attachments` 
            (email_id, filename, content_type, content_id, size, content)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, 'isssis',
            $emailId,
            $attachment['filename'],
            $attachment['content_type'],
            $attachment['content_id'],
            $attachment['size'],
            $attachment['content']
        );
        mysqli_stmt_execute($stmt);
    }

    /**
     * Parse E-Mail-Adresse "Name <email@example.com>" format
     */
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

    /**
     * Verarbeite Bounce-Notification
     */
    private function processBounce(array $message): array
    {
        // Hier könntest du Bounce-Tracking implementieren
        error_log("Bounce received: " . json_encode($message));
        return ['success' => true, 'type' => 'bounce'];
    }

    /**
     * Verarbeite Complaint-Notification
     */
    private function processComplaint(array $message): array
    {
        // Hier könntest du Complaint-Tracking implementieren
        error_log("Complaint received: " . json_encode($message));
        return ['success' => true, 'type' => 'complaint'];
    }

    /**
     * Hole alle E-Mails (mit Paginierung)
     */
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
                id, message_id, from_email, from_name, to_email, subject,
                LEFT(body_text, 200) as preview,
                is_read, is_starred, is_archived, folder, labels,
                spam_verdict, received_at, created_at
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

        // Gesamtanzahl
        $countSql = "SELECT COUNT(*) as total FROM incoming_emails WHERE $whereClause";
        $countResult = mysqli_query($this->con, $countSql);
        $total = mysqli_fetch_assoc($countResult)['total'];

        return [
            'emails' => $emails,
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Hole einzelne E-Mail
     */
    public function getEmail(int $id): ?array
    {
        $sql = "SELECT * FROM incoming_emails WHERE id = $id";
        $result = mysqli_query($this->con, $sql);
        $email = mysqli_fetch_assoc($result);

        if (!$email) {
            return null;
        }

        // Headers und Labels dekodieren
        $email['headers'] = json_decode($email['headers'], true) ?? [];
        $email['labels'] = json_decode($email['labels'], true) ?? [];

        // Anhänge laden
        $email['attachments'] = $this->getAttachments($id);

        return $email;
    }

    /**
     * Prüfe ob E-Mail Anhänge hat
     */
    private function hasAttachments(int $emailId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM email_attachments WHERE email_id = $emailId";
        $result = mysqli_query($this->con, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }

    /**
     * Hole Anhänge einer E-Mail
     */
    public function getAttachments(int $emailId): array
    {
        $sql = "SELECT id, filename, content_type, content_id, size, created_at 
                FROM email_attachments WHERE email_id = $emailId";
        $result = mysqli_query($this->con, $sql);
        $attachments = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $attachments[] = $row;
        }

        return $attachments;
    }

    /**
     * Hole Anhang-Inhalt
     */
    public function getAttachmentContent(int $attachmentId): ?array
    {
        $sql = "SELECT * FROM email_attachments WHERE id = $attachmentId";
        $result = mysqli_query($this->con, $sql);
        return mysqli_fetch_assoc($result);
    }

    /**
     * Markiere E-Mail als gelesen
     */
    public function markAsRead(int $id, bool $read = true): bool
    {
        $readVal = $read ? 1 : 0;
        $sql = "UPDATE incoming_emails SET is_read = $readVal WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    /**
     * Markiere E-Mail als Starred
     */
    public function markAsStarred(int $id, bool $starred = true): bool
    {
        $starredVal = $starred ? 1 : 0;
        $sql = "UPDATE incoming_emails SET is_starred = $starredVal WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    /**
     * Verschiebe E-Mail in Ordner
     */
    public function moveToFolder(int $id, string $folder): bool
    {
        $folder = mysqli_real_escape_string($this->con, $folder);
        $sql = "UPDATE incoming_emails SET folder = '$folder' WHERE id = $id";
        return mysqli_query($this->con, $sql);
    }

    /**
     * Lösche E-Mail (soft delete)
     */
    public function deleteEmail(int $id, bool $permanent = false): bool
    {
        if ($permanent) {
            $sql = "DELETE FROM incoming_emails WHERE id = $id";
        } else {
            $sql = "UPDATE incoming_emails SET is_deleted = 1, folder = 'trash' WHERE id = $id";
        }
        return mysqli_query($this->con, $sql);
    }

    /**
     * Hole Folder-Statistiken
     */
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
                'total' => (int)$row['total'],
                'unread' => (int)$row['unread'],
            ];
        }

        return $stats;
    }

    /**
     * Verarbeite raw E-Mail direkt (für Tests oder manuelle Imports)
     */
    public function processRawEmail(string $rawEmail, array $metadata = []): array
    {
        $parsed = $this->parseMimeContent($rawEmail);
        
        $messageId = $parsed['headers']['Message-ID'] ?? uniqid('email_');
        $messageId = trim($messageId, '<>');
        
        $fromParsed = $this->parseEmailAddress($parsed['headers']['From'] ?? '');
        $toParsed = $parsed['headers']['To'] ?? '';
        $subject = $parsed['headers']['Subject'] ?? '(No Subject)';
        $date = $parsed['headers']['Date'] ?? date('c');

        $sql = "INSERT INTO `incoming_emails` 
            (message_id, from_email, from_name, to_email, subject, 
            body_text, body_html, headers, raw_email, received_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";

        $stmt = mysqli_prepare($this->con, $sql);
        $headersJson = json_encode($parsed['headers']);
        $receivedAt = date('Y-m-d H:i:s', strtotime($date));

        mysqli_stmt_bind_param($stmt, 'ssssssssss',
            $messageId,
            $fromParsed['email'],
            $fromParsed['name'],
            $toParsed,
            $subject,
            $parsed['text'],
            $parsed['html'],
            $headersJson,
            $rawEmail,
            $receivedAt
        );

        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            return ['success' => false, 'error' => mysqli_error($this->con)];
        }

        $emailId = mysqli_insert_id($this->con);

        // Anhänge speichern
        foreach ($parsed['attachments'] as $attachment) {
            $this->saveAttachment($emailId, $attachment);
        }

        return [
            'success' => true,
            'email_id' => $emailId,
            'message_id' => $messageId,
        ];
    }
}
