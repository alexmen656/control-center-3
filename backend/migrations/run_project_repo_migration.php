<?php
// run_project_repo_migration.php
// Führt das SQL-Migrationsskript für die Projekt-Repo-Tabelle aus

require_once 'config.php';
require_once 'head.php';

$sql = file_get_contents(__DIR__ . '/create_project_repo_table.sql');
if (!$sql) {
    die('Migration-SQL nicht gefunden.');
}

if (query($sql)) {
    echo 'Migration erfolgreich.';
} else {
    echo 'Migration fehlgeschlagen.';
}
