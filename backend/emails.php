<?php
/**
 * Incoming Emails API
 * 
 * REST API für das Abrufen und Verwalten von empfangenen E-Mails.
 * Verwendet Resend als E-Mail-Provider.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db_connection.php';
require_once 'functions.php';
// require_once 'vendor/autoload.php'; // Nicht nötig für Resend (verwendet cURL)
require_once 'services/ResendReceiver.php';

$receiver = new \ControlCenter\ResendReceiver($con);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        // ==========================================
        // E-Mail-Listen
        // ==========================================
        
        case 'list':
        case 'get_emails':
            // GET /emails.php?action=list&folder=inbox&limit=50&offset=0&search=
            $options = [
                'folder' => $_GET['folder'] ?? 'inbox',
                'limit' => (int)($_GET['limit'] ?? 50),
                'offset' => (int)($_GET['offset'] ?? 0),
                'search' => $_GET['search'] ?? null,
                'include_deleted' => isset($_GET['include_deleted']) && $_GET['include_deleted'] === 'true',
            ];
            
            $result = $receiver->getEmails($options);
            echo json_encode([
                'success' => true,
                'data' => $result,
            ], JSON_PRETTY_PRINT);
            break;

        // ==========================================
        // Einzelne E-Mail
        // ==========================================
        
        case 'get':
        case 'get_email':
            // GET /emails.php?action=get&id=123
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Email ID required');
            }
            
            $email = $receiver->getEmail($id);
            
            if (!$email) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Email not found']);
                break;
            }
            
            // Als gelesen markieren
            if (isset($_GET['mark_read']) && $_GET['mark_read'] !== 'false') {
                $receiver->markAsRead($id, true);
                $email['is_read'] = 1;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $email,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;

        // ==========================================
        // Anhänge
        // ==========================================
        
        case 'get_attachment':
            // GET /emails.php?action=get_attachment&id=123
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Attachment ID required');
            }
            
            $attachment = $receiver->getAttachmentContent($id);
            
            if (!$attachment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Attachment not found']);
                break;
            }
            
            // Download oder Base64 zurückgeben
            if (isset($_GET['download']) && $_GET['download'] === 'true') {
                header('Content-Type: ' . $attachment['content_type']);
                header('Content-Disposition: attachment; filename="' . $attachment['filename'] . '"');
                header('Content-Length: ' . $attachment['size']);
                echo $attachment['content'];
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $attachment['id'],
                        'filename' => $attachment['filename'],
                        'content_type' => $attachment['content_type'],
                        'size' => $attachment['size'],
                        'content_base64' => base64_encode($attachment['content']),
                    ],
                ]);
            }
            break;

        // ==========================================
        // E-Mail-Aktionen
        // ==========================================
        
        case 'mark_read':
            // POST /emails.php?action=mark_read
            // Body: { "id": 123, "read": true }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
            $read = $input['read'] ?? $_POST['read'] ?? true;
            
            if (!$id) {
                throw new Exception('Email ID required');
            }
            
            $result = $receiver->markAsRead($id, (bool)$read);
            echo json_encode(['success' => $result]);
            break;

        case 'mark_starred':
            // POST /emails.php?action=mark_starred
            // Body: { "id": 123, "starred": true }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
            $starred = $input['starred'] ?? $_POST['starred'] ?? true;
            
            if (!$id) {
                throw new Exception('Email ID required');
            }
            
            $result = $receiver->markAsStarred($id, (bool)$starred);
            echo json_encode(['success' => $result]);
            break;

        case 'move':
        case 'move_to_folder':
            // POST /emails.php?action=move
            // Body: { "id": 123, "folder": "archive" }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
            $folder = $input['folder'] ?? $_POST['folder'] ?? 'inbox';
            
            if (!$id) {
                throw new Exception('Email ID required');
            }
            
            $result = $receiver->moveToFolder($id, $folder);
            echo json_encode(['success' => $result]);
            break;

        case 'delete':
            // DELETE /emails.php?action=delete&id=123
            // oder POST /emails.php?action=delete mit Body { "id": 123, "permanent": false }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? $_GET['id'] ?? $_POST['id'] ?? 0);
            $permanent = $input['permanent'] ?? $_GET['permanent'] ?? false;
            
            if (!$id) {
                throw new Exception('Email ID required');
            }
            
            $result = $receiver->deleteEmail($id, (bool)$permanent);
            echo json_encode(['success' => $result]);
            break;

        case 'bulk_action':
            // POST /emails.php?action=bulk_action
            // Body: { "ids": [1,2,3], "action": "mark_read"|"delete"|"move", "folder": "archive" }
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [];
            $bulkAction = $input['action'] ?? '';
            
            if (empty($ids)) {
                throw new Exception('Email IDs required');
            }
            
            $success = 0;
            $failed = 0;
            
            foreach ($ids as $id) {
                $result = false;
                switch ($bulkAction) {
                    case 'mark_read':
                        $result = $receiver->markAsRead((int)$id, true);
                        break;
                    case 'mark_unread':
                        $result = $receiver->markAsRead((int)$id, false);
                        break;
                    case 'delete':
                        $result = $receiver->deleteEmail((int)$id);
                        break;
                    case 'move':
                        $folder = $input['folder'] ?? 'inbox';
                        $result = $receiver->moveToFolder((int)$id, $folder);
                        break;
                    case 'star':
                        $result = $receiver->markAsStarred((int)$id, true);
                        break;
                    case 'unstar':
                        $result = $receiver->markAsStarred((int)$id, false);
                        break;
                }
                
                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            
            echo json_encode([
                'success' => $failed === 0,
                'processed' => $success,
                'failed' => $failed,
            ]);
            break;

        // ==========================================
        // Statistiken
        // ==========================================
        
        case 'stats':
        case 'folder_stats':
            $stats = $receiver->getFolderStats();
            echo json_encode([
                'success' => true,
                'data' => $stats,
            ]);
            break;

        // ==========================================
        // Test/Debug: Raw E-Mail importieren
        // ==========================================
        
        case 'import_raw':
            // POST /emails.php?action=import_raw
            // Body: Raw E-Mail als Text
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $rawEmail = file_get_contents('php://input');
            
            if (empty($rawEmail)) {
                throw new Exception('Raw email content required');
            }
            
            $result = $receiver->processRawEmail($rawEmail);
            echo json_encode($result);
            break;

        // ==========================================
        // Test: SNS Webhook simulieren
        // ==========================================
        
        case 'test_webhook':
            // POST /emails.php?action=test_webhook
            // Body: SNS-ähnliches JSON
            if ($method !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $payload = file_get_contents('php://input');
            $result = $receiver->processSnsNotification($payload);
            echo json_encode($result);
            break;

        // ==========================================
        // Standard: Keine Aktion
        // ==========================================
        
        default:
            // Wenn keine Action aber GET mit ID -> einzelne E-Mail
            if ($method === 'GET' && isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $email = $receiver->getEmail($id);
                
                if (!$email) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Email not found']);
                    break;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $email,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                // Standard: Liste zurückgeben
                $options = [
                    'folder' => $_GET['folder'] ?? 'inbox',
                    'limit' => (int)($_GET['limit'] ?? 50),
                    'offset' => (int)($_GET['offset'] ?? 0),
                ];
                
                $result = $receiver->getEmails($options);
                echo json_encode([
                    'success' => true,
                    'data' => $result,
                    'available_actions' => [
                        'list' => 'GET ?action=list&folder=inbox&limit=50&offset=0&search=',
                        'get' => 'GET ?action=get&id=123',
                        'get_attachment' => 'GET ?action=get_attachment&id=123&download=true',
                        'mark_read' => 'POST ?action=mark_read {id, read}',
                        'mark_starred' => 'POST ?action=mark_starred {id, starred}',
                        'move' => 'POST ?action=move {id, folder}',
                        'delete' => 'DELETE ?action=delete&id=123 oder POST {id, permanent}',
                        'bulk_action' => 'POST ?action=bulk_action {ids[], action, folder?}',
                        'stats' => 'GET ?action=stats',
                    ],
                ], JSON_PRETTY_PRINT);
            }
            break;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
