<?php

if (!defined('GIT_BARE_ROOT')) {
    define('GIT_BARE_ROOT', '/var/www/git');
}

function git_exec($cwd, array $args)
{
    $parts = ['git'];
    foreach ($args as $a) {
        $parts[] = escapeshellarg($a);
    }
    $cmd = 'cd ' . escapeshellarg($cwd) . ' && ' . implode(' ', $parts) . ' 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    return [$code, implode("\n", $out)];
}

function git_codespaceId($project, $codespace)
{
    $p = escape_string($project);
    $c = escape_string($codespace);
    $res = query("SELECT pc.id FROM project_codespaces pc
                  JOIN projects p ON pc.project_id = p.projectID
                  WHERE p.link='$p' AND pc.slug='$c' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return $row['id'];
    }
    return null;
}

function git_barePath($project, $userID, $codespace)
{
    $id = git_codespaceId($project, $codespace);
    if ($id !== null) {
        $name = 'cs-' . $id;
    } else {

        $name = 'cs-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $userID . '-' . $project . '-' . $codespace);
    }
    return GIT_BARE_ROOT . '/' . $name . '.git';
}

function git_writeGitignore($projectPath)
{
    $gi = $projectPath . '/.gitignore';
    $needed = [
        '.monaco_commits.json',
        '.monaco_staged.json',
        '.monaco_lastcommit.json',
        '.monaco_git',
        '.monaco_initialized',
    ];

    $existing = file_exists($gi) ? file_get_contents($gi) : '';
    $lines = $existing === '' ? [] : explode("\n", rtrim($existing, "\n"));
    $changed = false;

    foreach ($needed as $n) {
        if (!in_array($n, $lines, true)) {
            $lines[] = $n;
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($gi, implode("\n", $lines) . "\n");
    }
}

function git_ensureRepo($projectPath, $project, $userID, $codespace)
{
    if (!is_dir($projectPath)) {
        mkdir($projectPath, 0755, true);
    }

    $barePath = git_barePath($project, $userID, $codespace);

    if (!is_dir($barePath)) {
        @mkdir(dirname($barePath), 0775, true);
        git_exec(sys_get_temp_dir(), ['init', '--bare', '-b', 'main', $barePath]);
    }

    if (!is_dir($projectPath . '/.git')) {
        git_writeGitignore($projectPath);
        git_exec($projectPath, ['init', '-b', 'main']);
        git_exec($projectPath, ['remote', 'add', 'origin', $barePath]);
        git_exec($projectPath, ['add', '-A']);
        git_exec($projectPath, ['commit', '-m', 'Initial commit', '--allow-empty']);
        git_exec($projectPath, ['push', '-u', 'origin', 'main']);
    } else {
        list($rc, $ru) = git_exec($projectPath, ['remote', 'get-url', 'origin']);

        if ($rc !== 0) {
            git_exec($projectPath, ['remote', 'add', 'origin', $barePath]);
        } elseif (trim($ru) !== $barePath) {
            git_exec($projectPath, ['remote', 'set-url', 'origin', $barePath]);
        }
        
        git_writeGitignore($projectPath);
    }

    return $barePath;
}

function git_remoteHasBranch($projectPath, $branch = 'main')
{
    list($code, $out) = git_exec($projectPath, ['ls-remote', '--heads', 'origin', $branch]);
    return $code === 0 && trim($out) !== '';
}
