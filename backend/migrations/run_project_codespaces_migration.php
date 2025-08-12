<?php
/**
 * Migration für Project Codespaces
 * Erstellt die neue Tabelle und migriert bestehende Monaco-Instanzen
 */

include "head.php";

function migrateProjectCodespaces() {
    // SQL-Datei ausführen
    $sqlContent = file_get_contents(__DIR__ . '/create_project_codespaces_table.sql');
    
    // SQL in einzelne Statements aufteilen
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            $result = query($statement);
            if (!$result) {
                echo "Error executing: " . substr($statement, 0, 100) . "...\n";
                return false;
            }
        }
    }
    
    echo "Project Codespaces migration completed successfully!\n";
    return true;
}

// Migration ausführen
if (migrateProjectCodespaces()) {
    echo json_encode(['success' => true, 'message' => 'Migration completed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Migration failed']);
}
?>
