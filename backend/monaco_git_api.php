<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'head.php';

// Fallback für lokale Entwicklung
if (!function_exists('query')) {
    function query($sql) {
        global $servername, $username, $password, $dbname;
        static $connection = null;
        
        if ($connection === null) {
            $connection = new mysqli($servername, $username, $password, $dbname);
            if ($connection->connect_error) {
                die("Connection failed: " . $connection->connect_error);
            }
        }
        
        return $connection->query($sql);
    }
}

function getUserIDFromToken() {
    global $jwt_secret;
    
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        return 'dev_user'; // Development fallback
    }
    
    $token = $authHeader;
    $userData = SimpleJWT::verify($token, $jwt_secret);
    if (!$userData || !isset($userData['sub'])) {
        return 'dev_user';
    }
    
    return $userData['sub'];
}

function getProjectPath($project, $userID) {
    return __DIR__ . '/../data/projects/' . $userID . '/' . $project;
}

function getGitHubCredentials($project, $userID) {
    try {
        // Get GitHub token for user
        $tokenQuery = "SELECT github_token FROM control_center_github_tokens WHERE userID = '$userID' LIMIT 1";
        $tokenResult = query($tokenQuery);
        
        if (!$tokenResult || mysqli_num_rows($tokenResult) == 0) {
            return null; // No GitHub integration
        }
        
        $tokenData = mysqli_fetch_assoc($tokenResult);
        
        // Get repository info for project
        $repoQuery = "SELECT repo_full_name FROM control_center_project_repos WHERE project = '$project' LIMIT 1";
        $repoResult = query($repoQuery);
        
        if (!$repoResult || mysqli_num_rows($repoResult) == 0) {
            return null; // No repository linked
        }
        
        $repoData = mysqli_fetch_assoc($repoResult);
        
        // Split repo_full_name into owner/repo
        $repoParts = explode('/', $repoData['repo_full_name']);
        if (count($repoParts) !== 2) {
            return null;
        }
        
        return [
            'token' => $tokenData['github_token'],
            'owner' => $repoParts[0],
            'repo' => $repoParts[1],
            'repo_full_name' => $repoData['repo_full_name']
        ];
    } catch (Exception $e) {
        error_log("GitHub credentials error: " . $e->getMessage());
        return null;
    }
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
        // Handle Git API endpoints differently
        if (strpos($endpoint, 'git/') === 0) {
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/" . $endpoint;
        } else {
            $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/" . ltrim($endpoint, '/');
        }
        
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
            throw new Exception("GitHub API request failed: Unable to connect to $url");
        }
        
        // Get HTTP response code from headers
        $http_response_header = $http_response_header ?? [];
        $httpCode = 200;
        if (!empty($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("GitHub API Error: HTTP {$httpCode} for URL: $url, Response: $response");
            throw new Exception("GitHub API request failed: HTTP {$httpCode}");
        }
    }
    
    public function getBranches() {
        return $this->makeRequest('branches');
    }
    
    public function getCommits($per_page = 10) {
        return $this->makeRequest("commits?per_page={$per_page}");
    }
    
    public function getRepoContents($path = '') {
        return $this->makeRequest("contents/" . $path);
    }
    
    public function createOrUpdateFile($path, $content, $message, $sha = null) {
        $data = [
            'message' => $message,
            'content' => base64_encode($content)
        ];
        
        if ($sha) {
            $data['sha'] = $sha;
        }
        
        return $this->makeRequest("contents/" . $path, 'PUT', $data);
    }
    
    public function getFileContent($path) {
        try {
            $response = $this->makeRequest("contents/" . $path);
            if (isset($response['content']) && $response['encoding'] === 'base64') {
                return [
                    'content' => base64_decode($response['content']),
                    'sha' => $response['sha'],
                    'size' => $response['size']
                ];
            }
            return null;
        } catch (Exception $e) {
            return null; // File doesn't exist
        }
    }

    // Pull Request Funktionen
    public function createPullRequest($title, $body, $head_branch, $base_branch = 'main') {
        try {
            $data = [
                'title' => $title,
                'body' => $body,
                'head' => $head_branch,
                'base' => $base_branch
            ];
            
            return $this->makeRequest("pulls", $data, 'POST');
        } catch (Exception $e) {
            throw new Exception("Fehler beim Erstellen des Pull Requests: " . $e->getMessage());
        }
    }
    
    public function listPullRequests($state = 'open') {
        try {
            return $this->makeRequest("pulls?state=" . $state);
        } catch (Exception $e) {
            throw new Exception("Fehler beim Abrufen der Pull Requests: " . $e->getMessage());
        }
    }
    
    public function mergePullRequest($pull_number, $commit_title = null, $commit_message = null, $merge_method = 'merge') {
        try {
            $data = [
                'merge_method' => $merge_method
            ];
            
            if ($commit_title) {
                $data['commit_title'] = $commit_title;
            }
            
            if ($commit_message) {
                $data['commit_message'] = $commit_message;
            }
            
            return $this->makeRequest("pulls/{$pull_number}/merge", $data, 'PUT');
        } catch (Exception $e) {
            throw new Exception("Fehler beim Mergen des Pull Requests: " . $e->getMessage());
        }
    }
    
    public function closePullRequest($pull_number) {
        try {
            $data = ['state' => 'closed'];
            return $this->makeRequest("pulls/{$pull_number}", $data, 'PATCH');
        } catch (Exception $e) {
            throw new Exception("Fehler beim Schließen des Pull Requests: " . $e->getMessage());
        }
    }
    
    // Erstelle einen einzigen GitHub-Commit mit mehreren Dateien
    public function createCommitWithMultipleFiles($files, $message, $branch = 'main') {
        try {
            error_log("Creating multi-file commit with " . count($files) . " files using Git Tree API");
            
            // Schritt 1: Hole die aktuelle Branch-Referenz
            $ref = $this->makeRequest("git/refs/heads/{$branch}", 'GET');
            $latestCommitSha = $ref['object']['sha'];
            error_log("Latest commit SHA: $latestCommitSha");
            
            // Schritt 2: Hole den aktuellen Tree vom letzten Commit
            $commit = $this->makeRequest("git/commits/{$latestCommitSha}", 'GET');
            $baseTreeSha = $commit['tree']['sha'];
            error_log("Base tree SHA: $baseTreeSha");
            
            // Schritt 3: Erstelle ein neues Tree mit allen Dateien
            $treeData = [
                'base_tree' => $baseTreeSha,
                'tree' => []
            ];
            
            foreach ($files as $file) {
                error_log("Processing file for tree: " . $file['path']);
                
                // Erstelle Blob für jede Datei
                $blobData = [
                    'content' => base64_encode($file['content']),
                    'encoding' => 'base64'
                ];
                
                $blob = $this->makeRequest('git/blobs', 'POST', $blobData);
                error_log("Created blob for " . $file['path'] . ": " . $blob['sha']);
                
                // Füge zur Tree-Struktur hinzu
                $treeData['tree'][] = [
                    'path' => $file['path'],
                    'mode' => '100644', // Normal file mode
                    'type' => 'blob',
                    'sha' => $blob['sha']
                ];
            }
            
            // Erstelle das neue Tree
            $tree = $this->makeRequest('git/trees', 'POST', $treeData);
            error_log("Created new tree: " . $tree['sha']);
            
            // Schritt 4: Erstelle den Commit
            $commitData = [
                'message' => $message,
                'tree' => $tree['sha'],
                'parents' => [$latestCommitSha]
            ];
            
            $newCommit = $this->makeRequest('git/commits', 'POST', $commitData);
            error_log("Created new commit: " . $newCommit['sha']);
            
            // Schritt 5: Update die Branch-Referenz
            $updateRef = $this->makeRequest("git/refs/heads/{$branch}", 'PATCH', [
                'sha' => $newCommit['sha']
            ]);
            error_log("Updated branch reference successfully");
            
            return [
                'success' => true,
                'commit' => $newCommit,
                'files_count' => count($files)
            ];
            
        } catch (Exception $e) {
            error_log("Multi-file commit failed: " . $e->getMessage());
            throw new Exception("Fehler beim Erstellen des Multi-File-Commits: " . $e->getMessage());
        }
    }

    // Pull-Funktion um neueste Changes von GitHub zu holen
    public function pullFromGitHub($projectPath, $branch = 'main') {
        try {
            // Hole alle Dateien vom Repository
            $tree = $this->getRepositoryTree($branch);
            $pulledFiles = [];
            $errors = [];
            
            foreach ($tree as $item) {
                if ($item['type'] === 'blob') { // Das ist eine Datei
                    try {
                        $fileData = $this->getFileContent($item['path']);
                        if ($fileData) {
                            $localPath = $projectPath . '/' . $item['path'];
                            
                            // Erstelle Verzeichnis falls nötig
                            $dir = dirname($localPath);
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            
                            // Schreibe Datei
                            file_put_contents($localPath, $fileData['content']);
                            $pulledFiles[] = $item['path'];
                        }
                    } catch (Exception $e) {
                        $errors[] = "Fehler bei {$item['path']}: " . $e->getMessage();
                    }
                }
            }
            
            return [
                'success' => true,
                'pulled_files' => $pulledFiles,
                'files_count' => count($pulledFiles),
                'errors' => $errors,
                'message' => count($pulledFiles) . ' Dateien erfolgreich von GitHub geholt'
            ];
            
        } catch (Exception $e) {
            throw new Exception("Fehler beim Pull von GitHub: " . $e->getMessage());
        }
    }
    
    // Push-Funktion um lokale Changes zu GitHub zu pushen
    public function pushToGitHub($projectPath, $branch = 'main') {
        error_log("GitHubAPI::pushToGitHub called for project path: $projectPath");
        
        try {
            $pushedCommits = [];
            $errors = [];
            
            // Lese lokale Commits die noch nicht gepusht wurden
            $commitsFile = $projectPath . '/.monaco_commits.json';
            if (!file_exists($commitsFile)) {
                error_log("No commits file found at: $commitsFile");
                return [
                    'success' => true,
                    'commits_count' => 0,
                    'message' => 'Keine lokalen Commits zum Pushen gefunden'
                ];
            }
            
            $localCommits = json_decode(file_get_contents($commitsFile), true) ?: [];
            error_log("Found " . count($localCommits) . " local commits");
            
            // Hole aktuelle Remote Commits
            $remoteCommits = $this->getCommits(100);
            $remoteHashes = array_column($remoteCommits, 'sha');
            
            // Finde Commits die noch nicht auf GitHub sind
            $commitsToPush = [];
            foreach ($localCommits as $commit) {
                if (!in_array($commit['hash'], $remoteHashes)) {
                    $commitsToPush[] = $commit;
                }
            }
            
            error_log("Found " . count($commitsToPush) . " commits to push");
            
            if (empty($commitsToPush)) {
                return [
                    'success' => true,
                    'commits_count' => 0,
                    'message' => 'Alle Commits sind bereits auf GitHub'
                ];
            }
            
            // Pushe jeden Commit zu GitHub
            foreach ($commitsToPush as $commit) {
                error_log("Processing commit: " . $commit['hash'] . " with message: " . $commit['message']);
                try {
                    // Validiere dass files Array existiert
                    if (!isset($commit['files']) || !is_array($commit['files'])) {
                        // Fallback: Alle Dateien im Projekt verwenden
                        $allFiles = getProjectFiles($projectPath);
                        $commit['files'] = [];
                        foreach ($allFiles as $file) {
                            $relativePath = str_replace($projectPath . '/', '', $file);
                            if (strpos($relativePath, '.monaco_') !== 0) { // Skip metadata files
                                $commit['files'][] = ['path' => $relativePath];
                            }
                        }
                    }
                    
                    // FIXED: Sammle alle Dateien des Commits und erstelle einen einzigen GitHub-Commit
                    $filesToCommit = [];
                    foreach ($commit['files'] as $file) {
                        $filePath = isset($file['path']) ? $file['path'] : $file;
                        $fullPath = $projectPath . '/' . $filePath;
                        
                        if (file_exists($fullPath)) {
                            $content = file_get_contents($fullPath);
                            $sha = null;
                            
                            // Hole aktuelle SHA wenn Datei bereits existiert (nicht mehr nötig für Tree API)
                            // $existingFile = $this->getFileContent($filePath);
                            // if ($existingFile) {
                            //     $sha = $existingFile['sha'];
                            // }
                            
                            $filesToCommit[] = [
                                'path' => $filePath,
                                'content' => $content,
                                'sha' => $sha
                            ];
                        }
                    }
                    
                    // Erstelle einen einzigen Commit mit allen Dateien
                    if (!empty($filesToCommit)) {
                        error_log("Creating single GitHub commit with " . count($filesToCommit) . " files");
                        $this->createCommitWithMultipleFiles($filesToCommit, $commit['message']);
                        error_log("GitHub commit created successfully");
                    }
                    
                    $pushedCommits[] = $commit;
                } catch (Exception $e) {
                    $errors[] = "Fehler bei Commit {$commit['hash']}: " . $e->getMessage();
                }
            }
            
            // Cleanup: Entferne erfolgreich gepushte Commits aus lokaler Liste
            if (!empty($pushedCommits)) {
                $this->cleanupPushedCommits($projectPath, $pushedCommits);
            }
            
            return [
                'success' => true,
                'pushed_commits' => $pushedCommits,
                'commits_count' => count($pushedCommits),
                'errors' => $errors,
                'message' => count($pushedCommits) . ' Commits erfolgreich zu GitHub gepusht'
            ];
            
        } catch (Exception $e) {
            throw new Exception("Fehler beim Push zu GitHub: " . $e->getMessage());
        }
    }
    
    // Hilfsfunktion: Entferne gepushte Commits aus lokaler Liste
    private function cleanupPushedCommits($projectPath, $pushedCommits) {
        $commitsFile = $projectPath . '/.monaco_commits.json';
        if (!file_exists($commitsFile)) {
            return;
        }
        
        $allCommits = json_decode(file_get_contents($commitsFile), true) ?: [];
        $pushedHashes = array_column($pushedCommits, 'hash');
        
        // Entferne gepushte Commits aus der Liste
        $remainingCommits = [];
        foreach ($allCommits as $commit) {
            if (!in_array($commit['hash'], $pushedHashes)) {
                $remainingCommits[] = $commit;
            }
        }
        
        // Speichere die bereinigte Liste
        file_put_contents($commitsFile, json_encode($remainingCommits, JSON_PRETTY_PRINT));
    }
    
    private function getRepositoryTree($branch = 'main') {
        try {
            $response = $this->makeRequest("git/trees/{$branch}?recursive=1");
            return $response['tree'] ?? [];
        } catch (Exception $e) {
            throw new Exception("Fehler beim Abrufen des Repository-Trees: " . $e->getMessage());
        }
    }
}

try {
    $userID = getUserIDFromToken();
    $project = $_GET['project'] ?? 'default-project';
    $action = $_GET['action'] ?? '';
    
    $projectPath = getProjectPath($project, $userID);
    
    if (!is_dir($projectPath)) {
        // Create project directory if it doesn't exist
        mkdir($projectPath, 0755, true);
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($action, $projectPath, $project, $userID);
            break;
        case 'POST':
            handlePostRequest($projectPath, $project, $userID);
            break;
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $projectPath, $project, $userID) {
    switch ($action) {
        case 'status':
            echo json_encode(getLocalChanges($projectPath));
            break;
        case 'changes':
            echo json_encode(getDetailedChanges($projectPath, $project, $userID));
            break;
        case 'commits':
            echo json_encode(getCommitHistory($project, $userID));
            break;
        case 'diff':
            $file = $_GET['file'] ?? '';
            echo json_encode(getFileDiff($projectPath, $file, $project, $userID));
            break;
        case 'branches':
            echo json_encode(getBranches($project, $userID));
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($projectPath, $project, $userID) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'stage':
            echo json_encode(stageFile($projectPath, $input['file']));
            break;
        case 'unstage':
            echo json_encode(unstageFile($projectPath, $input['file']));
            break;
        case 'commit':
            error_log("Commit request received - Project: $project, Message: " . ($input['message'] ?? 'empty') . ", Files count: " . count($input['files'] ?? []));
            echo json_encode(commitChanges($projectPath, $input['message'], $input['files'] ?? [], $project, $userID));
            break;
        case 'push':
            error_log("Push request received - Project: $project");
            echo json_encode(pushToGitHub($projectPath, $project, $userID));
            break;
        case 'pull':
            echo json_encode(pullFromGitHubToLocal($projectPath, $project, $userID));
            break;
        case 'discard':
            echo json_encode(discardChanges($projectPath, $input['file'], $project, $userID));
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getLocalChanges($projectPath) {
    // Compare local files with staged state stored in metadata
    $stagedFile = $projectPath . '/.monaco_staged.json';
    $staged = file_exists($stagedFile) ? json_decode(file_get_contents($stagedFile), true) : [];
    
    $lastCommitFile = $projectPath . '/.monaco_lastcommit.json';
    $lastCommit = file_exists($lastCommitFile) ? json_decode(file_get_contents($lastCommitFile), true) : [];
    
    $changes = [];
    
    // Get all files in project
    $files = getProjectFiles($projectPath);
    
    foreach ($files as $file) {
        $relativePath = str_replace($projectPath . '/', '', $file);
        if (strpos($relativePath, '.monaco_') === 0) continue; // Skip metadata files
        
        $currentHash = md5(file_get_contents($file));
        $lastCommitHash = $lastCommit[$relativePath] ?? null;
        $isStaged = isset($staged[$relativePath]);
        
        if (!$lastCommitHash) {
            // New file
            $changes[] = [
                'file' => $relativePath,
                'staged' => $isStaged,
                'status' => 'untracked'
            ];
        } elseif ($currentHash !== $lastCommitHash) {
            // Modified file
            $changes[] = [
                'file' => $relativePath,
                'staged' => $isStaged,
                'status' => 'modified'
            ];
        }
    }
    
    // Check for deleted files
    foreach ($lastCommit as $file => $hash) {
        if (!file_exists($projectPath . '/' . $file)) {
            $changes[] = [
                'file' => $file,
                'staged' => isset($staged[$file]),
                'status' => 'deleted'
            ];
        }
    }
    
    return [
        'success' => true,
        'changes' => $changes
    ];
}

function getDetailedChanges($projectPath, $project, $userID) {
    $localChanges = getLocalChanges($projectPath);
    $changes = $localChanges['changes'] ?? [];
    
    $staged = [];
    $unstaged = [];
    $untracked = [];
    
    foreach ($changes as $change) {
        $changeData = [
            'path' => $change['file'],
            'status' => $change['status'],
            'type' => $change['staged'] ? 'staged' : ($change['status'] === 'untracked' ? 'untracked' : 'unstaged')
        ];
        
        if ($change['staged']) {
            $staged[] = $changeData;
        } elseif ($change['status'] === 'untracked') {
            $untracked[] = $changeData;
        } else {
            $unstaged[] = $changeData;
        }
    }
    
    // Combine for frontend
    $allChanges = array_merge($staged, $unstaged, $untracked);
    
    return [
        'success' => true,
        'changes' => $allChanges,
        'detailed' => [
            'staged' => $staged,
            'unstaged' => $unstaged,
            'untracked' => $untracked
        ],
        'summary' => [
            'staged_count' => count($staged),
            'unstaged_count' => count($unstaged),
            'untracked_count' => count($untracked)
        ]
    ];
}

function getCommitHistory($project, $userID) {
    $credentials = getGitHubCredentials($project, $userID);
    
    if (!$credentials) {
        // Return local commit history if no GitHub integration
        return getLocalCommitHistory($project, $userID);
    }
    
    try {
        $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
        $commits = $github->getCommits(20);
        
        $formattedCommits = [];
        foreach ($commits as $commit) {
            $formattedCommits[] = [
                'hash' => $commit['sha'],
                'short_hash' => substr($commit['sha'], 0, 7),
                'author' => $commit['commit']['author']['name'],
                'email' => $commit['commit']['author']['email'],
                'date' => $commit['commit']['author']['date'],
                'message' => $commit['commit']['message'],
                'parents' => array_column($commit['parents'] ?? [], 'sha')
            ];
        }
        
        return [
            'success' => true,
            'commits' => $formattedCommits
        ];
    } catch (Exception $e) {
        return getLocalCommitHistory($project, $userID);
    }
}

function getLocalCommitHistory($project, $userID) {
    $projectPath = getProjectPath($project, $userID);
    $commitsFile = $projectPath . '/.monaco_commits.json';
    
    if (!file_exists($commitsFile)) {
        return [
            'success' => true,
            'commits' => []
        ];
    }
    
    $commits = json_decode(file_get_contents($commitsFile), true) ?? [];
    
    return [
        'success' => true,
        'commits' => array_slice($commits, 0, 20) // Limit to 20 commits
    ];
}

function getProjectFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

function stageFile($projectPath, $file) {
    $stagedFile = $projectPath . '/.monaco_staged.json';
    $staged = file_exists($stagedFile) ? json_decode(file_get_contents($stagedFile), true) : [];
    
    $filePath = $projectPath . '/' . $file;
    if (file_exists($filePath)) {
        $staged[$file] = md5(file_get_contents($filePath));
    } else {
        $staged[$file] = 'deleted';
    }
    
    file_put_contents($stagedFile, json_encode($staged, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'file' => $file,
        'message' => 'File staged successfully'
    ];
}

function unstageFile($projectPath, $file) {
    $stagedFile = $projectPath . '/.monaco_staged.json';
    $staged = file_exists($stagedFile) ? json_decode(file_get_contents($stagedFile), true) : [];
    
    unset($staged[$file]);
    
    file_put_contents($stagedFile, json_encode($staged, JSON_PRETTY_PRINT));
    
    return [
        'success' => true,
        'file' => $file,
        'message' => 'File unstaged successfully'
    ];
}

function commitChanges($projectPath, $message, $files, $project, $userID) {
    error_log("CommitChanges called - Message: $message, Files: " . json_encode($files));
    
    // Stage all files if none specified
    if (empty($files)) {
        $changes = getLocalChanges($projectPath);
        $files = array_column($changes['changes'], 'file');
    }
    
    // Normalize file paths to string array
    $filePaths = [];
    foreach ($files as $file) {
        if (is_array($file)) {
            // Handle both { file: "path" } and { path: "path" } structures
            $filePath = $file['file'] ?? $file['path'] ?? null;
        } else {
            $filePath = $file;
        }
        
        if ($filePath && !in_array($filePath, $filePaths)) {
            $filePaths[] = $filePath;
        }
    }
    
    error_log("Normalized file paths: " . json_encode($filePaths));
    
    // Stage all files at once (instead of one by one)
    foreach ($filePaths as $filePath) {
        stageFile($projectPath, $filePath);
    }
    
    // Create single commit with all files
    $commitHash = substr(md5($message . time() . json_encode($filePaths)), 0, 40);
    
    // Bereite die Datei-Informationen für den Commit vor
    $commitFiles = [];
    foreach ($filePaths as $filePath) {
        $commitFiles[] = ['path' => $filePath];
    }
    
    $commitData = [
        'hash' => $commitHash,
        'short_hash' => substr($commitHash, 0, 7),
        'author' => 'Control Center User',
        'email' => 'user@controlcenter.dev',
        'date' => date('c'),
        'message' => $message,
        'files' => $commitFiles,
        'parents' => []
    ];
    
    // Save to local commit history
    $commitsFile = $projectPath . '/.monaco_commits.json';
    $commits = file_exists($commitsFile) ? json_decode(file_get_contents($commitsFile), true) : [];
    array_unshift($commits, $commitData);
    file_put_contents($commitsFile, json_encode($commits, JSON_PRETTY_PRINT));
    
    // Update last commit state
    $lastCommitFile = $projectPath . '/.monaco_lastcommit.json';
    $lastCommit = [];
    $projectFiles = getProjectFiles($projectPath);
    
    foreach ($projectFiles as $file) {
        $relativePath = str_replace($projectPath . '/', '', $file);
        if (strpos($relativePath, '.monaco_') === 0) continue;
        $lastCommit[$relativePath] = md5(file_get_contents($file));
    }
    
    file_put_contents($lastCommitFile, json_encode($lastCommit, JSON_PRETTY_PRINT));
    
    // Clear staged files
    file_put_contents($projectPath . '/.monaco_staged.json', '{}');
    
    error_log("Commit completed successfully - Hash: $commitHash, Files: " . count($filePaths));
    
    return [
        'success' => true,
        'commit' => [
            'sha' => $commitHash,
            'short_sha' => substr($commitHash, 0, 7),
            'message' => $message,
            'author' => [
                'name' => 'Control Center User',
                'email' => 'user@controlcenter.dev'
            ],
            'created_at' => date('c'),
            'date' => date('c')
        ]
    ];
}

function pullFromGitHubToLocal($projectPath, $project, $userID) {
    $credentials = getGitHubCredentials($project, $userID);
    
    if (!$credentials) {
        throw new Exception('GitHub credentials not found');
    }
    
    try {
        $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
        $result = $github->pullFromGitHub($projectPath);
        
        // Update local metadata after successful pull
        $lastCommitFile = $projectPath . '/.monaco_lastcommit.json';
        $stagedFile = $projectPath . '/.monaco_staged.json';
        
        // Clear staged changes since we pulled fresh content
        file_put_contents($stagedFile, '{}');
        
        // Update last commit metadata
        $commitData = [
            'hash' => 'pulled-' . time(),
            'message' => 'Pulled from GitHub',
            'timestamp' => time(),
            'files' => $result['pulled_files']
        ];
        file_put_contents($lastCommitFile, json_encode($commitData));
        
        return $result;
        
    } catch (Exception $e) {
        throw new Exception('Pull from GitHub failed: ' . $e->getMessage());
    }
}

function pushToGitHub($projectPath, $project, $userID) {
    error_log("PushToGitHub called - Project: $project");
    
    $credentials = getGitHubCredentials($project, $userID);
    
    if (!$credentials) {
        throw new Exception('GitHub credentials not found for project');
    }
    
    try {
        $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
        $result = $github->pushToGitHub($projectPath);
        
        error_log("Push completed - Commits pushed: " . ($result['commits_count'] ?? 0));
        
        // Zusätzliche Sicherheit: Entferne auch hier erfolgreich gepushte Commits
        if ($result['success'] && !empty($result['pushed_commits'])) {
            $commitsFile = $projectPath . '/.monaco_commits.json';
            if (file_exists($commitsFile)) {
                $allCommits = json_decode(file_get_contents($commitsFile), true) ?: [];
                $pushedHashes = array_column($result['pushed_commits'], 'hash');
                
                $remainingCommits = [];
                foreach ($allCommits as $commit) {
                    if (!in_array($commit['hash'], $pushedHashes)) {
                        $remainingCommits[] = $commit;
                    }
                }
                
                file_put_contents($commitsFile, json_encode($remainingCommits, JSON_PRETTY_PRINT));
            }
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Push failed: " . $e->getMessage());
        throw new Exception('Push to GitHub failed: ' . $e->getMessage());
    }
}

function discardChanges($projectPath, $file, $project, $userID) {
    $credentials = getGitHubCredentials($project, $userID);
    
    if ($credentials) {
        // Restore from GitHub
        try {
            $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
            $fileContent = $github->getFileContent($file);
            
            if ($fileContent) {
                file_put_contents($projectPath . '/' . $file, $fileContent['content']);
                return [
                    'success' => true,
                    'file' => $file,
                    'message' => 'Changes discarded, restored from GitHub'
                ];
            }
        } catch (Exception $e) {
            // Fall through to local restore
        }
    }
    
    // Restore from last commit state
    $lastCommitFile = $projectPath . '/.monaco_lastcommit.json';
    if (file_exists($lastCommitFile)) {
        $lastCommit = json_decode(file_get_contents($lastCommitFile), true);
        if (isset($lastCommit[$file])) {
            // Would need to restore from backup - for now just delete the file
            $filePath = $projectPath . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return [
                'success' => true,
                'file' => $file,
                'message' => 'Changes discarded'
            ];
        }
    }
    
    return [
        'success' => false,
        'file' => $file,
        'message' => 'Unable to discard changes'
    ];
}

function getFileDiff($projectPath, $file, $project, $userID) {
    // Simple diff implementation
    return [
        'success' => true,
        'file' => $file,
        'diffs' => [
            'staged_diff' => 'Diff functionality in development',
            'unstaged_diff' => 'Diff functionality in development'
        ]
    ];
}

function getBranches($project, $userID) {
    $credentials = getGitHubCredentials($project, $userID);
    
    if (!$credentials) {
        return [
            'success' => true,
            'branches' => [
                ['name' => 'main', 'current' => true, 'remote' => false]
            ],
            'current' => 'main'
        ];
    }
    
    try {
        $github = new GitHubAPI($credentials['token'], $credentials['owner'], $credentials['repo']);
        $branches = $github->getBranches();
        
        $formattedBranches = [];
        $defaultBranch = 'main';
        
        foreach ($branches as $branch) {
            $formattedBranches[] = [
                'name' => $branch['name'],
                'current' => $branch['name'] === $defaultBranch,
                'remote' => false
            ];
        }
        
        return [
            'success' => true,
            'branches' => $formattedBranches,
            'current' => $defaultBranch
        ];
    } catch (Exception $e) {
        return [
            'success' => true,
            'branches' => [
                ['name' => 'main', 'current' => true, 'remote' => false]
            ],
            'current' => 'main'
        ];
    }
}
?>
