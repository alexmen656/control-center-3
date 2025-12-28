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
 * - deploy: Bei 'true' werden die Dateien an den Publish-Server gesendet (optional)
 */

// CSS-Optimizer-Klasse einbinden
require_once 'css-optimizer.php';

// Webhook-Konfiguration für Deployment
define('PUBLISH_WEBHOOK_URL', 'https://webhook.control-center.eu/publish_web_builder.php');
define('PUBLISH_WEBHOOK_SECRET', 'cc_web_builder_publish_secret_2025');

// Check if project_id is provided
if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    die('Error: Project ID is required. Use ?project_id=X in the URL.');
}

$projectId = (int)$_GET['project_id'];

// Überprüfen, ob CSS-Optimierung aktiviert werden soll
$optimizeCss = isset($_GET['css']) && ($_GET['css'] === 'true' || $_GET['css'] === '1');

// Überprüfen, ob Deployment aktiviert werden soll
$deployToServer = isset($_GET['deploy']) && ($_GET['deploy'] === 'true' || $_GET['deploy'] === '1');

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

// ============================================
// DEPLOYMENT ZUM PUBLISH-SERVER
// ============================================
if ($deployToServer) {
    echo "<h3>Deploying to Publish Server...</h3>";
    
    // Hole den CC Project Link (project_id in der Web Builder Tabelle)
    $stmtProjectLink = $pdo->prepare('SELECT project_id FROM control_center_modul_web_builder_projects WHERE id = ?');
    $stmtProjectLink->execute([$projectId]);
    $projectLinkRow = $stmtProjectLink->fetch(PDO::FETCH_ASSOC);
    
    if (!$projectLinkRow || empty($projectLinkRow['project_id'])) {
        echo "<p style='color: orange;'>⚠️ Kein Control Center Projekt verknüpft. Deployment übersprungen.</p>";
    } else {
        $ccProjectLink = $projectLinkRow['project_id'];
        
        // Hole den CC Project Slug (link-Feld aus projects-Tabelle)
        $stmtCCProject = $pdo->prepare('SELECT link FROM projects WHERE projectID = ?');
        $stmtCCProject->execute([$ccProjectLink]);
        $ccProject = $stmtCCProject->fetch(PDO::FETCH_ASSOC);
        
        if (!$ccProject || empty($ccProject['link'])) {
            echo "<p style='color: orange;'>⚠️ Control Center Projekt nicht gefunden. Deployment übersprungen.</p>";
        } else {
            $projectSlug = $ccProject['link'];
            
            // Prüfe ob eine Domain konfiguriert ist
            $stmtDomain = $pdo->prepare('SELECT domain FROM web_builder_domains WHERE projectID = ? AND is_enabled = 1 LIMIT 1');
            $stmtDomain->execute([$ccProjectLink]);
            $domainRow = $stmtDomain->fetch(PDO::FETCH_ASSOC);
            
            if (!$domainRow) {
                echo "<p style='color: orange;'>⚠️ Keine Domain konfiguriert. Deployment übersprungen.</p>";
                echo "<p>Bitte zuerst eine Domain im Web Builder konfigurieren.</p>";
            } else {
                echo "<p>📤 Deploying zu: {$domainRow['domain']}</p>";
                echo "<p>Project Slug: {$projectSlug}</p>";
                
                // Sammle alle Dateien aus dem published-Verzeichnis
                $filesToDeploy = [];
                $publishedDir = $outputDir;
                
                if (is_dir($publishedDir)) {
                    $files = scandir($publishedDir);
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..') continue;
                        $filePath = $publishedDir . $file;
                        if (is_file($filePath)) {
                            $filesToDeploy[] = [
                                'filename' => $file,
                                'content' => file_get_contents($filePath)
                            ];
                        }
                    }
                }
                
                echo "<p>📁 " . count($filesToDeploy) . " Dateien zum Deployment bereit</p>";
                
                // Sende an Webhook
                $webhookData = [
                    'secret' => PUBLISH_WEBHOOK_SECRET,
                    'project_slug' => $projectSlug,
                    'files' => $filesToDeploy
                ];
                
                $opts = [
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => json_encode($webhookData),
                        'timeout' => 30
                    ]
                ];
                
                $context = stream_context_create($opts);
                $response = @file_get_contents(PUBLISH_WEBHOOK_URL, false, $context);
                
                if ($response === false) {
                    echo "<p style='color: red;'>❌ Deployment fehlgeschlagen - Server nicht erreichbar</p>";
                } else {
                    $result = json_decode($response, true);
                    if ($result && isset($result['success']) && $result['success']) {
                        echo "<p style='color: green;'>✅ Deployment erfolgreich!</p>";
                        echo "<p>Veröffentlichte Dateien: " . implode(', ', $result['published']) . "</p>";
                        echo "<p><a href='https://{$domainRow['domain']}' target='_blank'>🌐 Live ansehen: https://{$domainRow['domain']}</a></p>";
                    } else {
                        $errorMsg = isset($result['error']) ? $result['error'] : 'Unbekannter Fehler';
                        echo "<p style='color: red;'>❌ Deployment fehlgeschlagen: {$errorMsg}</p>";
                        if (isset($result['errors'])) {
                            echo "<pre>" . print_r($result['errors'], true) . "</pre>";
                        }
                    }
                }
            }
        }
    }
} else {
    echo "<h3>Deployment übersprungen</h3>";
    echo "<p>Füge '&deploy=true' zur URL hinzu, um die Seite auf dem Publish-Server zu veröffentlichen.</p>";
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