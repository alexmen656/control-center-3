<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/functions.php';

if (!defined('DEPLOY_ROOT')) {
    define('DEPLOY_ROOT', '/var/www/deploys');
}
if (!defined('DEPLOY_BUILD_TMP')) {
    define('DEPLOY_BUILD_TMP', '/var/www/deploy-tmp');
}
if (!defined('DEPLOY_LOG_ROOT')) {
    define('DEPLOY_LOG_ROOT', '/var/www/deploy-logs');
}
if (!defined('DEPLOY_GIT_ROOT')) {
    define('DEPLOY_GIT_ROOT', '/var/www/git');
}
if (!defined('DEPLOY_APPS_DOMAIN')) {
    define('DEPLOY_APPS_DOMAIN', 'apps.fringelo.com');
}
if (!defined('DEPLOY_PORT_BASE')) {
    define('DEPLOY_PORT_BASE', 21000);
}

function deploy_encryption_key()
{
    global $deploy_encryption_key, $jwt_secret;
    if (!empty($deploy_encryption_key) && strlen($deploy_encryption_key) === 64) {
        return hex2bin($deploy_encryption_key);
    }
    return hash('sha256', 'fringelo-deploy:' . $jwt_secret, true);
}

function deploy_encrypt($plain)
{
    $key = deploy_encryption_key();
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function deploy_decrypt($stored)
{
    $key = deploy_encryption_key();
    $raw = base64_decode($stored);

    if ($raw === false || strlen($raw) < 28) {
        return '';
    }

    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? '' : $plain;
}

function deploy_slug($codespaceId)
{
    return 'cs-' . (int) $codespaceId;
}

function deploy_url($codespaceId)
{
    return 'https://' . deploy_slug($codespaceId) . '.' . DEPLOY_APPS_DOMAIN;
}

function deploy_host($codespaceId)
{
    return deploy_slug($codespaceId) . '.' . DEPLOY_APPS_DOMAIN;
}

function deploy_dir($codespaceId)
{
    return DEPLOY_ROOT . '/' . deploy_slug($codespaceId);
}

function deploy_release_dir($codespaceId, $deploymentId)
{
    return deploy_dir($codespaceId) . '/releases/' . (int) $deploymentId;
}

function deploy_current_link($codespaceId)
{
    return deploy_dir($codespaceId) . '/current';
}

function deploy_bare_repo($codespaceId)
{
    return DEPLOY_GIT_ROOT . '/' . deploy_slug($codespaceId) . '.git';
}

function deploy_internal_port($codespaceId)
{
    return DEPLOY_PORT_BASE + (int) $codespaceId;
}

function deploy_resolve_codespace($project, $codespace)
{
    $p = escape_string($project);
    $c = escape_string($codespace);
    $res = query("SELECT pc.id, pc.slug, pc.template, pc.project_id, p.link AS project_link
                  FROM project_codespaces pc
                  JOIN projects p ON pc.project_id = p.projectID
                  WHERE p.link = '$p' AND pc.slug = '$c' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

function deploy_get_config($codespaceId)
{
    $id = (int) $codespaceId;
    $res = query("SELECT * FROM codespace_deploy_config WHERE codespace_id = '$id' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

function deploy_save_config($codespaceId, $cfg)
{
    $id = (int) $codespaceId;
    $framework = escape_string($cfg['framework'] ?? '');
    $install = escape_string($cfg['install_cmd'] ?? '');
    $build = escape_string($cfg['build_cmd'] ?? '');
    $output = escape_string($cfg['output_dir'] ?? '');
    $runtime = ($cfg['runtime'] ?? 'static') === 'node' ? 'node' : 'static';
    $start = escape_string($cfg['start_cmd'] ?? '');
    $node = escape_string($cfg['node_version'] ?? '22');

    $existing = deploy_get_config($id);
    if ($existing) {
        query("UPDATE codespace_deploy_config SET
                framework='$framework', install_cmd='$install', build_cmd='$build',
                output_dir='$output', runtime='$runtime', start_cmd='$start', node_version='$node'
                WHERE codespace_id='$id'");
    } else {
        query("INSERT INTO codespace_deploy_config
                (codespace_id, framework, install_cmd, build_cmd, output_dir, runtime, start_cmd, node_version)
                VALUES ('$id', '$framework', '$install', '$build', '$output', '$runtime', '$start', '$node')");
    }
    return deploy_get_config($id);
}

function deploy_create($codespaceId, $commitSha, $runtime)
{
    $id = (int) $codespaceId;
    $commit = escape_string($commitSha);
    $rt = $runtime === 'node' ? 'node' : 'static';
    query("INSERT INTO deployments (codespace_id, commit_sha, status, runtime)
           VALUES ('$id', '$commit', 'queued', '$rt')");
    return mysqli_insert_id($GLOBALS['con']);
}

function deploy_set_status($deploymentId, $status, $extra = [])
{
    $id = (int) $deploymentId;
    $s = escape_string($status);
    $sets = ["status='$s'"];
    if (isset($extra['url'])) {
        $sets[] = "url='" . escape_string($extra['url']) . "'";
    }
    if (isset($extra['internal_port'])) {
        $sets[] = "internal_port='" . (int) $extra['internal_port'] . "'";
    }
    if (isset($extra['runtime'])) {
        $sets[] = "runtime='" . ($extra['runtime'] === 'node' ? 'node' : 'static') . "'";
    }
    if (isset($extra['build_log'])) {
        $sets[] = "build_log='" . escape_string($extra['build_log']) . "'";
    }
    if (isset($extra['error_msg'])) {
        $sets[] = "error_msg='" . escape_string($extra['error_msg']) . "'";
    }
    if ($status === 'ready') {
        $sets[] = "ready_at=CURRENT_TIMESTAMP";
    }
    query("UPDATE deployments SET " . implode(', ', $sets) . " WHERE id='$id'");
}

function deploy_get($deploymentId)
{
    $id = (int) $deploymentId;
    $res = query("SELECT * FROM deployments WHERE id='$id' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

function deploy_status_to_ready_state($status)
{
    switch ($status) {
        case 'ready':
            return 'READY';
        case 'building':
            return 'BUILDING';
        case 'queued':
            return 'QUEUED';
        case 'canceled':
            return 'CANCELED';
        default:
            return 'ERROR';
    }
}

function deploy_log_sig($deploymentId)
{
    global $jwt_secret;
    return substr(hash_hmac('sha256', 'deploylog:' . (int) $deploymentId, $jwt_secret), 0, 32);
}

function deploy_list_for_frontend($codespaceId, $limit = 20)
{
    $id = (int) $codespaceId;
    $limit = (int) $limit;
    $res = query("SELECT * FROM deployments WHERE codespace_id='$id' ORDER BY id DESC LIMIT $limit");
    $items = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $host = $row['url'] ? preg_replace('#^https?://#', '', $row['url']) : '';
            $items[] = [
                'uid' => (string) $row['id'],
                'url' => $host,
                'readyState' => deploy_status_to_ready_state($row['status']),
                'meta' => ['githubCommitSha' => $row['commit_sha']],
                'created' => strtotime($row['created_at']) * 1000,
                'inspectorUrl' => 'https://api.fringelo.com/deploy_logs.php?deployment=' . $row['id'] . '&sig=' . deploy_log_sig($row['id']),
            ];
        }
    }
    return ['deployments' => $items];
}

function deploy_env_vars($codespaceId, $target)
{
    $id = (int) $codespaceId;
    $res = query("SELECT var_key, value_encrypted, target FROM codespace_env_vars WHERE codespace_id='$id'");
    $out = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if ($row['target'] === 'both' || $row['target'] === $target) {
                $out[$row['var_key']] = deploy_decrypt($row['value_encrypted']);
            }
        }
    }
    return $out;
}

