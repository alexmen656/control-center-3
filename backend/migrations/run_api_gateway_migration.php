<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../helpers/deploy.php';

$alters = [
    "ALTER TABLE project_api_subscriptions ADD COLUMN IF NOT EXISTS key_prefix VARCHAR(16) DEFAULT NULL",
    "ALTER TABLE project_api_subscriptions ADD COLUMN IF NOT EXISTS key_hash CHAR(64) DEFAULT NULL",
    "ALTER TABLE project_api_subscriptions ADD COLUMN IF NOT EXISTS key_enc TEXT DEFAULT NULL",
    "ALTER TABLE project_api_subscriptions ADD INDEX IF NOT EXISTS idx_key_prefix (key_prefix)",
    "ALTER TABLE cms_apis ADD COLUMN IF NOT EXISTS source_type ENUM('internal','external','codespace') NOT NULL DEFAULT 'external'",
    "ALTER TABLE cms_apis ADD COLUMN IF NOT EXISTS codespace_id INT DEFAULT NULL",
    "ALTER TABLE cms_apis ADD COLUMN IF NOT EXISTS upstream VARCHAR(255) DEFAULT NULL",
    "UPDATE cms_apis SET source_type='internal' WHERE endpoint_base LIKE '/api/v1/%'",
];

foreach ($alters as $sql) {
    query($sql);
    echo 'ok: ' . substr($sql, 0, 70) . "\n";
}

$res = query("SELECT id, api_key FROM project_api_subscriptions WHERE (key_hash IS NULL OR key_hash='') AND api_key IS NOT NULL AND api_key != ''");
$n = 0;

while ($row = mysqli_fetch_assoc($res)) {
    $key = $row['api_key'];
    $prefix = escape_string(substr($key, 0, 16));
    $hash = hash('sha256', $key);
    $enc = escape_string(deploy_encrypt($key));
    $id = (int) $row['id'];
    query("UPDATE project_api_subscriptions SET key_prefix='$prefix', key_hash='$hash', key_enc='$enc' WHERE id='$id'");
    $n++;
}

echo "backfilled $n subscriptions\n";
