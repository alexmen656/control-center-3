<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';
require_once 'jwt_helper.php';
require_once 'monaco_git_api.php';

function getUserIDFromToken() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authorization, 'Bearer ') === 0) {
            $token = substr($authorization, 7);
            $decoded = verifyJWT($token);
            if ($decoded) {
                return $decoded['user_id'];
            }
        }
    }
    
    // Fallback für Entwicklung
    return 1;
}

function sendResponse($success, $data = null, $message = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function getPDO() {
    global $servername, $username, $password, $dbname;
    
    try {
        $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }
}

function getGitHubTokenForProject($pdo, $user_id, $project_name) {
    $stmt = $pdo->prepare("
        SELECT gt.token, pr.github_repo
        FROM github_tokens gt
        JOIN project_repo pr ON gt.user_id = pr.user_id
        WHERE gt.user_id = ? AND pr.project_name = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $project_name]);
    return $stmt->fetch();
}

try {
    $user_id = getUserIDFromToken();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $_GET['action'] ?? '';
    $project_name = $_GET['project'] ?? '';
    
    if (!$project_name) {
        sendResponse(false, null, 'Projektname erforderlich');
    }
    
    $pdo = getPDO();
    $github_data = getGitHubTokenForProject($pdo, $user_id, $project_name);
    
    if (!$github_data || !$github_data['token'] || !$github_data['github_repo']) {
        sendResponse(false, null, 'GitHub Token oder Repository nicht gefunden');
    }
    
    $github_api = new GitHubAPI($github_data['token'], $github_data['github_repo']);
    
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
