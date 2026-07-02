<?php
require_once __DIR__ . '/../db_connection.php';

function columnExists($con, $table, $column)
{
    $table = mysqli_real_escape_string($con, $table);
    $column = mysqli_real_escape_string($con, $column);
    $res = mysqli_query($con, "
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = '$table'
          AND COLUMN_NAME = '$column'
        LIMIT 1
    ");
    return $res && mysqli_num_rows($res) > 0;
}

$createSql = file_get_contents(__DIR__ . '/create_api_call_logs_table.sql');
if (!mysqli_query($con, $createSql)) {
    echo "Fehler beim Erstellen der Tabelle: " . mysqli_error($con) . "\n";
    exit(1);
}
echo "cms_api_usage_logs sichergestellt.\n";

$columns = [
    'subscription_id' => "int(11) NOT NULL DEFAULT 0",
    'endpoint_id' => "int(11) DEFAULT NULL",
    'method' => "varchar(10) NOT NULL DEFAULT 'GET'",
    'path' => "varchar(500) NOT NULL DEFAULT ''",
    'status_code' => "int(11) NOT NULL DEFAULT 0",
    'response_time' => "int(11) DEFAULT 0",
    'ip_address' => "varchar(45) DEFAULT NULL",
    'user_agent' => "text DEFAULT NULL",
    'request_query' => "text DEFAULT NULL",
    'request_headers' => "json DEFAULT NULL",
    'request_body' => "longtext DEFAULT NULL",
    'response_headers' => "json DEFAULT NULL",
    'response_body' => "longtext DEFAULT NULL",
    'error_message' => "text DEFAULT NULL",
    'timestamp' => "timestamp DEFAULT CURRENT_TIMESTAMP",
];

foreach ($columns as $name => $definition) {
    if (!columnExists($con, 'cms_api_usage_logs', $name)) {
        if (mysqli_query($con, "ALTER TABLE `cms_api_usage_logs` ADD COLUMN `$name` $definition")) {
            echo "Spalte hinzugefügt: $name\n";
        } else {
            echo "Konnte Spalte $name nicht hinzufügen: " . mysqli_error($con) . "\n";
        }
    }
}

echo "Migration abgeschlossen.\n";
