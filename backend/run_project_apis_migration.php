<?php
include 'head.php';

echo "Running Project APIs Migration...\n";

// SQL ausführen
$sql = file_get_contents('create_project_apis_table.sql');

// SQL in einzelne Statements aufteilen
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        $result = query($statement);
        if ($result) {
            echo "✓ Statement executed successfully\n";
        } else {
            echo "✗ Error executing statement: " . mysqli_error($GLOBALS['conn']) . "\n";
        }
    }
}

echo "Migration completed!\n";
?>
