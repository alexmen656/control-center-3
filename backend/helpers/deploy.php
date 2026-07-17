<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/.././helpers/db_connection.php';
require_once __DIR__ . '/../functions.php';

if (!defined('DEPLOY_ROOT')) {
    define('DEPLOY_ROOT', '/var/www/deploys');
}
if (!defined('DEPLOY_BUILD_TMP')) {
    define('DEPLOY_BUILD_TMP', '/var/www/deploy-tmp');
}
if (!defined('DEPLOY_LOG_ROOT')) {
    define('DEPLOY_LOG_ROOT', '/var/www/deploy-logs');
}
if (!defined('DEPLOY_RUNTIME_LOG_ROOT')) {
    define('DEPLOY_RUNTIME_LOG_ROOT', '/var/www/runtime-logs');
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

class DeployHelper
{
    public static function deploy_encryption_key()
    {
        global $deploy_encryption_key, $jwt_secret;
        if (!empty($deploy_encryption_key) && strlen($deploy_encryption_key) === 64) {
            return hex2bin($deploy_encryption_key);
        }
        return hash('sha256', 'fringelo-deploy:' . $jwt_secret, true);
    }

    public static function deploy_encrypt($plain)
    {
        $key = deploy_encryption_key();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $cipher);
    }

    public static function deploy_decrypt($stored)
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

    public static function deploy_slug($codespaceId)
    {
        return 'cs-' . (int) $codespaceId;
    }

    public static function deploy_url($codespaceId)
    {
        return 'https://' . deploy_slug($codespaceId) . '.' . DEPLOY_APPS_DOMAIN;
    }

    public static function deploy_host($codespaceId)
    {
        return deploy_slug($codespaceId) . '.' . DEPLOY_APPS_DOMAIN;
    }

    public static function deploy_dir($codespaceId)
    {
        return DEPLOY_ROOT . '/' . deploy_slug($codespaceId);
    }

    public static function deploy_release_dir($codespaceId, $deploymentId)
    {
        return deploy_dir($codespaceId) . '/releases/' . (int) $deploymentId;
    }

    public static function deploy_current_link($codespaceId)
    {
        return deploy_dir($codespaceId) . '/current';
    }

    public static function deploy_bare_repo($codespaceId)
    {
        return DEPLOY_GIT_ROOT . '/' . deploy_slug($codespaceId) . '.git';
    }

    public static function deploy_internal_port($codespaceId)
    {
        return DEPLOY_PORT_BASE + (int) $codespaceId;
    }

    public static function deploy_resolve_codespace($project, $codespace)
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

    public static function deploy_get_config($codespaceId)
    {
        $id = (int) $codespaceId;
        $res = query("SELECT * FROM codespace_deploy_config WHERE codespace_id = '$id' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    public static function deploy_save_config($codespaceId, $cfg)
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

    public static function deploy_create($codespaceId, $commitSha, $runtime)
    {
        $id = (int) $codespaceId;
        $commit = escape_string($commitSha);
        $rt = $runtime === 'node' ? 'node' : 'static';
        query("INSERT INTO deployments (codespace_id, commit_sha, status, runtime)
               VALUES ('$id', '$commit', 'queued', '$rt')");
        return mysqli_insert_id($GLOBALS['con']);
    }

    public static function deploy_set_status($deploymentId, $status, $extra = [])
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

    public static function deploy_get($deploymentId)
    {
        $id = (int) $deploymentId;
        $res = query("SELECT * FROM deployments WHERE id='$id' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    public static function deploy_status_to_ready_state($status)
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

    public static function deploy_log_sig($deploymentId)
    {
        global $jwt_secret;
        return substr(hash_hmac('sha256', 'deploylog:' . (int) $deploymentId, $jwt_secret), 0, 32);
    }

    public static function deploy_list_for_frontend($codespaceId, $limit = 20)
    {
        $id = (int) $codespaceId;
        $limit = (int) $limit;
        $res = query("SELECT * FROM deployments WHERE codespace_id='$id' ORDER BY id DESC LIMIT $limit");

        $domains = [];
        $domainRows = query("SELECT domain, status FROM codespace_domains WHERE codespace_id='$id'");
        if ($domainRows) {
            while ($dr = mysqli_fetch_assoc($domainRows)) {
                $domains[] = ['domain' => $dr['domain'], 'status' => $dr['status']];
            }
        }

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
                    'inspectorUrl' => 'https://api.fringelo.com/v2/deploy-logs?deployment=' . $row['id'] . '&sig=' . deploy_log_sig($row['id']),
                    'domains' => $domains,
                ];
            }
        }
        return ['deployments' => $items];
    }

    public static function deploy_set_env_var($codespaceId, $key, $value, $target = 'both')
    {
        $id = (int) $codespaceId;
        $k = escape_string($key);
        $enc = escape_string(deploy_encrypt($value));
        $t = in_array($target, ['build', 'runtime', 'both'], true) ? $target : 'both';
        query("INSERT INTO codespace_env_vars (codespace_id, var_key, value_encrypted, target)
               VALUES ('$id', '$k', '$enc', '$t')
               ON DUPLICATE KEY UPDATE value_encrypted='$enc', target='$t'");
    }

    public static function deploy_delete_env_var($codespaceId, $key)
    {
        $id = (int) $codespaceId;
        $k = escape_string($key);
        query("DELETE FROM codespace_env_vars WHERE codespace_id='$id' AND var_key='$k'");
    }

    public static function deploy_env_vars($codespaceId, $target)
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
}

function deploy_encryption_key()
{
    return DeployHelper::deploy_encryption_key();
}

function deploy_encrypt($plain)
{
    return DeployHelper::deploy_encrypt($plain);
}

function deploy_decrypt($stored)
{
    return DeployHelper::deploy_decrypt($stored);
}

function deploy_slug($codespaceId)
{
    return DeployHelper::deploy_slug($codespaceId);
}

function deploy_url($codespaceId)
{
    return DeployHelper::deploy_url($codespaceId);
}

function deploy_host($codespaceId)
{
    return DeployHelper::deploy_host($codespaceId);
}

function deploy_dir($codespaceId)
{
    return DeployHelper::deploy_dir($codespaceId);
}

function deploy_release_dir($codespaceId, $deploymentId)
{
    return DeployHelper::deploy_release_dir($codespaceId, $deploymentId);
}

function deploy_current_link($codespaceId)
{
    return DeployHelper::deploy_current_link($codespaceId);
}

function deploy_bare_repo($codespaceId)
{
    return DeployHelper::deploy_bare_repo($codespaceId);
}

function deploy_internal_port($codespaceId)
{
    return DeployHelper::deploy_internal_port($codespaceId);
}

function deploy_resolve_codespace($project, $codespace)
{
    return DeployHelper::deploy_resolve_codespace($project, $codespace);
}

function deploy_get_config($codespaceId)
{
    return DeployHelper::deploy_get_config($codespaceId);
}

function deploy_save_config($codespaceId, $cfg)
{
    return DeployHelper::deploy_save_config($codespaceId, $cfg);
}

function deploy_create($codespaceId, $commitSha, $runtime)
{
    return DeployHelper::deploy_create($codespaceId, $commitSha, $runtime);
}

function deploy_set_status($deploymentId, $status, $extra = [])
{
    return DeployHelper::deploy_set_status($deploymentId, $status, $extra);
}

function deploy_get($deploymentId)
{
    return DeployHelper::deploy_get($deploymentId);
}

function deploy_status_to_ready_state($status)
{
    return DeployHelper::deploy_status_to_ready_state($status);
}

function deploy_log_sig($deploymentId)
{
    return DeployHelper::deploy_log_sig($deploymentId);
}

function deploy_list_for_frontend($codespaceId, $limit = 20)
{
    return DeployHelper::deploy_list_for_frontend($codespaceId, $limit);
}

function deploy_set_env_var($codespaceId, $key, $value, $target = 'both')
{
    return DeployHelper::deploy_set_env_var($codespaceId, $key, $value, $target);
}

function deploy_delete_env_var($codespaceId, $key)
{
    return DeployHelper::deploy_delete_env_var($codespaceId, $key);
}

function deploy_env_vars($codespaceId, $target)
{
    return DeployHelper::deploy_env_vars($codespaceId, $target);
}
