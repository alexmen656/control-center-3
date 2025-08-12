<?php

/**
 * Notifications API - Benachrichtigungsystem für CMS Projekte
 * Handles notifications sending and management
 */

require_once 'helper/BaseAPI.php';

class NotificationsAPI extends BaseAPI {

    public function __construct() {
        parent::__construct();
        $this->initDatabase();
    }

    private function initDatabase() {
        // Include mysql.php für query() Funktionen
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Log API call
        $this->logApiCall('notifications', $method);

        switch ($method) {
            case 'GET':
                $this->getNotifications();
                break;
            case 'POST':
                if (isset($pathParts[3]) && $pathParts[3] === 'send') {
                    $this->sendNotification();
                } else {
                    $this->createNotification();
                }
                break;
            case 'PUT':
                if (isset($pathParts[3])) {
                    $this->markAsRead($pathParts[3]);
                }
                break;
            case 'DELETE':
                if (isset($pathParts[3])) {
                    $this->deleteNotification($pathParts[3]);
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getNotifications() {
        $params = $_GET;
        
        // SQL Query für Benachrichtigungen
        $sql = "SELECT id, title, message, type, read_status, created_at, updated_at 
                FROM notifications 
                WHERE user_id = {$this->userID}";
        
        // Filter anwenden
        if (isset($params['unread_only']) && $params['unread_only'] === 'true') {
            $sql .= " AND read_status = 0";
        }
        
        if (isset($params['type'])) {
            $type = $this->sanitize($params['type']);
            $sql .= " AND type = '$type'";
        }
        
        // Sortierung
        $sql .= " ORDER BY created_at DESC";
        
        // Paginierung
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $limit = isset($params['limit']) ? max(1, min(100, (int)$params['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        
        $sql .= " LIMIT $limit OFFSET $offset";
        
        $result = query($sql);
        
        $notifications = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
            }
        }
        
        // Anzahl ungelesener Benachrichtigungen
        $unreadResult = query("SELECT COUNT(*) as count FROM notifications WHERE user_id = {$this->userID} AND read_status = 0");
        $unreadCount = 0;
        if ($unreadResult && mysqli_num_rows($unreadResult) > 0) {
            $unreadRow = mysqli_fetch_assoc($unreadResult);
            $unreadCount = (int)$unreadRow['count'];
        }
        
        $this->sendSuccess([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'pagination' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    private function sendNotification() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['message']);
        
        $title = $this->sanitize($data['title'] ?? 'Notification');
        $message = $this->sanitize($data['message']);
        $type = $this->sanitize($data['type'] ?? 'info');
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : $this->userID;
        
        // Benachrichtigung in Datenbank speichern
        $sql = "INSERT INTO notifications (user_id, title, message, type, read_status, created_at) 
                VALUES ($userId, '$title', '$message', '$type', 0, NOW())";
        
        $result = query($sql);
        
        if ($result) {
            // Push-Benachrichtigung senden (falls Token verfügbar)
            $this->sendPushNotification($userId, $title, $message);
            
            $this->sendSuccess([
                'notification_id' => function_exists('mysqli_insert_id') && isset($GLOBALS['con']) ? mysqli_insert_id($GLOBALS['con']) : null,
                'sent' => true
            ], 'Notification sent successfully');
        } else {
            $this->sendError('Failed to send notification', 500);
        }
    }

    private function createNotification() {
        // Alias für sendNotification
        $this->sendNotification();
    }

    private function markAsRead($notificationId) {
        $id = (int)$notificationId;
        
        $sql = "UPDATE notifications 
                SET read_status = 1, updated_at = NOW() 
                WHERE id = $id AND user_id = {$this->userID}";
        
        $result = query($sql);
        
        if ($result) {
            $this->sendSuccess(['marked_as_read' => true], 'Notification marked as read');
        } else {
            $this->sendError('Failed to mark notification as read', 500);
        }
    }

    private function deleteNotification($notificationId) {
        $id = (int)$notificationId;
        
        $sql = "DELETE FROM notifications 
                WHERE id = $id AND user_id = {$this->userID}";
        
        $result = query($sql);
        
        if ($result) {
            $this->sendSuccess(['deleted' => true], 'Notification deleted successfully');
        } else {
            $this->sendError('Failed to delete notification', 500);
        }
    }

    /**
     * Sendet eine Push-Benachrichtigung an den Benutzer
     */
    private function sendPushNotification($userId, $title, $message) {
        // Push-Token des Benutzers abrufen
        $tokenResult = query("SELECT push_token FROM push_notifications_token WHERE userID = $userId AND active = 1");
        
        if ($tokenResult && mysqli_num_rows($tokenResult) > 0) {
            while ($tokenRow = mysqli_fetch_assoc($tokenResult)) {
                $pushToken = $tokenRow['push_token'];
                
                // Push-Nachricht zusammenstellen
                $payload = [
                    'to' => $pushToken,
                    'title' => $title,
                    'body' => $message,
                    'data' => [
                        'type' => 'notification',
                        'user_id' => $userId,
                        'project_id' => $this->projectID
                    ]
                ];
                
                // An Expo Push Service senden
                $this->sendExpoPushNotification($payload);
            }
        }
    }

    /**
     * Sendet eine Push-Nachricht über Expo
     */
    private function sendExpoPushNotification($payload) {
        $url = 'https://exp.host/--/api/v2/push/send';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Response loggen (optional)
        if ($httpCode !== 200) {
            error_log("Push notification failed: HTTP $httpCode - $response");
        }
    }
}

// Handle the request
$api = new NotificationsAPI();
$api->handleRequest();
