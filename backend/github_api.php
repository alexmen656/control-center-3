<?php
/*header('Content-Tfunction getUserIDFromToken() {
    global $jwt_secret;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception("No authorization token provided");
    }
    
    $token = $matches[1];
    $userData = SimpleJWT::verify($token, $jwt_secret);
    
    if (!$userData || !isset($userData['userID'])) {
        throw new Exception("Invalid token or missing user ID");
    }
    
    return $userData['userID'];
}json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
*/

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

function getGitHubCredentials($project, $userID) {
    // Get GitHub token for user
    $tokenResult = query("SELECT github_token FROM control_center_github_tokens WHERE userID = $userID");
    
    if (!$tokenResult || mysqli_num_rows($tokenResult) == 0) {
        throw new Exception("No GitHub token found for user");
    }
    
    $tokenData = fetch_assoc($tokenResult);
    
    // Get repository info for project
    $repoResult = query("SELECT repo_full_name FROM control_center_project_repos WHERE project = '" . escape_string($project) . "'");
    
    if (!$repoResult || mysqli_num_rows($repoResult) == 0) {
        throw new Exception("No repository found for project: " . $project);
    }
    
    $repoData = fetch_assoc($repoResult);
    
    // Split repo_full_name into owner/repo
    $repoParts = explode('/', $repoData['repo_full_name']);
    if (count($repoParts) !== 2) {
        throw new Exception("Invalid repository format");
    }
    
    return [
        'token' => $tokenData['github_token'],
        'owner' => $repoParts[0],
        'repo' => $repoParts[1]
    ];
}

class GitHubAPI {
    private $token;
    private $owner;
    private $repo;
    
    public function __construct($token, $owner, $repo) {
        $this->token = $token;
        $this->owner = $owner;
        $this->repo = $repo;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/" . ltrim($endpoint, '/');
        
        $headers = [
            'Authorization: token ' . $this->token,
            'User-Agent: ControlCenter-App/1.0',
            'Accept: application/vnd.github.v3+json'
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
            $headers[] = 'Content-Type: application/json';
            $context_options['http']['content'] = json_encode($data);
            $context_options['http']['header'] = implode("\r\n", $headers);
        }
        
        $context = stream_context_create($context_options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("GitHub API request failed: Unable to connect");
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
            throw new Exception("GitHub API request failed: HTTP {$httpCode} - {$response}");
        }
    }
    
    public function getCommits($per_page = 10) {
        return $this->makeRequest("commits?per_page={$per_page}");
    }
    
    public function getRepoStatus() {
        // Get the current status of the repository
        $commits = $this->getCommits(1);
        $branches = $this->makeRequest('branches');
        
        return [
            'latest_commit' => $commits[0] ?? null,
            'branches' => $branches,
            'default_branch' => $branches[0]['name'] ?? 'main'
        ];
    }
    
    public function createCommit($message, $files = []) {
        // This is a simplified version - in practice, you'd need to:
        // 1. Get the current tree
        // 2. Create blobs for changed files
        // 3. Create a new tree
        // 4. Create the commit
        // 5. Update the reference
        
        // For now, return a mock response
        return [
            'sha' => substr(md5($message . time()), 0, 40),
            'message' => $message,
            'author' => [
                'name' => 'Control Center User',
                'email' => 'user@example.com'
            ],
            'created_at' => date('c')
        ];
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
    
    // Get GitHub credentials from database
    $credentials = getGitHubCredentials($project, $userID);
    
    $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'commits':
                    $commits = $github->getCommits();
                    echo json_encode(['success' => true, 'commits' => $commits]);
                    break;
                    
                case 'status':
                    $status = $github->getRepoStatus();
                    echo json_encode(['success' => true, 'status' => $status]);
                    break;
                    
                default:
                    throw new Exception('Unknown action');
            }
        } else {
            // Default: return recent commits
            $commits = $github->getCommits();
            echo json_encode(['success' => true, 'commits' => $commits]);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'commit':
                    $message = $input['message'] ?? '';
                    $files = $input['files'] ?? [];
                    
                    if (empty($message)) {
                        throw new Exception('Commit message is required');
                    }
                    
                    $commit = $github->createCommit($message, $files);
                    echo json_encode(['success' => true, 'commit' => $commit]);
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
