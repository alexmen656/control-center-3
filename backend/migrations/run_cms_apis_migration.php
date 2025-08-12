<?php
include 'head.php';

echo "Running CMS APIs Migration...\n";

// SQL ausführen
$sql = file_get_contents('create_cms_apis_tables.sql');

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

echo "CMS APIs Migration completed!\n";
echo "Available APIs:\n";

// Show created APIs
$apis = query("SELECT name, slug, category FROM cms_apis ORDER BY category, name");
foreach ($apis as $api) {
    echo "- {$api['name']} ({$api['category']}) - /{$api['slug']}\n";
}
?>
