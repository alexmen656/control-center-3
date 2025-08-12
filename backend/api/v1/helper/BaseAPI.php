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

include "../../use_template_function.php";
include "../../db_connection.php";
include "../../functions.php";

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
        $apiKey = escape_string($apiKey);
        $apiId = escape_string($apiId);

        $result = query("
            SELECT projectID 
            FROM project_api_subscriptions 
            WHERE api_key = '$apiKey' 
            AND api_id = '$apiId'
        ");

        if ($result && mysqli_num_rows($result) === 1) {
            $subscription = mysqli_fetch_assoc($result);
            $this->projectID = $subscription['projectID'];

            return true;
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

    /*protected function logApiCall($endpoint, $method, $responseCode = 200)
    {
        $endpoint = escape_string($endpoint);
        $method = escape_string($method);
        $userAgent = escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip = escape_string($_SERVER['REMOTE_ADDR'] ?? '');
        $timestamp = date('Y-m-d H:i:s');
        $apiId = escape_string($this->apiId ?? '');

        query("INSERT INTO api_logs (project_id, user_id, app_id, endpoint, method, response_code, user_agent, ip_address, created_at) 
               VALUES ('{$this->projectID}', {$this->userID}, '$apiId', '$endpoint', '$method', $responseCode, '$userAgent', '$ip', '$timestamp')");
    }*/

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
}
