<?php
require_once 'head.php';
require_once 'git_helper.php';

function getUserIDFromToken()
{
    global $jwt_secret;

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader)) {
        return 'dev_user';
    }

    $userData = SimpleJWT::verify($authHeader, $jwt_secret);
    if (!$userData || !isset($userData['sub'])) {
        return 'dev_user';
    }

    return $userData['sub'];
}

function getProjectPath($project, $userID, $codespace = 'main')
{
    return __DIR__ . '/../data/projects/' . $userID . '/' . $project . '/' . $codespace;
}

function gitAuthor($projectPath)
{
    list(, $name) = git_exec($projectPath, ['config', 'user.name']);
    list(, $email) = git_exec($projectPath, ['config', 'user.email']);
    $name = trim($name) ?: 'Fringelo';
    $email = trim($email) ?: 'codespaces@fringelo.com';
    return ['name' => $name, 'email' => $email];
}

if (basename($_SERVER['PHP_SELF']) === 'monaco_git_api.php') {
    try {
        $userID = getUserIDFromToken();
        $project = $_GET['project'] ?? 'default-project';
        $codespace = $_GET['codespace'] ?? 'main';
        $action = $_GET['action'] ?? '';

        $projectPath = getProjectPath($project, $userID, $codespace);

        git_ensureRepo($projectPath, $project, $userID, $codespace);

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                handleGetRequest($action, $projectPath, $project, $userID, $codespace);
                break;
            case 'POST':
                handlePostRequest($projectPath, $project, $userID, $codespace);
                break;
            default:
                throw new Exception('Method not allowed');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetRequest($action, $projectPath, $project, $userID, $codespace = 'main')
{
    switch ($action) {
        case 'status':
            echo json_encode(getLocalChanges($projectPath));
            break;
        case 'changes':
            echo json_encode(getDetailedChanges($projectPath));
            break;
        case 'commits':
            echo json_encode(getCommitHistory($projectPath));
            break;
        case 'diff':
            $file = $_GET['file'] ?? '';
            echo json_encode(getFileDiff($projectPath, $file));
            break;
        case 'branches':
            echo json_encode(getBranches($projectPath));
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($projectPath, $project, $userID, $codespace = 'main')
{
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
            echo json_encode(commitChanges($projectPath, $input['message'] ?? '', $input['files'] ?? []));
            break;
        case 'push':
            echo json_encode(pushToRemote($projectPath));
            break;
        case 'pull':
            echo json_encode(pullFromRemote($projectPath));
            break;
        case 'auto_resolve_conflicts':
            $conflicts = $input['conflicts'] ?? ($_POST['conflicts'] ?? []);
            echo json_encode(autoResolveConflicts($projectPath, $conflicts));
            break;
        case 'discard':
            echo json_encode(discardChanges($projectPath, $input['file']));
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getLocalChanges($projectPath)
{
    list($code, $out) = git_exec($projectPath, [
        '-c',
        'core.quotepath=false',
        'status',
        '--porcelain',
        '--untracked-files=all'
    ]);

    $changes = [];
    if ($code === 0) {
        foreach (explode("\n", $out) as $line) {
            if (strlen($line) < 3) {
                continue;
            }
            $x = $line[0];
            $y = $line[1];
            $file = substr($line, 3);

            if (strpos($file, ' -> ') !== false) {
                $parts = explode(' -> ', $file);
                $file = end($parts);
            }
            $file = trim($file, '"');

            $staged = ($x !== ' ' && $x !== '?');

            if ($x === 'D' || $y === 'D') {
                $status = 'deleted';
            } elseif ($x === '?') {
                $status = 'untracked';
            } elseif ($x === 'A') {
                $status = 'untracked';
            } else {
                $status = 'modified';
            }

            $changes[] = [
                'file' => $file,
                'staged' => $staged,
                'status' => $status,
            ];
        }
    }

    return [
        'success' => true,
        'changes' => $changes,
    ];
}

function getDetailedChanges($projectPath)
{
    $localChanges = getLocalChanges($projectPath);
    $changes = $localChanges['changes'] ?? [];

    $staged = [];
    $unstaged = [];
    $untracked = [];

    foreach ($changes as $change) {
        $changeData = [
            'path' => $change['file'],
            'status' => $change['status'],
            'type' => $change['staged'] ? 'staged' : ($change['status'] === 'untracked' ? 'untracked' : 'unstaged'),
        ];

        if ($change['staged']) {
            $staged[] = $changeData;
        } elseif ($change['status'] === 'untracked') {
            $untracked[] = $changeData;
        } else {
            $unstaged[] = $changeData;
        }
    }

    $allChanges = array_merge($staged, $unstaged, $untracked);

    return [
        'success' => true,
        'changes' => $allChanges,
        'detailed' => [
            'staged' => $staged,
            'unstaged' => $unstaged,
            'untracked' => $untracked,
        ],
        'summary' => [
            'staged_count' => count($staged),
            'unstaged_count' => count($unstaged),
            'untracked_count' => count($untracked),
        ],
    ];
}

function stageFile($projectPath, $file)
{

    list($code, $out) = git_exec($projectPath, ['add', '--', $file]);
    if ($code !== 0) {
        return ['success' => false, 'file' => $file, 'message' => trim($out) ?: 'Failed to stage file'];
    }
    return ['success' => true, 'file' => $file, 'message' => 'File staged successfully'];
}

function unstageFile($projectPath, $file)
{
    list($code, $out) = git_exec($projectPath, ['restore', '--staged', '--', $file]);
    if ($code !== 0) {

        git_exec($projectPath, ['rm', '--cached', '--', $file]);
    }
    return ['success' => true, 'file' => $file, 'message' => 'File unstaged successfully'];
}

function commitChanges($projectPath, $message, $files)
{
    if (trim($message) === '') {
        $message = 'Update';
    }

    if (empty($files)) {
        git_exec($projectPath, ['add', '-A']);
    } else {
        foreach ($files as $file) {
            if (is_array($file)) {
                $filePath = $file['file'] ?? $file['path'] ?? null;
            } else {
                $filePath = $file;
            }
            if ($filePath) {
                git_exec($projectPath, ['add', '--', $filePath]);
            }
        }
    }

    list($diffCode) = git_exec($projectPath, ['diff', '--cached', '--quiet']);
    if ($diffCode === 0) {
        return [
            'success' => false,
            'error' => 'No changes to commit',
            'message' => 'Es gibt keine Änderungen zum Committen',
        ];
    }

    list($code, $out) = git_exec($projectPath, ['commit', '-m', $message]);
    if ($code !== 0) {
        return [
            'success' => false,
            'error' => trim($out),
            'message' => 'Commit fehlgeschlagen: ' . trim($out),
        ];
    }

    list(, $sha) = git_exec($projectPath, ['rev-parse', 'HEAD']);
    $sha = trim($sha);
    $author = gitAuthor($projectPath);

    return [
        'success' => true,
        'commit' => [
            'sha' => $sha,
            'short_sha' => substr($sha, 0, 7),
            'message' => $message,
            'author' => $author,
            'created_at' => date('c'),
            'date' => date('c'),
        ],
    ];
}

function pushToRemote($projectPath, $branch = 'main')
{

    git_exec($projectPath, ['fetch', 'origin']);

    if (git_remoteHasBranch($projectPath, $branch)) {
        list(, $countOut) = git_exec($projectPath, ['rev-list', '--count', "origin/$branch..HEAD"]);
    } else {
        list(, $countOut) = git_exec($projectPath, ['rev-list', '--count', 'HEAD']);
    }
    $count = (int) trim($countOut);

    list($code, $out) = git_exec($projectPath, ['push', 'origin', "HEAD:$branch"]);
    if ($code !== 0) {
        throw new Exception('Push to git server failed: ' . trim($out));
    }

    return [
        'success' => true,
        'pushed_commits' => [],
        'commits_count' => $count,
        'errors' => [],
        'message' => $count . ' Commits erfolgreich zum Git-Server gepusht',
    ];
}

function pullFromRemote($projectPath, $branch = 'main')
{
    git_exec($projectPath, ['fetch', 'origin']);

    if (!git_remoteHasBranch($projectPath, $branch)) {
        return [
            'success' => true,
            'pulled_files' => [],
            'files_count' => 0,
            'errors' => [],
            'message' => 'Nichts zum Pullen gefunden',
        ];
    }

    list(, $before) = git_exec($projectPath, ['rev-parse', 'HEAD']);
    $before = trim($before);

    list($code, $out) = git_exec($projectPath, ['pull', '--no-rebase', 'origin', $branch]);
    if ($code !== 0) {
        throw new Exception('Pull from git server failed: ' . trim($out));
    }

    list(, $after) = git_exec($projectPath, ['rev-parse', 'HEAD']);
    $after = trim($after);

    $pulledFiles = [];
    if ($before && $after && $before !== $after) {
        list(, $names) = git_exec($projectPath, ['diff', '--name-only', $before, $after]);
        foreach (explode("\n", trim($names)) as $n) {
            if ($n !== '') {
                $pulledFiles[] = $n;
            }
        }
    }

    return [
        'success' => true,
        'pulled_files' => $pulledFiles,
        'files_count' => count($pulledFiles),
        'errors' => [],
        'message' => count($pulledFiles) . ' Dateien erfolgreich vom Git-Server geholt',
    ];
}

function getCommitHistory($projectPath)
{
    $sep = "\x1f";
    list($code, $out) = git_exec($projectPath, [
        'log',
        '-n',
        '20',
        '--pretty=format:%H' . $sep . '%an' . $sep . '%ae' . $sep . '%aI' . $sep . '%s' . $sep . '%P',
    ]);

    if ($code !== 0 || trim($out) === '') {
        return ['success' => true, 'commits' => []];
    }

    $commits = [];
    foreach (explode("\n", $out) as $line) {
        if ($line === '') {
            continue;
        }
        $f = explode($sep, $line);
        if (count($f) < 6) {
            continue;
        }
        $commits[] = [
            'hash' => $f[0],
            'short_hash' => substr($f[0], 0, 7),
            'author' => $f[1],
            'email' => $f[2],
            'date' => $f[3],
            'message' => $f[4],
            'parents' => $f[5] === '' ? [] : explode(' ', $f[5]),
        ];
    }

    return ['success' => true, 'commits' => $commits];
}

function getBranches($projectPath)
{
    list(, $curOut) = git_exec($projectPath, ['rev-parse', '--abbrev-ref', 'HEAD']);
    $current = trim($curOut) ?: 'main';

    list($code, $out) = git_exec($projectPath, ['branch', '--format=%(refname:short)']);

    $branches = [];
    if ($code === 0) {
        foreach (explode("\n", trim($out)) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $branches[] = [
                'name' => $name,
                'current' => $name === $current,
                'remote' => false,
            ];
        }
    }

    if (empty($branches)) {
        $branches[] = ['name' => 'main', 'current' => true, 'remote' => false];
        $current = 'main';
    }

    return [
        'success' => true,
        'branches' => $branches,
        'current' => $current,
    ];
}

function getFileDiff($projectPath, $file)
{
    $filePath = $projectPath . '/' . $file;
    $currentContent = file_exists($filePath) ? file_get_contents($filePath) : '';

    list($code, $out) = git_exec($projectPath, ['show', 'HEAD:' . $file]);
    $originalContent = ($code === 0) ? $out : '';

    $diff = generateLineDiff(explode("\n", $originalContent), explode("\n", $currentContent));

    return [
        'success' => true,
        'file' => $file,
        'diff' => $diff,
        'original_content' => $originalContent,
        'current_content' => $currentContent,
    ];
}

function generateLineDiff($originalLines, $currentLines)
{
    $diff = [];
    $maxLines = max(count($originalLines), count($currentLines));

    for ($i = 0; $i < $maxLines; $i++) {
        $originalLine = isset($originalLines[$i]) ? $originalLines[$i] : null;
        $currentLine = isset($currentLines[$i]) ? $currentLines[$i] : null;

        if ($originalLine === null) {
            $diff[] = ['type' => 'added', 'lineNumber' => $i + 1, 'content' => $currentLine];
        } elseif ($currentLine === null) {
            $diff[] = ['type' => 'deleted', 'lineNumber' => $i + 1, 'content' => $originalLine];
        } elseif ($originalLine !== $currentLine) {
            $diff[] = ['type' => 'deleted', 'lineNumber' => $i + 1, 'content' => $originalLine];
            $diff[] = ['type' => 'added', 'lineNumber' => $i + 1, 'content' => $currentLine];
        } else {
            $diff[] = ['type' => 'unchanged', 'lineNumber' => $i + 1, 'content' => $currentLine];
        }
    }

    return $diff;
}

function discardChanges($projectPath, $file)
{

    list($existsCode) = git_exec($projectPath, ['cat-file', '-e', 'HEAD:' . $file]);

    if ($existsCode === 0) {
        git_exec($projectPath, ['checkout', 'HEAD', '--', $file]);
        return [
            'success' => true,
            'file' => $file,
            'message' => 'Changes discarded, restored from git server',
        ];
    }

    $filePath = $projectPath . '/' . $file;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    return [
        'success' => true,
        'file' => $file,
        'message' => 'Changes discarded',
    ];
}

function autoResolveConflicts($projectPath, $conflicts)
{
    try {
        foreach ($conflicts as $file) {
            $filePath = $projectPath . '/' . $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);

                $resolvedContent = preg_replace('/<<<<<<< HEAD\n(.*?)\n=======\n.*?\n>>>>>>> .*?\n/s', '$1', $content);
                if ($resolvedContent === $content) {
                    $resolvedContent = preg_replace('/<<<<<<< .*?\n(.*?)\n=======\n.*?\n>>>>>>> .*?\n/s', '$1', $content);
                }

                file_put_contents($filePath, $resolvedContent);
                git_exec($projectPath, ['add', '--', $file]);
            }
        }

        return [
            'success' => true,
            'message' => 'Conflicts auto-resolved successfully',
            'resolved_files' => $conflicts,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Failed to auto-resolve conflicts: ' . $e->getMessage(),
        ];
    }
}
