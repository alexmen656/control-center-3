<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';
require_once 'jwt_helper.php';
require_once 'head.php';
require_once 'monaco_git_api.php';

function sendResponse($success, $data = null, $message = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function getGitHubTokenForProject($user_id, $project_name, $codespace = 'main') {
    $escaped_user_id = mysqli_real_escape_string($GLOBALS['con'], $user_id);
    $escaped_project = mysqli_real_escape_string($GLOBALS['con'], $project_name);
    $escaped_codespace = mysqli_real_escape_string($GLOBALS['con'], $codespace);
    
    // Zuerst müssen wir die codespace_id finden
    $codespaceQuery = "SELECT pc.id as codespace_id FROM project_codespaces pc 
                      JOIN projects p ON pc.project_id = p.projectID 
                      WHERE p.link = '$escaped_project' AND pc.slug = '$escaped_codespace' LIMIT 1";
    $codespaceResult = query($codespaceQuery);
    
    if (!$codespaceResult || mysqli_num_rows($codespaceResult) == 0) {
        return null;
    }
    
    $codespaceData = mysqli_fetch_assoc($codespaceResult);
    $codespaceId = $codespaceData['codespace_id'];
    
    // Hole GitHub Repository für diesen Codespace und Token für User
    $sql = "SELECT gt.github_token as token, cgr.repo_full_name as github_repo
            FROM control_center_github_tokens gt
            JOIN codespace_github_repos cgr ON cgr.user_id = gt.userid
            WHERE gt.userid = '$escaped_user_id' 
            AND cgr.codespace_id = '$codespaceId'
            LIMIT 1";
    
    $result = query($sql);
    return $result ? mysqli_fetch_assoc($result) : null;
}

try {
    $user_id = getUserIDFromToken();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $_GET['action'] ?? '';
    $project_name = $_GET['project'] ?? '';
    $codespace = $_GET['codespace'] ?? 'main';
    
    if (!$project_name) {
        sendResponse(false, null, 'Projektname erforderlich');
    }
    
    // Build codespace-specific project path
    $projectPath = "/data/projects/$user_id/$project_name/$codespace";
    
    $pdo = null; // Remove PDO usage
    $github_data = getGitHubTokenForProject($user_id, $project_name, $codespace);
    
    if (!$github_data || !$github_data['token'] || !$github_data['github_repo']) {
        sendResponse(false, null, 'GitHub Token oder Repository nicht gefunden');
    }
    
    // Split repo_full_name into owner/repo
    $repoParts = explode('/', $github_data['github_repo']);
    if (count($repoParts) !== 2) {
        sendResponse(false, null, 'Ungültiges Repository-Format');
    }
    
    $github_api = new GitHubAPI($github_data['token'], $repoParts[0], $repoParts[1]);
    
    switch ($action) {
        case 'create':
            if ($method !== 'POST') {
                sendResponse(false, null, 'POST Methode erforderlich');
            }
            
            $title = $input['title'] ?? '';
            $body = $input['body'] ?? '';
            $head_branch = $input['head_branch'] ?? '';
            $base_branch = $input['base_branch'] ?? 'main';
            
            if (!$title || !$head_branch) {
                sendResponse(false, null, 'Titel und Head-Branch erforderlich');
            }
            
            $result = $github_api->createPullRequest($title, $body, $head_branch, $base_branch);
            sendResponse(true, $result, 'Pull Request erfolgreich erstellt');
            break;
            
        case 'list':
            if ($method !== 'GET') {
                sendResponse(false, null, 'GET Methode erforderlich');
            }
            
            $state = $_GET['state'] ?? 'open';
            $result = $github_api->listPullRequests($state);
            sendResponse(true, $result, 'Pull Requests erfolgreich abgerufen');
            break;
            
        case 'merge':
            if ($method !== 'POST') {
                sendResponse(false, null, 'POST Methode erforderlich');
            }
            
            $pull_number = $_GET['number'] ?? '';
            $commit_title = $input['commit_title'] ?? null;
            $commit_message = $input['commit_message'] ?? null;
            $merge_method = $input['merge_method'] ?? 'merge';
            
            if (!$pull_number) {
                sendResponse(false, null, 'Pull Request Nummer erforderlich');
            }
            
            $result = $github_api->mergePullRequest($pull_number, $commit_title, $commit_message, $merge_method);
            sendResponse(true, $result, 'Pull Request erfolgreich gemergt');
            break;
            
        case 'close':
            if ($method !== 'POST') {
                sendResponse(false, null, 'POST Methode erforderlich');
            }
            
            $pull_number = $_GET['number'] ?? '';
            
            if (!$pull_number) {
                sendResponse(false, null, 'Pull Request Nummer erforderlich');
            }
            
            $result = $github_api->closePullRequest($pull_number);
            sendResponse(true, $result, 'Pull Request erfolgreich geschlossen');
            break;
            
        default:
            sendResponse(false, null, 'Ungültige Aktion');
    }
    
} catch (Exception $e) {
    sendResponse(false, null, $e->getMessage());
}
?>
