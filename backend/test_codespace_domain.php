<?php
// test_codespace_domain.php
// Test-Script für Codespace Domain Funktionalität

require_once 'config.php';
require_once 'head.php';

echo "<h2>Codespace Domain Test</h2>";

// Test 1: Domain-Info für einen Codespace abrufen
echo "<h3>Test 1: Domain-Info abrufen</h3>";
$codespace_id = 1; // Beispiel Codespace ID
$user_id = 1; // Beispiel User ID

$response = file_get_contents('http://localhost/codespace_connections.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'action' => 'get_project_domain_info',
            'codespace_id' => $codespace_id,
            'user_id' => $user_id
        ])
    ]
]));

echo "<pre>";
echo "Response: " . $response . "\n";
echo "</pre>";

// Test 2: Alle Verbindungen abrufen
echo "<h3>Test 2: Alle Verbindungen abrufen</h3>";
$response2 = file_get_contents('http://localhost/codespace_connections.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'action' => 'get_all_connections',
            'codespace_id' => $codespace_id,
            'user_id' => $user_id
        ])
    ]
]));

echo "<pre>";
echo "Response: " . $response2 . "\n";
echo "</pre>";

// Test 3: Überprüfe Datenbankstruktur
echo "<h3>Test 3: Datenbankstruktur</h3>";
$tables = ['codespace_domains', 'codespace_github_repos', 'codespace_vercel_projects'];

foreach ($tables as $table) {
    $result = query("SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Tabelle $table existiert<br>";
        
        // Spalten anzeigen
        $columns = query("DESCRIBE $table");
        echo "Spalten:<br>";
        while ($column = fetch_assoc($columns)) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
    } else {
        echo "✗ Tabelle $table existiert nicht<br>";
    }
    echo "<br>";
}
?>
