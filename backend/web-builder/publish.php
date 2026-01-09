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
    
    // Generate HTML (pass projectSlug for CC Forms integration and ccProjectId for dynamic content)
    $htmlContent = generatePageHtml($pageTitle, $pageMetaDescription, $components, $projectSlug, $ccProjectId);
    
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
 * Process dynamic content syntax in HTML
 * Replaces {{table_name.column_name[index]}} with actual content from CC Forms database tables
 * 
 * @param string $html - HTML content with dynamic content syntax
 * @param int $ccProjectId - Control Center project ID for content lookup
 * @return string - HTML with resolved dynamic content
 */
function processDynamicContent($html, $ccProjectId) {
    // Pattern to match {{table_name.column_name[index]}}
    $pattern = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]\}\}/';
    
    // Find all matches first to batch database queries
    preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        return $html;
    }
    
    // Group by table name for efficient querying
    $tableQueries = [];
    foreach ($matches as $match) {
        $tableName = $match[1];
        $columnName = $match[2];
        $index = intval($match[3]);
        
        if (!isset($tableQueries[$tableName])) {
            $tableQueries[$tableName] = [];
        }
        $tableQueries[$tableName][] = [
            'fullMatch' => $match[0],
            'column' => $columnName,
            'index' => $index
        ];
    }
    
    // Fetch content for each table (CC Forms tables)
    $resolvedContent = [];
    foreach ($tableQueries as $tableName => $columns) {
        $tableData = getCCFormsTableData($tableName);
        
        foreach ($columns as $colInfo) {
            $key = $colInfo['fullMatch'];
            $value = getCCFormsColumnValue($tableData, $colInfo['column'], $colInfo['index']);
            $resolvedContent[$key] = $value;
        }
    }
    
    // Replace all dynamic content in HTML
    foreach ($resolvedContent as $syntax => $value) {
        $html = str_replace($syntax, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
    }
    
    return $html;
}

/**
 * Get data from CC Forms table
 * CC Forms creates tables with naming convention: {project}_{form_name}
 * 
 * @param string $tableName - The CC Forms table name (e.g., "myproject_kontaktformular")
 * @return array - Array of rows from the table
 */
function getCCFormsTableData($tableName) {
    $tableName = escape_string($tableName);
    
    // Check if table exists
    $tableExists = query("SHOW TABLES LIKE '$tableName'");
    if (!$tableExists || mysqli_num_rows($tableExists) === 0) {
        return [];
    }
    
    // Get all rows from the CC Forms data table
    $result = query("SELECT * FROM `$tableName` ORDER BY id ASC");
    
    $data = [];
    if ($result) {
        while ($row = fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    return $data;
}

/**
 * Get a specific value from CC Forms table data
 * 
 * @param array $tableData - Array of rows from getCCFormsTableData
 * @param string $columnName - Column to get
 * @param int $index - Row index (0-based)
 * @return string - The value or empty string if not found
 */
function getCCFormsColumnValue($tableData, $columnName, $index) {
    if (!is_array($tableData) || empty($tableData)) {
        return '';
    }
    
    if ($index < 0 || $index >= count($tableData)) {
        return '';
    }
    
    $row = $tableData[$index];
    
    if (!isset($row[$columnName])) {
        return '';
    }
    
    return (string) $row[$columnName];
}

/**
 * Remove dynamic content badge HTML elements from output
 * These badges are editor artifacts and should not appear in published HTML
 * 
 * @param string $html - HTML content
 * @return string - HTML with badges removed
 */
function removeDynamicContentBadges($html) {
    // Remove span elements with data-cc-dynamic attribute
    // Pattern matches: <span class="cc-dynamic-badge..." data-cc-dynamic="true" ...>content</span>
    $pattern = '/<span[^>]*data-cc-dynamic="true"[^>]*>[^<]*<\/span>/i';
    $html = preg_replace($pattern, '', $html);
    
    // Also remove any badge style classes that might be left over
    $html = preg_replace('/\s*class="cc-dynamic-badge[^"]*"/i', '', $html);
    
    return $html;
}

/**
 * Generate HTML page from components
 */
function generatePageHtml($title, $metaDescription, $components, $projectSlug = '', $ccProjectId = null) {
    $componentsHtml = '';
    foreach ($components as $component) {
        $componentsHtml .= $component['html_code'] . "\n";
    }
    
    // Process dynamic content if project ID is available
    if ($ccProjectId) {
        $componentsHtml = processDynamicContent($componentsHtml, $ccProjectId);
    }
    
    // Remove any remaining dynamic content badges (editor artifacts)
    $componentsHtml = removeDynamicContentBadges($componentsHtml);
    
    // Escape special characters for HTML
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $metaDescription = htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8');
    $projectSlugJs = htmlspecialchars($projectSlug, ENT_QUOTES, 'UTF-8');
    
    // CC Forms Integration Script
    $ccFormsScript = getCCFormsScript($projectSlugJs);
    
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
        /* CC Forms Styles */
        .cc-form-loading { opacity: 0.6; pointer-events: none; }
        .cc-form-success { background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; }
        .cc-form-error { background: #ef4444; color: white; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; }
    </style>
</head>
<body class="antialiased">
{$componentsHtml}
{$ccFormsScript}
</body>
</html>
HTML;
}

/**
 * Generate CC Forms JavaScript for form submissions
 */
function getCCFormsScript($projectSlug) {
    return <<<SCRIPT
<!-- CC Forms Integration -->
<script>
(function() {
    'use strict';
    
    const CC_FORMS_API = 'https://alex.polan.sk/control-center/api/public_form_submit.php';
    const CC_PROJECT = '{$projectSlug}';
    
    // Find all forms with data-cc-form attribute
    function initCCForms() {
        const forms = document.querySelectorAll('form[data-cc-form]');
        forms.forEach(setupCCForm);
        
        // Also watch for dynamically added forms
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        if (node.matches && node.matches('form[data-cc-form]')) {
                            setupCCForm(node);
                        }
                        const nestedForms = node.querySelectorAll && node.querySelectorAll('form[data-cc-form]');
                        if (nestedForms) nestedForms.forEach(setupCCForm);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
    
    function setupCCForm(form) {
        if (form.dataset.ccFormInitialized) return;
        form.dataset.ccFormInitialized = 'true';
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formName = form.dataset.ccForm;
            const project = form.dataset.ccProject || CC_PROJECT;
            const successMessage = form.dataset.ccSuccess || 'Erfolgreich gesendet!';
            const errorMessage = form.dataset.ccError || 'Fehler beim Senden. Bitte versuchen Sie es erneut.';
            
            // Get submit button
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.textContent || submitBtn.value : '';
            
            // Show loading state
            form.classList.add('cc-form-loading');
            if (submitBtn) {
                submitBtn.disabled = true;
                if (submitBtn.tagName === 'BUTTON') submitBtn.textContent = 'Wird gesendet...';
                else submitBtn.value = 'Wird gesendet...';
            }
            
            // Remove previous messages
            const oldMsg = form.querySelector('.cc-form-message');
            if (oldMsg) oldMsg.remove();
            
            // Collect form data
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                // Handle multiple values (checkboxes with same name)
                if (data[key]) {
                    if (Array.isArray(data[key])) data[key].push(value);
                    else data[key] = [data[key], value];
                } else {
                    data[key] = value;
                }
            });
            
            try {
                const response = await fetch(CC_FORMS_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        project: project,
                        form_name: formName,
                        data: data,
                        source: 'web-builder'
                    })
                });
                
                const result = await response.json();
                
                // Create message element
                const msgEl = document.createElement('div');
                msgEl.className = 'cc-form-message';
                
                if (result.success) {
                    msgEl.className += ' cc-form-success';
                    msgEl.textContent = successMessage;
                    form.reset();
                    
                    // Custom success callback
                    if (typeof window.onCCFormSuccess === 'function') {
                        window.onCCFormSuccess(formName, result, form);
                    }
                } else {
                    msgEl.className += ' cc-form-error';
                    msgEl.textContent = result.error || errorMessage;
                    
                    // Custom error callback
                    if (typeof window.onCCFormError === 'function') {
                        window.onCCFormError(formName, result, form);
                    }
                }
                
                form.appendChild(msgEl);
                
                // Remove message after 5 seconds
                setTimeout(() => msgEl.remove(), 5000);
                
            } catch (error) {
                console.error('CC Forms Error:', error);
                
                const msgEl = document.createElement('div');
                msgEl.className = 'cc-form-message cc-form-error';
                msgEl.textContent = errorMessage;
                form.appendChild(msgEl);
                
                setTimeout(() => msgEl.remove(), 5000);
            } finally {
                // Reset loading state
                form.classList.remove('cc-form-loading');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.tagName === 'BUTTON') submitBtn.textContent = originalBtnText;
                    else submitBtn.value = originalBtnText;
                }
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCCForms);
    } else {
        initCCForms();
    }
})();
</script>
SCRIPT;
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
