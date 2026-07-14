<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('cli only');
}

require_once __DIR__ . '/../helpers/deploy.php';
require_once __DIR__ . '/../helpers/cloudflare.php';
require_once __DIR__ . '/framework.php';

define('WORKER_BUILDER_IMAGE', 'fringelo/builder');
define('WORKER_RUNTIME_IMAGE', 'fringelo/runtime');
define('WORKER_BUILD_TIMEOUT', 900);
define('WORKER_KEEP_RELEASES', 3);
define('WORKER_NGINX_DIR', '/etc/nginx/sites-enabled');
define('WORKER_NGINX_TPL', __DIR__ . '/nginx/app-node.conf.tpl');
define('WORKER_CUSTOM_DOMAIN_NODE_TPL', __DIR__ . '/nginx/custom-domain-node.conf.tpl');
define('WORKER_CUSTOM_DOMAIN_STATIC_TPL', __DIR__ . '/nginx/custom-domain-static.conf.tpl');
define('WORKER_CERTBOT_CLOUDFLARE_INI', '/etc/letsencrypt/cloudflare.ini');
define('WORKER_CERTBOT_EMAIL', 'admin@fringelo.com');

function wlog($msg)
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    fwrite(STDOUT, $line);
}

function sh($cmd, $logFile = null)
{
    if ($logFile) {
        $full = $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1';
    } else {
        $full = $cmd . ' 2>&1';
    }
    $out = [];
    $code = 0;
    exec($full, $out, $code);
    return [$code, implode("\n", $out)];
}

function ensure_dirs()
{
    foreach ([DEPLOY_ROOT, DEPLOY_BUILD_TMP, DEPLOY_LOG_ROOT, DEPLOY_RUNTIME_LOG_ROOT] as $d) {
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
    }
}

function sync_runtime_logs()
{
    static $lastRun = 0;
    $now = time();
    if ($now - $lastRun < 10) {
        return;
    }
    $lastRun = $now;

    list($rc, $out) = sh("docker ps --filter " . escapeshellarg('name=^app-cs-') . " --format '{{.Names}}'");
    if ($rc !== 0 || trim($out) === '') {
        return;
    }

    foreach (explode("\n", trim($out)) as $name) {
        $name = trim($name);
        if ($name === '' || !preg_match('/^app-cs-(\d+)$/', $name, $m)) {
            continue;
        }
        $logFile = DEPLOY_RUNTIME_LOG_ROOT . '/cs-' . $m[1] . '.log';
        sh('docker logs --tail 500 --timestamps ' . escapeshellarg($name) . ' > ' . escapeshellarg($logFile));
        @chmod($logFile, 0664);
    }
}

function claim_next_deployment()
{
    $res = query("SELECT id FROM deployments WHERE status='queued' ORDER BY id ASC LIMIT 1");
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    $row = mysqli_fetch_assoc($res);
    $id = (int) $row['id'];
    query("UPDATE deployments SET status='building' WHERE id='$id' AND status='queued'");
    if (mysqli_affected_rows($GLOBALS['con']) !== 1) {
        return null;
    }
    return deploy_get($id);
}

function reap_stuck_deployments()
{
    query("UPDATE deployments SET status='error', error_msg='worker timeout'
           WHERE status='building' AND created_at < (NOW() - INTERVAL 20 MINUTE)");
}

function wait_for_port($port, $timeoutSeconds)
{
    for ($i = 0; $i < $timeoutSeconds; $i++) {
        $conn = @fsockopen('127.0.0.1', (int) $port, $errno, $errstr, 1);

        if ($conn) {
            fclose($conn);
            return true;
        }

        sleep(1);
    }
    return false;
}

function write_env_file($codespaceId, $target, $path)
{
    $vars = deploy_env_vars($codespaceId, $target);
    $lines = [];
    foreach ($vars as $k => $v) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) {
            continue;
        }

        $v = str_replace(["\n", "\r"], '', $v);
        $lines[] = $k . '=' . $v;
    }
    file_put_contents($path, implode("\n", $lines) . "\n");
    chmod($path, 0600);
}

function inject_codespace_apis($codespaceId, $workDir, $runtime)
{
    if ($runtime !== 'node') {
        return;
    }

    $id = (int) $codespaceId;
    $res = query("
        SELECT ca.slug
        FROM codespace_api_activations caa
        JOIN project_api_subscriptions pas ON caa.subscription_id = pas.id
        JOIN cms_apis ca ON pas.api_id = ca.id
        WHERE caa.codespace_id = '$id' AND caa.is_active = 1
    ");
    if (!$res) {
        return;
    }

    $slugs = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $slugs[] = $row['slug'];
    }
    if (empty($slugs)) {
        return;
    }

    $sdkSrcDir = __DIR__ . '/../apis_sdk';
    $apisDir = $workDir . '/.monaco_apis';
    @mkdir($apisDir, 0775, true);

    $imports = '';
    $names = [];
    foreach ($slugs as $slug) {
        $src = $sdkSrcDir . '/' . $slug . 'SDK.js';
        if (!file_exists($src)) {
            continue;
        }
        $envName = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $slug)) . '_API_KEY';
        $content = str_replace('[{[apiKey]}]', $envName, file_get_contents($src));
        file_put_contents($apisDir . '/' . $slug . 'SDK.js', $content);

        $className = ucfirst($slug) . 'API';
        $imports .= "import {$className} from './{$slug}SDK.js';\n";
        $names[] = $className;
    }

    if (empty($names)) {
        return;
    }

    $exportList = implode(', ', $names);
    file_put_contents($apisDir . '/index.js', $imports . "\nexport { {$exportList} };\nexport default { {$exportList} };\n");
    file_put_contents($apisDir . '/package.json', "{\n  \"name\": \"apis\",\n  \"version\": \"1.0.0\",\n  \"type\": \"module\",\n  \"main\": \"index.js\"\n}\n");

    $pkgPath = $workDir . '/package.json';
    $pkg = file_exists($pkgPath) ? json_decode(file_get_contents($pkgPath), true) : [];
    if (!is_array($pkg)) {
        $pkg = [];
    }
    if (!isset($pkg['dependencies']) || !is_array($pkg['dependencies'])) {
        $pkg['dependencies'] = [];
    }
    $pkg['dependencies']['apis'] = 'file:./.monaco_apis';
    file_put_contents($pkgPath, json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

function build_deployment($dep)
{
    $deploymentId = (int) $dep['id'];
    $codespaceId = (int) $dep['codespace_id'];

    $logFile = DEPLOY_LOG_ROOT . '/' . $deploymentId . '.log';
    @file_put_contents($logFile, '');
    deploy_set_status($deploymentId, 'building', ['build_log' => $logFile]);

    $work = DEPLOY_BUILD_TMP . '/' . $deploymentId;
    sh('rm -rf ' . escapeshellarg($work));
    @mkdir($work, 0775, true);

    $bare = deploy_bare_repo($codespaceId);
    if (!is_dir($bare)) {
        fail($deploymentId, $logFile, "Bare repo not found: $bare");
        return;
    }

    wlog("deployment $deploymentId: cloning $bare");
    list($cc) = sh('git clone --depth 1 ' . escapeshellarg('file://' . $bare) . ' ' . escapeshellarg($work), $logFile);
    if ($cc !== 0) {
        fail($deploymentId, $logFile, 'git clone failed');
        return;
    }
    if (!empty($dep['commit_sha'])) {
        sh('git -C ' . escapeshellarg($work) . ' fetch --depth 1 origin ' . escapeshellarg($dep['commit_sha']), $logFile);
        list($coc) = sh('git -C ' . escapeshellarg($work) . ' checkout --detach ' . escapeshellarg($dep['commit_sha']), $logFile);
        list(, $head) = sh('git -C ' . escapeshellarg($work) . ' rev-parse HEAD');
        if ($coc !== 0 || trim($head) !== $dep['commit_sha']) {
            fail($deploymentId, $logFile, 'could not check out commit ' . $dep['commit_sha']);
            sh('rm -rf ' . escapeshellarg($work));
            return;
        }
    }

    $cfg = deploy_effective_config($codespaceId, $work);
    $runtime = $cfg['runtime'] === 'node' ? 'node' : 'static';
    wlog("deployment $deploymentId: framework={$cfg['framework']} runtime=$runtime");

    inject_codespace_apis($codespaceId, $work, $runtime);

    $releaseDir = deploy_release_dir($codespaceId, $deploymentId);
    sh('rm -rf ' . escapeshellarg($releaseDir));
    @mkdir($releaseDir, 0775, true);
    sh('chown -R 10001:10001 ' . escapeshellarg($releaseDir));

    $envFile = $work . '.buildenv';
    write_env_file($codespaceId, 'build', $envFile);

    $dockerCmd = 'timeout ' . WORKER_BUILD_TIMEOUT . ' docker run --rm'
        . ' --network fringelo-build --dns 1.1.1.1 --dns 8.8.8.8'
        . ' --memory 2g --memory-swap 2g --cpus 1.5 --pids-limit 512'
        . ' --cap-drop ALL --security-opt no-new-privileges'
        . ' --read-only --tmpfs /tmp:size=1g,exec,mode=1777 --tmpfs /build:size=6g,exec,mode=1777'
        . ' -e ' . escapeshellarg('HOME=/tmp')
        . ' -e ' . escapeshellarg('INSTALL_CMD=' . $cfg['install_cmd'])
        . ' -e ' . escapeshellarg('BUILD_CMD=' . $cfg['build_cmd'])
        . ' -e ' . escapeshellarg('OUTPUT_DIR=' . $cfg['output_dir'])
        . ' -e ' . escapeshellarg('RUNTIME=' . $runtime)
        . ' --env-file ' . escapeshellarg($envFile)
        . ' -v ' . escapeshellarg($work . ':/src:ro')
        . ' -v ' . escapeshellarg($releaseDir . ':/out')
        . ' ' . WORKER_BUILDER_IMAGE;

    wlog("deployment $deploymentId: building");
    list($bc) = sh($dockerCmd, $logFile);
    @unlink($envFile);

    if ($bc !== 0) {
        fail($deploymentId, $logFile, 'build failed (exit ' . $bc . ')');
        sh('rm -rf ' . escapeshellarg($work) . ' ' . escapeshellarg($releaseDir));
        return;
    }

    sh('chmod -R a+rX ' . escapeshellarg($releaseDir));
    sh('chown -R root:root ' . escapeshellarg($releaseDir));

    activate_release($codespaceId, $deploymentId, $releaseDir, $runtime, $cfg, $logFile);
    sh('rm -rf ' . escapeshellarg($work));
    prune_releases($codespaceId, $deploymentId);
}

function flip_current($codespaceId, $releaseDir)
{
    $current = deploy_current_link($codespaceId);
    $tmpLink = $current . '.tmp';
    sh('ln -sfn ' . escapeshellarg($releaseDir) . ' ' . escapeshellarg($tmpLink));
    sh('mv -Tf ' . escapeshellarg($tmpLink) . ' ' . escapeshellarg($current));
}

function activate_release($codespaceId, $deploymentId, $releaseDir, $runtime, $cfg, $logFile)
{
    $slug = deploy_slug($codespaceId);

    if ($runtime === 'node') {
        if (!start_node_runtime($codespaceId, $releaseDir, $cfg, $logFile)) {
            fail($deploymentId, $logFile, 'node runtime failed to start or become healthy');
            return;
        }
        flip_current($codespaceId, $releaseDir);
        deploy_set_status($deploymentId, 'ready', [
            'url' => deploy_url($codespaceId),
            'internal_port' => deploy_internal_port($codespaceId),
            'runtime' => 'node',
            'build_log' => $logFile,
        ]);
    } else {
        flip_current($codespaceId, $releaseDir);
        stop_node_runtime($slug);
        deploy_set_status($deploymentId, 'ready', [
            'url' => deploy_url($codespaceId),
            'runtime' => 'static',
            'build_log' => $logFile,
        ]);
    }
    wlog("deployment $deploymentId: ready at " . deploy_url($codespaceId));
}

function start_node_runtime($codespaceId, $releaseDir, $cfg, $logFile)
{
    $slug = deploy_slug($codespaceId);
    $port = deploy_internal_port($codespaceId);
    $name = 'app-' . $slug;

    sh('docker rm -f ' . escapeshellarg($name) . ' 2>/dev/null');

    $envFile = DEPLOY_BUILD_TMP . '/' . $slug . '.runenv';
    write_env_file($codespaceId, 'runtime', $envFile);

    $runCmd = 'docker run -d --restart unless-stopped --name ' . escapeshellarg($name)
        . ' --network fringelo-apps --dns 1.1.1.1'
        . ' --memory 512m --memory-swap 512m --cpus 1 --pids-limit 256'
        . ' --cap-drop ALL --security-opt no-new-privileges'
        . ' --read-only --tmpfs /tmp:size=256m,exec,mode=1777'
        . ' -p ' . escapeshellarg('127.0.0.1:' . $port . ':' . $port)
        . ' -e ' . escapeshellarg('HOME=/tmp')
        . ' -e ' . escapeshellarg('PORT=' . $port)
        . ' -e ' . escapeshellarg('START_CMD=' . $cfg['start_cmd'])
        . ' --env-file ' . escapeshellarg($envFile)
        . ' -v ' . escapeshellarg($releaseDir . ':/app:ro')
        . ' ' . WORKER_RUNTIME_IMAGE;

    list($rc) = sh($runCmd, $logFile);
    @unlink($envFile);

    if ($rc !== 0) {
        return false;
    }

    if (!wait_for_port($port, 25)) {
        sh('docker logs --tail 40 ' . escapeshellarg($name) . ' >> ' . escapeshellarg($logFile) . ' 2>&1');
        sh('docker rm -f ' . escapeshellarg($name) . ' 2>/dev/null');
        return false;
    }

    return write_node_vhost($slug, $port, $logFile);
}

function write_node_vhost($slug, $port, $logFile)
{
    $host = $slug . '.' . DEPLOY_APPS_DOMAIN;
    $tpl = file_get_contents(WORKER_NGINX_TPL);
    $conf = str_replace(['__HOST__', '__PORT__'], [$host, $port], $tpl);
    $path = WORKER_NGINX_DIR . '/app-' . $slug . '.conf';
    file_put_contents($path, $conf);

    list($tc) = sh('nginx -t', $logFile);
    if ($tc !== 0) {
        @unlink($path);
        return false;
    }
    sh('systemctl reload nginx', $logFile);
    return true;
}

function stop_node_runtime($slug)
{
    $name = 'app-' . $slug;
    sh('docker rm -f ' . escapeshellarg($name) . ' 2>/dev/null');
    $conf = WORKER_NGINX_DIR . '/app-' . $slug . '.conf';
    if (file_exists($conf)) {
        @unlink($conf);
        sh('nginx -t && systemctl reload nginx');
    }
}

function domain_conf_path($domain)
{
    $safe = preg_replace('/[^a-z0-9.-]/', '-', strtolower($domain));
    return WORKER_NGINX_DIR . '/domain-' . $safe . '.conf';
}

function claim_next_domain_job()
{
    $res = query("SELECT * FROM codespace_domains WHERE status='pending' ORDER BY id ASC LIMIT 1");
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    return mysqli_fetch_assoc($res);
}

function claim_next_domain_teardown()
{
    $res = query("SELECT * FROM codespace_domain_teardowns ORDER BY id ASC LIMIT 1");
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    return mysqli_fetch_assoc($res);
}

function issue_domain_certificate($domain, $logFile)
{
    $cmd = 'certbot certonly --dns-cloudflare'
        . ' --dns-cloudflare-credentials ' . escapeshellarg(WORKER_CERTBOT_CLOUDFLARE_INI)
        . ' --dns-cloudflare-propagation-seconds 30'
        . ' -d ' . escapeshellarg($domain)
        . ' --cert-name ' . escapeshellarg($domain)
        . ' --non-interactive --agree-tos -m ' . escapeshellarg(WORKER_CERTBOT_EMAIL);

    list($code, $out) = sh($cmd, $logFile);
    if ($code !== 0) {
        return ['success' => false, 'message' => trim(substr($out, -400))];
    }
    return ['success' => true];
}

function write_custom_domain_vhost($domain, $runtime, $codespaceId, $logFile)
{
    if ($runtime === 'node') {
        $tpl = file_get_contents(WORKER_CUSTOM_DOMAIN_NODE_TPL);
        $conf = str_replace(['__HOST__', '__PORT__'], [$domain, deploy_internal_port($codespaceId)], $tpl);
    } else {
        $tpl = file_get_contents(WORKER_CUSTOM_DOMAIN_STATIC_TPL);
        $conf = str_replace(['__HOST__', '__SLUG__'], [$domain, deploy_slug($codespaceId)], $tpl);
    }

    $path = domain_conf_path($domain);
    file_put_contents($path, $conf);

    list($tc) = sh('nginx -t', $logFile);
    if ($tc !== 0) {
        @unlink($path);
        return false;
    }
    sh('systemctl reload nginx', $logFile);
    return true;
}

function provision_custom_domain($row)
{
    $id = (int) $row['id'];
    $domain = $row['domain'];
    $codespaceId = (int) $row['codespace_id'];
    $logFile = DEPLOY_LOG_ROOT . '/domain-' . $id . '.log';
    @file_put_contents($logFile, '');

    $depRes = query("SELECT runtime FROM deployments WHERE codespace_id='$codespaceId' AND status='ready' ORDER BY id DESC LIMIT 1");
    $dep = $depRes ? mysqli_fetch_assoc($depRes) : null;

    if (!$dep) {
        query("UPDATE codespace_domains SET status='error', status_message='" . escape_string('Codespace wurde noch nicht deployed.') . "' WHERE id='$id'");
        return;
    }

    $runtime = $dep['runtime'] === 'node' ? 'node' : 'static';

    wlog("domain $id ($domain): issuing certificate");
    $cert = issue_domain_certificate($domain, $logFile);
    if (!$cert['success']) {
        query("UPDATE codespace_domains SET status='error', status_message='" . escape_string('Zertifikat fehlgeschlagen: ' . $cert['message']) . "' WHERE id='$id'");
        return;
    }

    wlog("domain $id ($domain): writing vhost (runtime=$runtime)");
    if (!write_custom_domain_vhost($domain, $runtime, $codespaceId, $logFile)) {
        query("UPDATE codespace_domains SET status='error', status_message='" . escape_string('nginx-Konfiguration fehlgeschlagen.') . "' WHERE id='$id'");
        return;
    }

    query("UPDATE codespace_domains SET status='active', status_message=NULL WHERE id='$id'");
    wlog("domain $id ($domain): active");
}

function teardown_custom_domain($row)
{
    $id = (int) $row['id'];
    $domain = $row['domain'];

    $conf = domain_conf_path($domain);
    if (file_exists($conf)) {
        @unlink($conf);
        sh('nginx -t && systemctl reload nginx');
    }

    sh('certbot delete --cert-name ' . escapeshellarg($domain) . ' --non-interactive');
    cloudflare_deleteRecordByDomain($domain);

    query("DELETE FROM codespace_domain_teardowns WHERE id='$id'");
    wlog("domain teardown $id ($domain): done");
}

function prune_releases($codespaceId, $currentDeploymentId)
{
    $releasesDir = deploy_dir($codespaceId) . '/releases';
    if (!is_dir($releasesDir)) {
        return;
    }
    $entries = array_filter(scandir($releasesDir), function ($e) {
        return ctype_digit($e);
    });
    rsort($entries, SORT_NUMERIC);
    $keep = array_slice($entries, 0, WORKER_KEEP_RELEASES);
    foreach ($entries as $e) {
        if (!in_array($e, $keep, true)) {
            sh('rm -rf ' . escapeshellarg($releasesDir . '/' . $e));
        }
    }
}

function fail($deploymentId, $logFile, $msg)
{
    wlog("deployment $deploymentId: FAILED - $msg");
    @file_put_contents($logFile, "\n[deploy] ERROR: $msg\n", FILE_APPEND);
    deploy_set_status($deploymentId, 'error', ['error_msg' => $msg, 'build_log' => $logFile]);
}

function main_loop()
{
    ensure_dirs();
    wlog('worker started');
    while (true) {
        reap_stuck_deployments();
        sync_runtime_logs();
        $dep = claim_next_deployment();
        if ($dep) {
            try {
                build_deployment($dep);
            } catch (Throwable $e) {
                $logFile = DEPLOY_LOG_ROOT . '/' . (int) $dep['id'] . '.log';
                fail((int) $dep['id'], $logFile, 'worker exception: ' . $e->getMessage());
            }
            continue;
        }

        $teardown = claim_next_domain_teardown();
        if ($teardown) {
            try {
                teardown_custom_domain($teardown);
            } catch (Throwable $e) {
                wlog('domain teardown exception: ' . $e->getMessage());
            }
            continue;
        }

        $domainJob = claim_next_domain_job();
        if ($domainJob) {
            try {
                provision_custom_domain($domainJob);
            } catch (Throwable $e) {
                query("UPDATE codespace_domains SET status='error', status_message='" . escape_string('worker exception: ' . $e->getMessage()) . "' WHERE id='" . (int) $domainJob['id'] . "'");
            }
            continue;
        }

        sleep(2);
    }
}

main_loop();
