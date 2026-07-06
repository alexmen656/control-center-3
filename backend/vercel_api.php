<?php
require_once 'head.php';
require_once 'deploy_helper.php';

function deploy_api_fail($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$project = $_GET['project'] ?? '';
$codespace = $_GET['codespace'] ?? 'main';

if ($project === '') {
    deploy_api_fail('project required');
}

$cs = deploy_resolve_codespace($project, $codespace);
if (!$cs) {
    deploy_api_fail('codespace not found');
}
$codespaceId = (int) $cs['id'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

if ($method === 'GET') {
    switch ($action) {
        case 'projects':
            echo json_encode(['success' => true, 'projects' => []]);
            break;
        case 'env':
            $vars = [];
            $res = query("SELECT var_key, target FROM codespace_env_vars WHERE codespace_id='$codespaceId'");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $vars[] = ['key' => $row['var_key'], 'target' => $row['target']];
                }
            }
            echo json_encode(['success' => true, 'envVars' => $vars]);
            break;
        case 'deployments':
        default:
            echo json_encode(['success' => true, 'deployments' => deploy_list_for_frontend($codespaceId)]);
            break;
    }
    exit;
}

if ($method === 'POST') {
    switch ($action) {
        case 'deploy':
            $bare = deploy_bare_repo($codespaceId);
            $commit = '';
            if (is_dir($bare)) {
                $out = trim((string) shell_exec('git --git-dir=' . escapeshellarg($bare) . ' rev-parse HEAD 2>/dev/null'));
                if ($out !== '') {
                    $commit = $out;
                }
            }
            $saved = deploy_get_config($codespaceId);
            $runtime = ($saved && $saved['runtime'] === 'node') ? 'node' : 'static';

            $deploymentId = deploy_create($codespaceId, $commit, $runtime);
            deploy_enqueue_job($deploymentId);

            echo json_encode([
                'success' => true,
                'deployment' => [
                    'uid' => (string) $deploymentId,
                    'url' => deploy_host($codespaceId),
                    'readyState' => 'QUEUED',
                    'created' => time() * 1000,
                    'inspectorUrl' => 'https://api.fringelo.com/deploy_logs.php?deployment=' . $deploymentId . '&sig=' . deploy_log_sig($deploymentId),
                ],
            ]);
            break;

        case 'status':
            $deploymentId = (int) ($input['deploymentId'] ?? $_GET['deploymentId'] ?? 0);
            $dep = deploy_get($deploymentId);
            if (!$dep || (int) $dep['codespace_id'] !== $codespaceId) {
                deploy_api_fail('deployment not found', 404);
            }
            echo json_encode([
                'success' => true,
                'status' => [
                    'uid' => (string) $dep['id'],
                    'readyState' => deploy_status_to_ready_state($dep['status']),
                    'url' => $dep['url'] ? preg_replace('#^https?://#', '', $dep['url']) : '',
                    'error' => $dep['error_msg'],
                ],
            ]);
            break;

        case 'create_env':
            $key = trim($input['key'] ?? '');
            $value = (string) ($input['value'] ?? '');
            $target = in_array($input['target'] ?? 'both', ['build', 'runtime', 'both'], true) ? $input['target'] : 'both';
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                deploy_api_fail('invalid env key');
            }
            $k = escape_string($key);
            $enc = escape_string(deploy_encrypt($value));
            $t = escape_string($target);
            query("INSERT INTO codespace_env_vars (codespace_id, var_key, value_encrypted, target)
                   VALUES ('$codespaceId', '$k', '$enc', '$t')
                   ON DUPLICATE KEY UPDATE value_encrypted='$enc', target='$t'");
            echo json_encode(['success' => true, 'result' => ['key' => $key]]);
            break;

        case 'delete_env':
            $key = escape_string($input['key'] ?? '');
            query("DELETE FROM codespace_env_vars WHERE codespace_id='$codespaceId' AND var_key='$key'");
            echo json_encode(['success' => true, 'result' => ['key' => $input['key'] ?? '']]);
            break;

        default:
            deploy_api_fail('invalid action');
    }
    exit;
}

deploy_api_fail('method not allowed', 405);
