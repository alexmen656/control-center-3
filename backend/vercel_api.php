<?php
/*header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'head.php';

function getUserIDFromToken() {
    global $jwt_secret;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        throw new Exception("No authorization token provided");
    }
    
    $token = $authHeader;
    $userData = SimpleJWT::verify($token, $jwt_secret);
    
    if (!$userData || !isset($userData['sub'])) {
        throw new Exception("Invalid token or missing user ID");
    }
    
    return $userData['sub'];
}

function getVercelCredentials($userID) {
    // Get Vercel token for user
    $tokenResult = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID = $userID");
    
    if (!$tokenResult || mysqli_num_rows($tokenResult) == 0) {
        throw new Exception("No Vercel token found for user");
    }
    
    $tokenData = fetch_assoc($tokenResult);
    
    return [
        'token' => $tokenData['vercel_token']
    ];
}

class VercelAPI {
    private $token;
    private $teamId;
    
    public function __construct($token, $teamId = null) {
        $this->token = $token;
        $this->teamId = $teamId;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null, $apiVersion = 'v2') {
        $url = 'https://api.vercel.com/' . $apiVersion . $endpoint;
        
        if ($this->teamId) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'teamId=' . $this->teamId;
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'User-Agent: ControlCenter-App/1.0'
        ];
        
        $context_options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 30
            ]
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $context_options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Vercel API request failed: Unable to connect");
        }
        
        // Get HTTP response code from headers
        $http_response_header = $http_response_header ?? [];
        $httpCode = 200; // Default
        if (!empty($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception("Vercel API request failed: HTTP {$httpCode} - {$response}");
        }
    }
    
    public function getDeployments($projectName = null, $limit = 10) {
        $endpoint = '/deployments';
        $params = ['limit' => $limit];
        
        if ($projectName) {
            $params['projectId'] = $projectName;
        }
        
        $queryString = http_build_query($params);
        return $this->makeRequest($endpoint . '?' . $queryString);
    }
    
    public function getProjects() {
        return $this->makeRequest('/projects');
    }
    
    public function getProject($projectId) {
        return $this->makeRequest("/projects/{$projectId}");
    }
    
    public function createDeployment($projectName, $files = [], $gitSource = null) {
        $data = [
            'name' => $projectName,
            'files' => $files,
            'projectSettings' => [
                'framework' => 'vue'
            ]
        ];
        
        if ($gitSource) {
            $data['gitSource'] = $gitSource;
        }
        
        return $this->makeRequest('/deployments', 'POST', $data);
    }
    
    public function getDeploymentStatus($deploymentId) {
        return $this->makeRequest("/deployments/{$deploymentId}");
    }
    
    public function getEnvironmentVariables($projectId) {
        return $this->makeRequest("/projects/{$projectId}/env", 'GET', null, 'v9');
    }
    
    public function getEnvironmentVariableValue($projectId, $envId) {
        return $this->makeRequest("/projects/{$projectId}/env/{$envId}", 'GET', null, 'v9');
    }
    
    public function getEnvironmentVariablesWithValues($projectId) {
        $envVars = $this->getEnvironmentVariables($projectId);
        
        if (isset($envVars['envs']) && is_array($envVars['envs'])) {
            foreach ($envVars['envs'] as &$envVar) {
                try {
                    $valueData = $this->getEnvironmentVariableValue($projectId, $envVar['id']);
                    if (isset($valueData['value'])) {
                        $envVar['decryptedValue'] = $valueData['value'];
                    }
                } catch (Exception $e) {
                    error_log("Failed to get decrypted value for env var {$envVar['id']}: " . $e->getMessage());
                }
            }
        }
        
        return $envVars;
    }
    
    public function createEnvironmentVariable($projectId, $key, $value, $target = ['production', 'preview', 'development']) {
        $data = [
            'key' => $key,
            'value' => $value,
            'target' => $target,
            'type' => 'encrypted'
        ];
        
        return $this->makeRequest("/projects/{$projectId}/env", 'POST', $data);
    }
    
    public function updateEnvironmentVariable($projectId, $envId, $key, $value, $target = ['production', 'preview', 'development']) {
        $data = [
            'key' => $key,
            'value' => $value,
            'target' => $target,
            'type' => 'encrypted'
        ];
        
        return $this->makeRequest("/projects/{$projectId}/env/{$envId}", 'PATCH', $data);
    }
    
    public function deleteEnvironmentVariable($projectId, $envId) {
        return $this->makeRequest("/projects/{$projectId}/env/{$envId}", 'DELETE');
    }
}

try {
    // Get project information
    $project = $_GET['project'] ?? $_POST['project'] ?? null;
    if (!$project) {
        throw new Exception('Project parameter is required');
    }
    
    // Get user ID from JWT token
    $userID = getUserIDFromToken();
    
    // Get Vercel credentials from database
    $credentials = getVercelCredentials($userID);
    
    $vercel = new VercelAPI($credentials['token']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'deployments':
                    $deployments = $vercel->getDeployments($project);
                    echo json_encode(['success' => true, 'deployments' => $deployments]);
                    break;
                    
                case 'projects':
                    $projects = $vercel->getProjects();
                    echo json_encode(['success' => true, 'projects' => $projects]);
                    break;
                    
                case 'project':
                    $projectData = $vercel->getProject($project);
                    echo json_encode(['success' => true, 'project' => $projectData]);
                    break;
                    
                case 'env':
                    $envVars = $vercel->getEnvironmentVariablesWithValues($project);
                    echo json_encode(['success' => true, 'envVars' => $envVars]);
                    break;
                    
                default:
                    throw new Exception('Unknown action');
            }
        } else {
            // Default: return recent deployments
            $deployments = $vercel->getDeployments($project);
            echo json_encode(['success' => true, 'deployments' => $deployments]);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'deploy':
                    $files = $input['files'] ?? [];
                    $gitSource = $input['gitSource'] ?? null;
                    
                    $deployment = $vercel->createDeployment($project, $files, $gitSource);
                    echo json_encode(['success' => true, 'deployment' => $deployment]);
                    break;
                    
                case 'status':
                    $deploymentId = $input['deploymentId'] ?? '';
                    if (empty($deploymentId)) {
                        throw new Exception('Deployment ID is required');
                    }
                    
                    $status = $vercel->getDeploymentStatus($deploymentId);
                    echo json_encode(['success' => true, 'status' => $status]);
                    break;
                    
                case 'create_env':
                    $key = $input['key'] ?? '';
                    $value = $input['value'] ?? '';
                    $target = $input['target'] ?? ['production', 'preview', 'development'];
                    
                    if (empty($key) || empty($value)) {
                        throw new Exception('Key and value are required');
                    }
                    
                    $result = $vercel->createEnvironmentVariable($project, $key, $value, $target);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;
                    
                case 'update_env':
                    $envId = $input['envId'] ?? '';
                    $key = $input['key'] ?? '';
                    $value = $input['value'] ?? '';
                    $target = $input['target'] ?? ['production', 'preview', 'development'];
                    
                    if (empty($envId) || empty($key) || empty($value)) {
                        throw new Exception('Environment ID, key and value are required');
                    }
                    
                    $result = $vercel->updateEnvironmentVariable($project, $envId, $key, $value, $target);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;
                    
                case 'delete_env':
                    $envId = $input['envId'] ?? '';
                    
                    if (empty($envId)) {
                        throw new Exception('Environment ID is required');
                    }
                    
                    $result = $vercel->deleteEnvironmentVariable($project, $envId);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;
                    
                default:
                    throw new Exception('Unknown action');
            }
        } else {
            throw new Exception('Action parameter is required');
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
