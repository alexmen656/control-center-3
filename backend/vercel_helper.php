<?php
// vercel_helper.php - Isolated Vercel integration for codespace APIs
if (!defined('VERCEL_HELPER_LOADED')) {
    define('VERCEL_HELPER_LOADED', true);
    
    require_once 'config.php';
    require_once 'head.php';
    require_once 'project_helper.php';

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

    // Isolated function to get Vercel project for codespace
    function getVercelProjectForCodespace($userID, $project, $codespace = 'main') {
        // Zuerst müssen wir die codespace_id finden
        $codespaceQuery = "SELECT pc.id as codespace_id FROM project_codespaces pc 
                          JOIN projects p ON pc.project_id = p.projectID 
                          WHERE p.link = '$project' AND pc.slug = '$codespace' LIMIT 1";
        $codespaceResult = query($codespaceQuery);
        
        if (!$codespaceResult || mysqli_num_rows($codespaceResult) == 0) {
            return null;
        }
        
        $codespaceData = fetch_assoc($codespaceResult);
        $codespaceId = $codespaceData['codespace_id'];
        
        // Hole Vercel-Projekt für diesen Codespace
        $vercelQuery = "SELECT vercel_project_id, vercel_project_name FROM codespace_vercel_projects 
                       WHERE codespace_id = '$codespaceId' AND user_id = '$userID' LIMIT 1";
        $vercelResult = query($vercelQuery);
        
        if (!$vercelResult || mysqli_num_rows($vercelResult) == 0) {
            return null;
        }
        
        return fetch_assoc($vercelResult);
    }

    // Complete VercelAPI class for all Vercel functionality
    class VercelAPIHelper {
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
        
        // Deployment methods
        public function getDeployments($projectName = null, $limit = 10) {
            $endpoint = '/deployments';
            $params = ['limit' => $limit];
            
            if ($projectName) {
                $params['projectId'] = $projectName;
            }
            
            $queryString = http_build_query($params);
            return $this->makeRequest($endpoint . '?' . $queryString);
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
        
        // Project methods
        public function getProjects() {
            return $this->makeRequest('/projects');
        }
        
        public function getProject($projectId) {
            return $this->makeRequest("/projects/{$projectId}");
        }
        
        // Environment variable methods
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
            
            return $this->makeRequest("/projects/{$projectId}/env/{$envId}", 'PATCH', $data, 'v9');
        }
        
        public function deleteEnvironmentVariable($projectId, $envId) {
            return $this->makeRequest("/projects/{$projectId}/env/{$envId}", 'DELETE', null, 'v9');
        }
        
        // Domain methods
        public function getDomains($projectId) {
            return $this->makeRequest("/projects/{$projectId}/domains", 'GET', null, 'v9');
        }
        
        public function addDomain($projectId, $domain) {
            $data = [
                'name' => $domain
            ];
            
            return $this->makeRequest("/projects/{$projectId}/domains", 'POST', $data, 'v9');
        }
        
        public function removeDomain($projectId, $domain) {
            return $this->makeRequest("/projects/{$projectId}/domains/{$domain}", 'DELETE', null, 'v9');
        }
        
        public function verifyDomain($projectId, $domain) {
            return $this->makeRequest("/projects/{$projectId}/domains/{$domain}/verify", 'POST', null, 'v9');
        }
    }
}

class VercelHelper {
    private $vercelAPI;
    private $userID;
    
    public function __construct($userID) {
        $this->userID = $userID;
        $credentials = getVercelCredentials($userID);
        $this->vercelAPI = new VercelAPIHelper($credentials['token']);
    }
    
    /**
     * Get the VercelAPI instance for direct access
     */
    public function getVercelAPI() {
        return $this->vercelAPI;
    }
    
    /**
     * Get Vercel project ID for a codespace
     */
    private function getVercelProjectForCodespace($project, $codespace) {
        return getVercelProjectForCodespace($this->userID, $project, $codespace);
    }
    
    /**
     * Get the appropriate Vercel project ID and name for operations
     */
    private function getVercelProjectInfo($project, $codespace) {
        $codespaceVercelProject = $this->getVercelProjectForCodespace($project, $codespace);
        
        return [
            'id' => $codespaceVercelProject ? $codespaceVercelProject['vercel_project_id'] : $project,
            'name' => $codespaceVercelProject ? $codespaceVercelProject['vercel_project_name'] : $project
        ];
    }
    
    // Deployment methods
    public function getDeployments($project, $codespace = 'main', $limit = 10) {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->getDeployments($projectInfo['name'], $limit);
    }
    
    public function createDeployment($project, $codespace = 'main', $files = [], $gitSource = null) {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->createDeployment($projectInfo['name'], $files, $gitSource);
    }
    
    public function getDeploymentStatus($deploymentId) {
        return $this->vercelAPI->getDeploymentStatus($deploymentId);
    }
    
    // Project methods
    public function getProjects() {
        return $this->vercelAPI->getProjects();
    }
    
    public function getProject($project, $codespace = 'main') {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->getProject($projectInfo['name']);
    }
    
    // Environment variable methods
    public function getEnvironmentVariables($project, $codespace = 'main') {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->getEnvironmentVariables($projectInfo['id']);
    }
    
    public function getEnvironmentVariablesWithValues($project, $codespace = 'main') {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->getEnvironmentVariablesWithValues($projectInfo['id']);
    }
    
    public function createEnvironmentVariable($project, $codespace, $key, $value, $target = ['production', 'preview', 'development']) {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->createEnvironmentVariable($projectInfo['id'], $key, $value, $target);
    }
    
    public function updateEnvironmentVariable($project, $codespace, $envId, $key, $value, $target = ['production', 'preview', 'development']) {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->updateEnvironmentVariable($projectInfo['id'], $envId, $key, $value, $target);
    }
    
    public function deleteEnvironmentVariable($project, $codespace, $envId) {
        $projectInfo = $this->getVercelProjectInfo($project, $codespace);
        return $this->vercelAPI->deleteEnvironmentVariable($projectInfo['id'], $envId);
    }
    
    /**
     * Set API key as environment variable in Vercel for a codespace
     */
    public function setAPIKeyEnvironmentVariable($project, $codespace, $apiSlug, $apiKey) {
        try {
            $vercelProject = $this->getVercelProjectForCodespace($project, $codespace);
            
            if (!$vercelProject) {
                throw new Exception("No Vercel project connected for this codespace");
            }
            
            // Create environment variable name: APINAME_API_KEY (for main codespace) or APINAME_CODESPACE_API_KEY
            $envVarName = strtoupper($apiSlug) . '_API_KEY';
            if ($codespace !== 'main') {
                $envVarName = strtoupper($apiSlug) . '_' . strtoupper($codespace) . '_API_KEY';
            }
            
            // Check if environment variable already exists
            $existingEnvVars = $this->vercelAPI->getEnvironmentVariables($vercelProject['vercel_project_id']);
            $existingEnvId = null;
            
            if (isset($existingEnvVars['envs'])) {
                foreach ($existingEnvVars['envs'] as $envVar) {
                    if ($envVar['key'] === $envVarName) {
                        $existingEnvId = $envVar['id'];
                        break;
                    }
                }
            }
            
            if ($existingEnvId) {
                // Update existing environment variable
                $result = $this->vercelAPI->updateEnvironmentVariable(
                    $vercelProject['vercel_project_id'],
                    $existingEnvId,
                    $envVarName,
                    $apiKey,
                    ['production', 'preview', 'development']
                );
            } else {
                // Create new environment variable
                $result = $this->vercelAPI->createEnvironmentVariable(
                    $vercelProject['vercel_project_id'],
                    $envVarName,
                    $apiKey,
                    ['production', 'preview', 'development']
                );
            }
            
            return [
                'success' => true,
                'env_var_name' => $envVarName,
                'action' => $existingEnvId ? 'updated' : 'created'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove API key environment variable from Vercel for a codespace
     */
    public function removeAPIKeyEnvironmentVariable($project, $codespace, $apiSlug) {
        try {
            $vercelProject = $this->getVercelProjectForCodespace($project, $codespace);
            
            if (!$vercelProject) {
                throw new Exception("No Vercel project connected for this codespace");
            }
            
            // Create environment variable name: APINAME_API_KEY (for main codespace) or APINAME_CODESPACE_API_KEY
            $envVarName = strtoupper($apiSlug) . '_API_KEY';
            if ($codespace !== 'main') {
                $envVarName = strtoupper($apiSlug) . '_' . strtoupper($codespace) . '_API_KEY';
            }
            
            // Find environment variable to delete
            $existingEnvVars = $this->vercelAPI->getEnvironmentVariables($vercelProject['vercel_project_id']);
            $envIdToDelete = null;
            
            if (isset($existingEnvVars['envs'])) {
                foreach ($existingEnvVars['envs'] as $envVar) {
                    if ($envVar['key'] === $envVarName) {
                        $envIdToDelete = $envVar['id'];
                        break;
                    }
                }
            }
            
            if ($envIdToDelete) {
                $result = $this->vercelAPI->deleteEnvironmentVariable(
                    $vercelProject['vercel_project_id'],
                    $envIdToDelete
                );
                
                return [
                    'success' => true,
                    'env_var_name' => $envVarName,
                    'action' => 'deleted'
                ];
            } else {
                return [
                    'success' => true,
                    'env_var_name' => $envVarName,
                    'action' => 'not_found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync all active API keys for a codespace to Vercel
     */
    public function syncCodespaceAPIKeys($project, $codespace) {
        try {
            $projectID = getProjectID($project);
            
            // Get codespace ID
            $codespaceResult = query("SELECT id FROM project_codespaces WHERE project_id='$projectID' AND slug='" . escape_string($codespace) . "' LIMIT 1");
            if (mysqli_num_rows($codespaceResult) === 0) {
                throw new Exception("Codespace not found");
            }
            $codespaceData = fetch_assoc($codespaceResult);
            $codespaceId = $codespaceData['id'];
            
            // Get all active APIs for this codespace
            $activeAPIs = query("
                SELECT 
                    ca.slug,
                    pas.api_key
                FROM codespace_api_activations caa
                JOIN project_api_subscriptions pas ON caa.subscription_id = pas.id
                JOIN cms_apis ca ON pas.api_id = ca.id
                WHERE caa.codespace_id = '$codespaceId' AND caa.is_active = 1 AND pas.is_enabled = 1
            ");
            
            $results = [];
            foreach ($activeAPIs as $api) {
                $result = $this->setAPIKeyEnvironmentVariable($project, $codespace, $api['slug'], $api['api_key']);
                $results[] = [
                    'api_slug' => $api['slug'],
                    'result' => $result
                ];
            }
            
            return [
                'success' => true,
                'synced_apis' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Domain methods
    public function addDomainToProject($project, $codespace, $domain) {
        try {
            $projectInfo = $this->getVercelProjectInfo($project, $codespace);
            return $this->vercelAPI->addDomain($projectInfo['id'], $domain);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function removeDomainFromProject($project, $codespace, $domain) {
        try {
            $projectInfo = $this->getVercelProjectInfo($project, $codespace);
            return $this->vercelAPI->removeDomain($projectInfo['id'], $domain);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getProjectDomains($project, $codespace) {
        try {
            $projectInfo = $this->getVercelProjectInfo($project, $codespace);
            return $this->vercelAPI->getDomains($projectInfo['id']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
