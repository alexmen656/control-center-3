<?php
// run_codespace_connections_migration.php
require_once 'config.php';
require_once 'head.php';

echo "Starting codespace connections migration...\n";

// SQL aus der create_codespace_connections_table.sql Datei lesen und ausführen
$sql = file_get_contents(__DIR__ . '/create_codespace_connections_table.sql');

// SQL Statements aufteilen
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $result = query($statement);
        if ($result) {
            echo "✓ Success\n";
        } else {
            echo "✗ Failed\n";
        }
    }
}

echo "Migration completed!\n";
?>
