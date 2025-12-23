<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

/**
 * Publish Script
 * 
 * This script generates HTML pages for a specified project.
 * It retrieves all pages and their components from the database and creates static HTML files.
 * The page with the lowest ID becomes index.html, others are named as {slug}.html
 * 
 * Parameter:
 * - project_id: ID des zu veröffentlichenden Projekts (erforderlich)
 * - css: Bei 'true' wird die CSS-Datei optimiert, um unbenutzte Klassen zu entfernen (optional)
 */

// CSS-Optimizer-Klasse einbinden
require_once 'css-optimizer.php';

// Check if project_id is provided
if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    die('Error: Project ID is required. Use ?project_id=X in the URL.');
}

$projectId = (int)$_GET['project_id'];

// Überprüfen, ob CSS-Optimierung aktiviert werden soll
$optimizeCss = isset($_GET['css']) && ($_GET['css'] === 'true' || $_GET['css'] === '1');

// Database connection
$dbConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'alex01d01',
    'username' => 'alex01d01',
    'password' => 'XL2fiPeVH3'
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}

// Check if project exists
$stmtProject = $pdo->prepare('SELECT * FROM control_center_web_builder_projects WHERE id = ?');
$stmtProject->execute([$projectId]);
$project = $stmtProject->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die('Error: Project not found.');
}

// Create output directory for the project
$outputDir = "published/{$projectId}/";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Copy assets (CSS) to the published directory
function copyAssets($outputDir) {
    // Create assets directory in published folder if it doesn't exist
    if (!is_dir($outputDir . 'assets/')) {
        mkdir($outputDir . 'assets/', 0755, true);
    }
    
    // Copy styles.css to published directory
    $sourceStylesCss = 'assets/styles.css';
    $destStylesCss = $outputDir . 'styles.css';
    
    if (file_exists($sourceStylesCss)) {
        copy($sourceStylesCss, $destStylesCss);
        echo "Copied styles.css to published directory<br>";
    } else {
        echo "Warning: Could not find styles.css in assets directory<br>";
    }
}

// Copy assets before generating HTML files
copyAssets($outputDir);

// Get all pages for the project, ordered by ID
$stmtPages = $pdo->prepare('SELECT * FROM control_center_web_builder_pages WHERE project_id = ? ORDER BY id ASC');
$stmtPages->execute([$projectId]);
$pages = $stmtPages->fetchAll(PDO::FETCH_ASSOC);

if (count($pages) === 0) {
    die('Error: No pages found for this project.');
}

// For each page, get components ordered by position
$stmtComponents = $pdo->prepare('
    SELECT * FROM control_center_web_builder_components 
    WHERE page_id = ? 
    ORDER BY position ASC
');

// Keep track of the first page to set as index.html
$firstPage = null;

// Process each page
foreach ($pages as $page) {
    // For the first page in the results (lowest ID)
    if ($firstPage === null) {
        $firstPage = $page;
    }
    
    $pageId = $page['id'];
    $pageSlug = $page['slug'];
    $pageTitle = $page['title'];
    $pageMetaDescription = $page['meta_description'];
    
    // Get components for this page
    $stmtComponents->execute([$pageId]);
    $components = $stmtComponents->fetchAll(PDO::FETCH_ASSOC);
    
    // Start output buffering to capture the rendered PHP templates
    ob_start();
    
    // Include header template
    include 'header_template.php';
    
    // Add all components in order
    foreach ($components as $component) {
        echo $component['html_code'] . "\n";
    }
    
    // Include footer template
    include 'footer_template.php';
    
    // Get the complete HTML content
    $htmlContent = ob_get_clean();
    
    // Determine filename (index.html for first page, {slug}.html for others)
    $filename = ($page['id'] === $firstPage['id']) ? "index.html" : "{$pageSlug}.html";
    $filePath = $outputDir . $filename;
    
    // Write the HTML file
    if (file_put_contents($filePath, $htmlContent) === false) {
        echo "Failed to write file: {$filename}<br>";
    } else {
        echo "Successfully created: {$filename}<br>";
    }
}

// Nach dem Generieren aller HTML-Seiten: CSS optimieren, wenn gewünscht
if ($optimizeCss) {
    echo "<h3>Optimiere CSS für Projekt...</h3>";
    echo "<pre>";
    // Output-Buffer für bessere Lesbarkeit der Konsole
    ob_start();
    $cssOptimized = optimizeCssForProject($projectId);
    $cssOutput = ob_get_clean();
    // Ersetze Newlines mit BR-Tags für HTML-Ausgabe
    echo nl2br(htmlspecialchars($cssOutput));
    echo "</pre>";
    if ($cssOptimized) {
        echo "<p>CSS wurde erfolgreich optimiert und verkleinert!</p>";
    } else {
        echo "<p>CSS konnte nicht optimiert werden. Vollständige CSS-Datei wurde verwendet.</p>";
    }
} else {
    echo "<h3>CSS-Optimierung übersprungen</h3>";
    echo "<p>CSS-Datei wurde unverändert übernommen. Füge '?css=true' zur URL hinzu, um ungenutzte CSS-Klassen zu entfernen.</p>";
}

echo "<h3>Publishing complete for project '{$project['name']}'</h3>";
echo "<p>Files have been saved to: {$outputDir}</p>";
echo "<ul>";
echo "<li>Homepage: <a href='{$outputDir}index.html' target='_blank'>index.html</a></li>";

// List other pages
foreach ($pages as $page) {
    if ($page['id'] !== $firstPage['id']) {
        echo "<li>{$page['name']}: <a href='{$outputDir}{$page['slug']}.html' target='_blank'>{$page['slug']}.html</a></li>";
    }
}
echo "</ul>";
?>