<?php
// run_vercel_tokens_migration.php
require_once 'head.php';

$sql = file_get_contents(__DIR__ . '/create_vercel_tokens_table.sql');
if (!$sql) {
    die('Migration-SQL nicht gefunden.');
}

if (query($sql)) {
    echo "Migration erfolgreich!";
} else {
    echo "Fehler bei Migration: " . mysqli_error($db);
}
