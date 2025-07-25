<?php
// Test script for Monaco IDE APIs

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Monaco IDE Backend Test</h1>";

// Test basic directory creation
echo "<h2>1. Testing Data Directory Setup</h2>";
$dataDir = __DIR__ . '/data/projects/dev_user/test-project';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "✓ Created data directory: $dataDir<br>";
} else {
    echo "✓ Data directory exists: $dataDir<br>";
}

// Test Git initialization
echo "<h2>2. Testing Git Initialization</h2>";
$cwd = getcwd();
chdir($dataDir);

if (!is_dir('.git')) {
    exec('git init 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        echo "✓ Git repository initialized<br>";
        
        // Set basic config
        exec('git config user.email "test@controlcenter.dev" 2>&1');
        exec('git config user.name "Control Center Test" 2>&1');
        echo "✓ Git config set<br>";
        
        // Create initial file
        file_put_contents('test.js', 'console.log("Hello World!");');
        exec('git add test.js 2>&1');
        exec('git commit -m "Initial commit" 2>&1', $commitOutput, $commitReturn);
        
        if ($commitReturn === 0) {
            echo "✓ Initial commit created<br>";
        } else {
            echo "✗ Failed to create initial commit<br>";
        }
    } else {
        echo "✗ Failed to initialize git repository<br>";
    }
} else {
    echo "✓ Git repository already exists<br>";
}

chdir($cwd);

// Test File API
echo "<h2>3. Testing File API</h2>";
$testUrl = 'http://localhost/backend/file_api.php?project=test-project&action=list';
echo "Testing URL: $testUrl<br>";

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($testUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data) {
        echo "✓ File API responding<br>";
        echo "Files found: " . count($data) . "<br>";
        if (count($data) > 0) {
            echo "Sample file: " . $data[0]['name'] . "<br>";
        }
    } else {
        echo "✗ File API returned invalid JSON: $response<br>";
    }
} else {
    echo "✗ File API not accessible. Make sure your web server is running.<br>";
}

// Test Git API
echo "<h2>4. Testing Git API</h2>";
$gitTestUrl = 'http://localhost/backend/monaco_git_api.php?project=test-project&action=status';
echo "Testing URL: $gitTestUrl<br>";

$gitResponse = @file_get_contents($gitTestUrl, false, $context);
if ($gitResponse !== false) {
    $gitData = json_decode($gitResponse, true);
    if ($gitData) {
        echo "✓ Git API responding<br>";
        if ($gitData['success']) {
            echo "Git status: " . count($gitData['changes']) . " changes<br>";
        } else {
            echo "Git API error: " . ($gitData['error'] ?? 'Unknown error') . "<br>";
        }
    } else {
        echo "✗ Git API returned invalid JSON: $gitResponse<br>";
    }
} else {
    echo "✗ Git API not accessible<br>";
}

echo "<h2>5. File Permissions Test</h2>";
if (is_writable($dataDir)) {
    echo "✓ Data directory is writable<br>";
} else {
    echo "✗ Data directory is not writable. Check permissions.<br>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p>Your Monaco IDE backend is ready. Make sure to:</p>";
echo "<ul>";
echo "<li>Start your web server (Apache/Nginx)</li>";
echo "<li>Make sure PHP has exec() function enabled for Git operations</li>";
echo "<li>Ensure the data directory is writable by the web server</li>";
echo "</ul>";
?>
