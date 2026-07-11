<?php
ini_set('display_errors', true);
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../../use_template_function.php";
require_once "../.././helpers/db_connection.php";
require_once "../../functions.php";

$headers = getRequestHeaders();


class BaseAPI
{
    protected $projectID;
    protected $userID;
    protected $apiKey;
    protected $apiId;

    public function __construct($apiId = null)
    {
        $this->apiId = $apiId;

        if ($this->apiId) {
            $this->authenticate();
        }
    }

    public function authenticate($apiId = null)
    {
        if ($apiId) {
            $this->apiId = $apiId;
        }

        if (!$this->apiId) {
            $this->sendError('App ID is required for authentication', 400);
        }

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->sendError('Missing or invalid authorization header', 401);
        }

        $this->apiKey = $matches[1];

        if (!$this->validateApiKey($this->apiKey, $this->apiId)) {
            $this->sendError('Invalid API key for this service', 401);
        }
    }

    private function validateApiKey($apiKey, $apiId)
    {
        $prefix = escape_string(substr($apiKey, 0, 16));
        $hash = hash('sha256', $apiKey);
        $apiId = escape_string($apiId);

        $result = query("
            SELECT projectID, key_hash
            FROM project_api_subscriptions
            WHERE key_prefix = '$prefix'
            AND api_id = '$apiId'
            AND is_enabled = 1
        ");

        if ($result) {
            while ($subscription = mysqli_fetch_assoc($result)) {
                if (!empty($subscription['key_hash']) && hash_equals($subscription['key_hash'], $hash)) {
                    $this->projectID = $subscription['projectID'];
                    $pid = escape_string($subscription['projectID']);
                    $ur = query("SELECT userID FROM control_center_user_projects WHERE projectID='$pid' ORDER BY role ASC LIMIT 1");
                    if ($ur && mysqli_num_rows($ur) > 0) { $this->userID = (int) mysqli_fetch_assoc($ur)['userID']; }
                    return true;

                }
            }
        }

        return false;
    }

    protected function sendSuccess($data = null, $message = 'Success')
    {
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

    protected function sendError($message = 'Error', $code = 400)
    {
        http_response_code($code);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => $message,
            'code' => $code
        ]);
        exit();
    }

    protected function validateRequired($data, $required)
    {
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

    protected function getJsonInput()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }

        return $data;
    }

    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }

    protected function checkRateLimit()
    {
        if (!$this->projectID || !$this->apiId) {
            return true;
        }

        $apiId = escape_string($this->apiId);
        $projectID = escape_string($this->projectID);

        $result = query("
            SELECT pas.rate_limit, pas.usage_count, pas.last_used
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.projectID = '$projectID' 
            AND ca.slug = '$apiId'
            AND pas.is_enabled = 1
        ");

        if ($result && mysqli_num_rows($result) === 1) {
            $subscription = mysqli_fetch_assoc($result);
            $rateLimit = $subscription['rate_limit'];
            $usageCount = $subscription['usage_count'];
            $lastUsed = $subscription['last_used'];

            $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));

            if ($lastUsed && $lastUsed > $oneHourAgo && $usageCount >= $rateLimit) {
                $this->sendError('Rate limit exceeded. Try again later.', 429);
            }
        }

        return true;
    }

    protected function logApiCall($service = '', $method = '') {}
}
