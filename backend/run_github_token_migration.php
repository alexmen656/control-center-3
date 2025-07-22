<?php
// run_github_token_migration.php
// Führt das SQL-Migrationsskript für die GitHub-Token-Tabelle aus

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/head.php';

$sql = file_get_contents(__DIR__ . '/create_github_token_table.sql');

if (!$sql) {
    die('Konnte SQL-Datei nicht lesen.');
}

$result = query($sql);
if ($result) {
    echo "Migration erfolgreich ausgeführt.";
} else {
    echo "Fehler bei Migration.";
}
