<?php
/**
 * Web Builder Publish API
 * 
 * Generates static HTML files from Web Builder project and
 * optionally deploys them to the publish server.
 * 
 * Usage:
 * GET /publish.php?project_id=X           - Generate HTML files
 * GET /publish.php?project_id=X&deploy=1  - Generate and deploy
 * 
 * Requires authentication via JWT token
 */

require_once __DIR__ . '/api_base.php';

// Webhook-Konfiguration für Deployment
define('PUBLISH_WEBHOOK_URL', 'https://webhook.control-center.eu/publish_web_builder.php');
define('PUBLISH_WEBHOOK_SECRET', 'cc_web_builder_publish_secret_2025');

// Authenticate user
$userId = authenticateUser();

// Get project ID from request
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$deployToServer = isset($_GET['deploy']) && ($_GET['deploy'] === 'true' || $_GET['deploy'] === '1');

if (!$projectId) {
    sendError('Project ID is required', 400);
}

// Get Web Builder project
$projectResult = query("SELECT * FROM control_center_modul_web_builder_projects WHERE id = $projectId");
if (!$projectResult || mysqli_num_rows($projectResult) === 0) {
    sendError('Project not found', 404);
}
$project = fetch_assoc($projectResult);

// Verify user has access to the linked Control Center project
$ccProjectId = $project['project_id'];
if (!userHasProjectAccess($userId, $ccProjectId)) {
    sendError('Access denied to this project', 403);
}

// Get Control Center project info (for slug)
$ccProject = getControlCenterProject($ccProjectId);
if (!$ccProject) {
    sendError('Linked Control Center project not found', 404);
}
$projectSlug = $ccProject['link'];

// Get all pages for this project
$pagesResult = query("SELECT * FROM control_center_modul_web_builder_pages 
                      WHERE project_id = $projectId 
                      ORDER BY is_home DESC, id ASC");

if (!$pagesResult || mysqli_num_rows($pagesResult) === 0) {
    sendError('No pages found for this project', 404);
}

$pages = [];
while ($row = fetch_assoc($pagesResult)) {
    $pages[] = $row;
}

// Generate HTML files
$generatedFiles = [];
$firstPage = null;

foreach ($pages as $page) {
    if ($firstPage === null) {
        $firstPage = $page;
    }
    
    $pageId = $page['id'];
    $pageSlug = $page['slug'];
    $pageTitle = $page['title'] ?: $page['name'];
    $pageMetaDescription = $page['meta_description'] ?: '';
    
    // Get components for this page
    $componentsResult = query("SELECT * FROM control_center_modul_web_builder_components 
                               WHERE page_id = $pageId 
                               ORDER BY position ASC");
    
    $components = [];
    if ($componentsResult) {
        while ($comp = fetch_assoc($componentsResult)) {
            $components[] = $comp;
        }
    }
    
    // Generate HTML
    $htmlContent = generatePageHtml($pageTitle, $pageMetaDescription, $components);
    
    // Determine filename (index.html for home/first page)
    $filename = ($page['is_home'] || $page['id'] === $firstPage['id']) 
        ? 'index.html' 
        : $pageSlug . '.html';
    
    $generatedFiles[] = [
        'filename' => $filename,
        'content' => $htmlContent,
        'pageId' => $pageId,
        'pageName' => $page['name']
    ];
}

// Add CSS file
$cssContent = getStylesCSS();
if ($cssContent) {
    $generatedFiles[] = [
        'filename' => 'styles.css',
        'content' => $cssContent
    ];
}

// Deployment
$deploymentResult = null;
$domain = null;

if ($deployToServer) {
    // Check if domain is configured
    // Try both projectID and link since web_builder_domains might use either
    $domainResult = query("SELECT domain FROM web_builder_domains 
                           WHERE (projectID = '" . escape_string($ccProjectId) . "' 
                                  OR projectID = '" . escape_string($projectSlug) . "')
                           AND is_enabled = 1 
                           LIMIT 1");
    
    if ($domainResult && mysqli_num_rows($domainResult) > 0) {
        $domainRow = fetch_assoc($domainResult);
        $domain = $domainRow['domain'];
        
        // Prepare files for webhook (only filename and content)
        $filesToDeploy = array_map(function($file) {
            return [
                'filename' => $file['filename'],
                'content' => $file['content']
            ];
        }, $generatedFiles);
        
        // Send to webhook
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
            $deploymentResult = [
                'success' => false,
                'error' => 'Could not reach publish server'
            ];
        } else {
            $deploymentResult = json_decode($response, true);
        }
    } else {
        $deploymentResult = [
            'success' => false,
            'error' => 'No domain configured for this project'
        ];
    }
}

// Build response
$response = [
    'success' => true,
    'project' => [
        'id' => $project['id'],
        'name' => $project['name'],
        'ccProjectId' => $ccProjectId,
        'ccProjectSlug' => $projectSlug
    ],
    'generated' => [
        'files' => array_map(function($f) {
            return [
                'filename' => $f['filename'],
                'size' => strlen($f['content'])
            ];
        }, $generatedFiles),
        'totalFiles' => count($generatedFiles)
    ]
];

if ($deployToServer) {
    $response['deployment'] = [
        'attempted' => true,
        'success' => $deploymentResult['success'] ?? false,
        'domain' => $domain,
        'liveUrl' => $domain ? "https://$domain" : null,
        'error' => $deploymentResult['error'] ?? null,
        'publishedFiles' => $deploymentResult['published'] ?? []
    ];
} else {
    $response['deployment'] = [
        'attempted' => false,
        'hint' => 'Add &deploy=true to deploy to publish server'
    ];
}

sendResponse($response);

// ============================================
// Helper Functions
// ============================================

/**
 * Generate HTML page from components
 */
function generatePageHtml($title, $metaDescription, $components) {
    $componentsHtml = '';
    foreach ($components as $component) {
        $componentsHtml .= $component['html_code'] . "\n";
    }
    
    // Escape special characters for HTML
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $metaDescription = htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8');
    
    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{$metaDescription}">
    <title>{$title}</title>
    <!-- Tailwind CSS via CDN for reliability -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased">
{$componentsHtml}
</body>
</html>
HTML;
}

/**
 * Get Tailwind CSS styles
 */
function getStylesCSS() {
    // Try to load from the web-builder assets on the server
    $possiblePaths = [
        '/www/paxar/control-center/web-builder/backend_cc/assets/styles.css',
        __DIR__ . '/../../web-builder/backend_cc/assets/styles.css',
        __DIR__ . '/../site-builder/assets/styles.css'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return file_get_contents($path);
        }
    }
    
    // Fallback: return minimal custom CSS (Tailwind is loaded via CDN in HTML)
    return <<<CSS
/* Custom styles for Web Builder */
/* Tailwind CSS is loaded via CDN in the HTML */

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom focus styles */
*:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Hide scrollbar but keep functionality */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
CSS;
}
