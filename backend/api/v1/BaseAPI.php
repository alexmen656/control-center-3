<?php

/**
 * BaseAPI - Basis-Klasse für alle CMS APIs
 * Stellt gemeinsame Funktionalität wie Authentifizierung, Validierung und Response-Handling bereit
 */

class BaseAPI {
    protected $projectID;
    protected $userID;
    protected $apiKey;
    protected $allowedOrigins = [
        'https://alex.polan.sk',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173'
    ];

    public function __construct() {
        $this->handleCORS();
        $this->authenticate();
    }

    /**
     * Handles CORS headers
     */
    private function handleCORS() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $this->allowedOrigins) || strpos($origin, 'alex.polan.sk') !== false) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Authentifiziert die API-Anfrage
     */
    protected function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->sendError('Missing or invalid authorization header', 401);
        }
        
        $this->apiKey = $matches[1];
        
        // Validiere API Key
        if (!$this->validateApiKey($this->apiKey)) {
            $this->sendError('Invalid API key', 401);
        }
    }

    /**
     * Validiert einen API-Schlüssel
     */
    private function validateApiKey($apiKey) {
        // Für die Demo verwenden wir einen einfachen Ansatz
        // In der Produktion sollte hier eine echte API-Key-Validierung stattfinden
        if ($apiKey === 'demo-api-key-123' || $apiKey === 'test-key') {
            $this->projectID = 1; // Demo-Projekt
            $this->userID = 1; // Demo-Benutzer
            return true;
        }
        
        // Falls mysql.php verfügbar ist, echte Validierung verwenden
        if (file_exists('../mysql.php')) {
            require_once '../mysql.php';
            
            // Escape string function prüfen
            if (function_exists('mysqli_real_escape_string') && isset($GLOBALS['con'])) {
                $apiKey = mysqli_real_escape_string($GLOBALS['con'], $apiKey);
                $result = mysqli_query($GLOBALS['con'], "SELECT * FROM project_api_subscriptions WHERE api_key = '$apiKey' AND is_active = 1");
                
                if ($result && mysqli_num_rows($result) === 1) {
                    $subscription = mysqli_fetch_assoc($result);
                    $this->projectID = $subscription['project_id'];
                    $this->userID = $subscription['user_id'];
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Sendet eine erfolgreiche JSON-Antwort
     */
    protected function sendSuccess($data = null, $message = 'Success') {
        http_response_code(200);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit();
    }

    /**
     * Sendet eine Fehler-JSON-Antwort
     */
    protected function sendError($message = 'Error', $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $code
        ]);
        exit();
    }

    /**
     * Validiert erforderliche Parameter
     */
    protected function validateRequired($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Missing required fields: ' . implode(', ', $missing), 400);
        }
    }

    /**
     * Holt JSON-Daten aus dem Request Body
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }
        
        return $data;
    }

    /**
     * Sanitisiert Eingabedaten
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Prüft ob der Benutzer Berechtigung für das Projekt hat
     */
    protected function checkProjectAccess($projectID = null) {
        $projectID = $projectID ?? $this->projectID;
        
        if (file_exists('../mysql.php')) {
            require_once '../mysql.php';
            
            if (function_exists('mysqli_query') && isset($GLOBALS['con'])) {
                $result = mysqli_query($GLOBALS['con'], "SELECT * FROM control_center_user_projects WHERE userID = {$this->userID} AND projectID = '$projectID'");
                
                if (!$result || mysqli_num_rows($result) === 0) {
                    $this->sendError('Access denied to this project', 403);
                }
            }
        }
        // Wenn keine DB-Verbindung, dann Demo-Modus - Zugriff erlauben
    }

    /**
     * Loggt API-Aufrufe für Analytics
     */
    protected function logApiCall($endpoint, $method, $responseCode = 200) {
        if (file_exists('../mysql.php')) {
            require_once '../mysql.php';
            
            if (function_exists('mysqli_real_escape_string') && isset($GLOBALS['con'])) {
                $endpoint = mysqli_real_escape_string($GLOBALS['con'], $endpoint);
                $method = mysqli_real_escape_string($GLOBALS['con'], $method);
                $userAgent = mysqli_real_escape_string($GLOBALS['con'], $_SERVER['HTTP_USER_AGENT'] ?? '');
                $ip = mysqli_real_escape_string($GLOBALS['con'], $_SERVER['REMOTE_ADDR'] ?? '');
                $timestamp = date('Y-m-d H:i:s');
                
                mysqli_query($GLOBALS['con'], "INSERT INTO api_logs (project_id, user_id, endpoint, method, response_code, user_agent, ip_address, created_at) 
                       VALUES ('{$this->projectID}', {$this->userID}, '$endpoint', '$method', $responseCode, '$userAgent', '$ip', '$timestamp')");
            }
        }
    }
}
