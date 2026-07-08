<?php
require_once __DIR__ . '/helpers/deploy.php';

function api_generate_key($projectID = null)
{
    $suffix = $projectID ? '_' . $projectID : '_' . time();
    return 'cms_' . bin2hex(random_bytes(16)) . $suffix;
}

function api_key_prefix($key)
{
    return substr($key, 0, 16);
}

function api_key_hash($key)
{
    return hash('sha256', $key);
}

function api_key_columns($key)
{
    return [
        'prefix' => api_key_prefix($key),
        'hash' => api_key_hash($key),
        'enc' => deploy_encrypt($key),
    ];
}

function api_store_key($subscriptionId, $key)
{
    $c = api_key_columns($key);
    $id = (int) $subscriptionId;
    $prefix = escape_string($c['prefix']);
    $hash = escape_string($c['hash']);
    $enc = escape_string($c['enc']);
    query("UPDATE project_api_subscriptions SET key_prefix='$prefix', key_hash='$hash', key_enc='$enc', api_key='' WHERE id='$id'");
}

function api_lookup_subscription($key, $apiId = null)
{
    $prefix = escape_string(api_key_prefix($key));
    $hash = api_key_hash($key);
    $sql = "SELECT * FROM project_api_subscriptions WHERE key_prefix='$prefix' AND is_enabled=1";
    if ($apiId !== null) {
        $sql .= " AND api_id='" . escape_string($apiId) . "'";
    }
    $res = query($sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['key_hash']) && hash_equals($row['key_hash'], $hash)) {
                return $row;
            }
        }
    }
    return null;
}

function api_env_name($slug)
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $slug)) . '_API_KEY';
}

function api_decrypt_key($subscriptionRow)
{
    if (!empty($subscriptionRow['key_enc'])) {
        return deploy_decrypt($subscriptionRow['key_enc']);
    }
    return $subscriptionRow['api_key'] ?? '';
}
