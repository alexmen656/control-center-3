<?php

if (!defined('GIT_BARE_ROOT')) {
    define('GIT_BARE_ROOT', '/var/www/git');
}

class GitHelper
{
    public static function git_exec($cwd, array $args)
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

    public static function git_barePath($codespaceId)
    {
        return GIT_BARE_ROOT . '/cs-' . (int) $codespaceId . '.git';
    }

    public static function git_ensureRepo($projectPath, $codespaceId)
    {
        if (!is_dir($projectPath)) {
            mkdir($projectPath, 0755, true);
        }

        $barePath = git_barePath($codespaceId);

        if (!is_dir($barePath)) {
            @mkdir(dirname($barePath), 0775, true);
            git_exec(sys_get_temp_dir(), ['init', '--bare', '-b', 'main', $barePath]);
        }

        if (!is_dir($projectPath . '/.git')) {
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
        }

        return $barePath;
    }

    public static function git_remoteHasBranch($projectPath, $branch = 'main')
    {
        list($code, $out) = git_exec($projectPath, ['ls-remote', '--heads', 'origin', $branch]);
        return $code === 0 && trim($out) !== '';
    }
}

function git_exec($cwd, array $args)
{
    return GitHelper::git_exec($cwd, $args);
}

function git_barePath($codespaceId)
{
    return GitHelper::git_barePath($codespaceId);
}

function git_ensureRepo($projectPath, $codespaceId)
{
    return GitHelper::git_ensureRepo($projectPath, $codespaceId);
}

function git_remoteHasBranch($projectPath, $branch = 'main')
{
    return GitHelper::git_remoteHasBranch($projectPath, $branch);
}
