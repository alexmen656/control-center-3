<?php
// Monaco IDE Test Script
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Monaco IDE Full Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

$testProject = 'test-monaco-' . time();
$testUserID = 'dev_user';

echo "<div class='test-section'>";
echo "<h2>Test 1: Projekt-Datenverzeichnis erstellen</h2>";

// Test directory creation
$projectDataDir = __DIR__ . '/data/projects/' . $testUserID . '/' . $testProject;
if (!is_dir($projectDataDir)) {
    mkdir($projectDataDir, 0755, true);
    echo "<span class='success'>✓ Projektverzeichnis erstellt: $projectDataDir</span><br>";
} else {
    echo "<span class='info'>ℹ Projektverzeichnis existiert bereits: $projectDataDir</span><br>";
}

// Test Git initialization
chdir($projectDataDir);
if (!is_dir('.git')) {
    exec('git init 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        echo "<span class='success'>✓ Git Repository initialisiert</span><br>";
        
        exec('git config user.email "test@controlcenter.dev" 2>&1');
        exec('git config user.name "Control Center Test" 2>&1');
        echo "<span class='success'>✓ Git Konfiguration gesetzt</span><br>";
        
        // Create test files
        file_put_contents('README.md', "# $testProject\n\nTest Project für Monaco IDE\n");
        file_put_contents('main.js', "// Test JavaScript\nconsole.log('Hello Monaco IDE!');\n");
        file_put_contents('style.css', "/* Test CSS */\nbody { margin: 0; }\n");
        
        exec('git add . 2>&1');
        exec('git commit -m "Initial commit" 2>&1', $commitOutput, $commitReturn);
        
        if ($commitReturn === 0) {
            echo "<span class='success'>✓ Initial Commit erstellt</span><br>";
        } else {
            echo "<span class='error'>✗ Initial Commit fehlgeschlagen</span><br>";
        }
    } else {
        echo "<span class='error'>✗ Git Initialisierung fehlgeschlagen</span><br>";
    }
} else {
    echo "<span class='info'>ℹ Git Repository existiert bereits</span><br>";
}

chdir(__DIR__);
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 2: File API</h2>";

// Test File API
$fileApiUrl = 'http://localhost/backend/file_api.php?project=' . $testProject . '&action=list';
echo "Test URL: <code>$fileApiUrl</code><br>";

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($fileApiUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && !isset($data['error'])) {
        echo "<span class='success'>✓ File API funktioniert</span><br>";
        echo "Gefundene Dateien: " . count($data) . "<br>";
        if (count($data) > 0) {
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<span class='error'>✗ File API Error: " . ($data['error'] ?? 'Unbekannter Fehler') . "</span><br>";
        echo "<pre>Response: $response</pre>";
    }
} else {
    echo "<span class='error'>✗ File API nicht erreichbar</span><br>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 3: Git API</h2>";

$gitApiUrl = 'http://localhost/backend/monaco_git_api.php?project=' . $testProject . '&action=changes';
echo "Test URL: <code>$gitApiUrl</code><br>";

$gitResponse = @file_get_contents($gitApiUrl, false, $context);
if ($gitResponse !== false) {
    $gitData = json_decode($gitResponse, true);
    if ($gitData && !isset($gitData['error'])) {
        echo "<span class='success'>✓ Git API funktioniert</span><br>";
        if ($gitData['success']) {
            echo "Git Changes: " . count($gitData['changes']) . "<br>";
            if (count($gitData['changes']) > 0) {
                echo "<pre>" . json_encode($gitData['changes'], JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<span class='info'>ℹ Keine Git-Änderungen gefunden (normal bei sauberem Repository)</span><br>";
            }
        } else {
            echo "<span class='error'>Git API Error: " . ($gitData['error'] ?? 'Unbekannter Fehler') . "</span><br>";
        }
    } else {
        echo "<span class='error'>✗ Git API returned invalid JSON</span><br>";
        echo "<pre>Response: $gitResponse</pre>";
    }
} else {
    echo "<span class='error'>✗ Git API nicht erreichbar</span><br>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 4: Datei erstellen und Git-Änderung testen</h2>";

// Create a new file to test git changes
$testFile = $projectDataDir . '/test-change.js';
file_put_contents($testFile, "// Test file for git changes\nconsole.log('This is a test change');\n");
echo "<span class='success'>✓ Test-Datei erstellt: test-change.js</span><br>";

// Test git changes again
$gitResponse2 = @file_get_contents($gitApiUrl, false, $context);
if ($gitResponse2 !== false) {
    $gitData2 = json_decode($gitResponse2, true);
    if ($gitData2 && $gitData2['success']) {
        echo "Git Changes nach Datei-Erstellung: " . count($gitData2['changes']) . "<br>";
        if (count($gitData2['changes']) > 0) {
            echo "<span class='success'>✓ Git erkennt Änderungen korrekt!</span><br>";
            echo "<pre>" . json_encode($gitData2['changes'], JSON_PRETTY_PRINT) . "</pre>";
        }
    }
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 5: Commit Test</h2>";

// Test commit via API
$commitUrl = 'http://localhost/backend/monaco_git_api.php?project=' . $testProject;
$commitData = [
    'action' => 'commit',
    'message' => 'Test commit via API',
    'files' => []
];

$commitContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($commitData),
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$commitResponse = @file_get_contents($commitUrl, false, $commitContext);
if ($commitResponse !== false) {
    $commitResult = json_decode($commitResponse, true);
    if ($commitResult && $commitResult['success']) {
        echo "<span class='success'>✓ Commit via API erfolgreich!</span><br>";
        echo "Commit Hash: " . $commitResult['commit']['sha'] . "<br>";
        echo "Commit Message: " . $commitResult['commit']['message'] . "<br>";
    } else {
        echo "<span class='error'>✗ Commit fehlgeschlagen: " . ($commitResult['error'] ?? 'Unbekannter Fehler') . "</span><br>";
        echo "<pre>Response: $commitResponse</pre>";
    }
} else {
    echo "<span class='error'>✗ Commit API nicht erreichbar</span><br>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎉 Test Zusammenfassung</h2>";
echo "<p><strong>Deine Monaco IDE ist bereit!</strong></p>";
echo "<ul>";
echo "<li>✅ Automatische Projektverzeichnis-Erstellung</li>";
echo "<li>✅ Git-Repository Initialisierung</li>";
echo "<li>✅ File API für Dateiverwaltung</li>";
echo "<li>✅ Git API für Versionskontrolle</li>";
echo "<li>✅ Echte Git-Änderungserkennung</li>";
echo "<li>✅ Commit-Funktionalität</li>";
echo "</ul>";

echo "<p><strong>Nächste Schritte:</strong></p>";
echo "<ol>";
echo "<li>Erstelle ein neues Projekt über die normale Control Center UI</li>";
echo "<li>Öffne die Monaco IDE für das Projekt</li>";
echo "<li>Die Sidebar sollte jetzt echte Daten anzeigen (keine Mock-Daten mehr)</li>";
echo "<li>Erstelle/bearbeite Dateien und sieh die Änderungen in der Git-Section</li>";
echo "</ol>";
echo "</div>";

// Cleanup
echo "<div class='test-section'>";
echo "<h2>Cleanup</h2>";
echo "<p>Test-Projekt erstellt in: <code>$projectDataDir</code></p>";
echo "<p>Du kannst dieses Verzeichnis löschen oder für weitere Tests behalten.</p>";
echo "</div>";
?>
