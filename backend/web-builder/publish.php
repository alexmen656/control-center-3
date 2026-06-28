<?php
require_once __DIR__ . '/api_base.php';

define('PUBLISH_WEBHOOK_URL', 'https://webhook.control-center.eu/publish_web_builder.php');
define('PUBLISH_WEBHOOK_SECRET', 'cc_web_builder_publish_secret_2025');

$userId = authenticateUser();
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$deployToServer = isset($_GET['deploy']) && ($_GET['deploy'] === 'true' || $_GET['deploy'] === '1');

if (!$projectId) {
    sendError('Project ID is required', 400);
}

$projectResult = query("SELECT * FROM control_center_modul_web_builder_projects WHERE id = $projectId");

if (!$projectResult || mysqli_num_rows($projectResult) === 0) {
    sendError('Project not found', 404);
}

$project = fetch_assoc($projectResult);
$ccProjectId = $project['project_id'];

if (!userHasProjectAccess($userId, $ccProjectId)) {
    sendError('Access denied to this project', 403);
}

$ccProject = getControlCenterProject($ccProjectId);

if (!$ccProject) {
    sendError('Linked Fringelo project not found', 404);
}

$projectSlug = $ccProject['link'];
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
    $componentsResult = query("SELECT * FROM control_center_modul_web_builder_components 
                               WHERE page_id = $pageId 
                               ORDER BY position ASC");

    $components = [];
    if ($componentsResult) {
        while ($comp = fetch_assoc($componentsResult)) {
            $components[] = $comp;
        }
    }

    $htmlContent = generatePageHtml($pageTitle, $pageMetaDescription, $components, $projectSlug, $ccProjectId);
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

$cssContent = getStylesCSS();
if ($cssContent) {
    $generatedFiles[] = [
        'filename' => 'styles.css',
        'content' => $cssContent
    ];
}

$deploymentResult = null;
$domain = null;

if ($deployToServer) {
    $domainResult = query("SELECT domain FROM web_builder_domains 
                           WHERE projectID = '" . escape_string($projectSlug) . "' 
                           AND is_enabled = 1 
                           LIMIT 1");

    if ($domainResult && mysqli_num_rows($domainResult) > 0) {
        $domainRow = fetch_assoc($domainResult);
        $domain = $domainRow['domain'];

        $filesToDeploy = array_map(function ($file) {
            return [
                'filename' => $file['filename'],
                'content' => $file['content']
            ];
        }, $generatedFiles);

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

$response = [
    'success' => true,
    'project' => [
        'id' => $project['id'],
        'name' => $project['name'],
        'ccProjectId' => $ccProjectId,
        'ccProjectSlug' => $projectSlug
    ],
    'generated' => [
        'files' => array_map(function ($f) {
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
 * @param int $ccProjectId - Fringelo project ID for content lookup
 * @return string - HTML with resolved dynamic content
 */
function processDynamicContent($html, $ccProjectId)
{
    // Pattern to match {{table_name.column_name[index] | modifiers}}
    // Allows for | filter:arg | ...
    $pattern = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]((?:\s*\|\s*[^\}]+)*)\}\}/';
    preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        return $html;
    }

    $tableQueries = [];
    foreach ($matches as $match) {
        $tableName = $match[1];
        $columnName = $match[2];
        $index = intval($match[3]);
        $modifiers = $match[4] ?? '';

        if (!isset($tableQueries[$tableName])) {
            $tableQueries[$tableName] = [];
        }
        $tableQueries[$tableName][] = [
            'fullMatch' => $match[0],
            'column' => $columnName,
            'index' => $index,
            'modifiers' => $modifiers
        ];
    }

    $resolvedContent = [];
    foreach ($tableQueries as $tableName => $columns) {
        $tableData = getCCFormsTableData($tableName);

        foreach ($columns as $colInfo) {
            $key = $colInfo['fullMatch'];
            $value = getCCFormsColumnValue($tableData, $colInfo['column'], $colInfo['index']);

            if (!empty($colInfo['modifiers'])) {
                $value = applyValueFilters($value, $colInfo['modifiers']);
            }

            $resolvedContent[$key] = $value;
        }
    }

    foreach ($resolvedContent as $syntax => $value) {
        $html = str_replace($syntax, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $html);
    }

    return $html;
}

/**
 * Process loop syntax in HTML
 * Support for {% for item in table | filter:key=val | sort:key:desc | limit:n %} ... {{item.field}} ... {% endfor %}
 * 
 * @param string $html - HTML content
 * @param int $ccProjectId - Project ID (unused in current impl but kept for consistent signature)
 * @return string - HTML with loops processed
 */
function processLoops($html, $ccProjectId)
{
    // Pattern to match {% for var in table [modifiers] %} ... {% endfor %}
    $pattern = '/\{%\s*for\s+([a-zA-Z0-9_]+)\s+in\s+([a-zA-Z0-9_]+)((?:\s*\|\s*[a-zA-Z0-9_=:"\'-]+)*)\s*%\}(.*?)\{%\s*endfor\s*%\}/s';

    return preg_replace_callback($pattern, function ($matches) {
        $loopVar = $matches[1];
        $tableName = $matches[2];
        $modifiersStr = $matches[3];
        $template = $matches[4];
        $tableData = getCCFormsTableData($tableName);

        if (empty($tableData)) {
            return '';
        }

        if (!empty($modifiersStr)) {
            $modifiers = explode('|', $modifiersStr);
            foreach ($modifiers as $mod) {
                $mod = trim($mod);
                if (empty($mod))
                    continue;

                if (strpos($mod, 'filter:') === 0) {
                    $parts = explode('=', substr($mod, 7));
                    if (count($parts) >= 2) {
                        $key = trim($parts[0], " \"'");
                        $val = trim(substr($mod, 7 + strlen($parts[0]) + 1), " \"'");

                        $tableData = array_filter($tableData, function ($row) use ($key, $val) {
                            if (!isset($row[$key]))
                                return false;
                            return (string) $row[$key] === (string) $val;
                        });
                    }
                }

                // Sort: sort:key:order or sort:'key'
                if (strpos($mod, 'sort:') === 0) {
                    $args = substr($mod, 5);
                    $parts = explode(':', $args);

                    $key = trim($parts[0] ?? 'id', " \"'");
                    $order = strtolower(trim($parts[1] ?? 'asc'));

                    usort($tableData, function ($a, $b) use ($key, $order) {
                        $valA = $a[$key] ?? '';
                        $valB = $b[$key] ?? '';

                        if (is_numeric($valA) && is_numeric($valB)) {
                            $cmp = $valA - $valB;
                        } else {
                            $cmp = strcmp($valA, $valB);
                        }

                        return $order === 'desc' ? -$cmp : $cmp;
                    });
                }

                // Reverse
                if ($mod === 'reverse') {
                    $tableData = array_reverse($tableData);
                }

                // Limit: limit:n
                if (strpos($mod, 'limit:') === 0) {
                    $limit = intval(substr($mod, 6));
                    if ($limit > 0) {
                        $tableData = array_slice($tableData, 0, $limit);
                    }
                }
            }
        }

        $output = '';
        foreach ($tableData as $row) {
            $rowHtml = $template;

            // 1. Replace {{loopVar.column}} within the loop (visual output)
            // Updated to allow whitespace around dot: {{ member . name }}
            // Also supports modifiers: {{ member.name | upper }}
            $variablePattern = '/\{\{\s*' . preg_quote($loopVar, '/') . '\s*\.\s*([a-zA-Z0-9_]+)((?:\s*\|\s*[^\}]+)*)\s*\}\}/';

            $rowHtml = preg_replace_callback($variablePattern, function ($varMatches) use ($row) {
                $column = $varMatches[1];
                $modifiers = $varMatches[2] ?? '';
                $val = getCCFormsColumnValue([$row], $column, 0);

                if (!empty($modifiers)) {
                    $val = applyValueFilters($val, $modifiers);
                }

                return htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
            }, $rowHtml);

            // 2. Prepare variables for conditional logic inside the loop
            // Find all {% if ... %} tags and replace all occurrences of loopVar.field inside them
            // Added 's' modifier to handle multiline tags
            // Note: We use a non-greedy regex inside to avoid replacing content inside strings
            $rowHtml = preg_replace_callback('/\{%\s*if\s+(.+?)\s*%\}/s', function ($match) use ($loopVar, $row) {
                $content = $match[1];

                // Replace loopVar.field -> "value" (multiple occurrences supported)
                // Use slightly stricter regex to avoid matching inside typical string literals if possible,
                // but perfect parsing is hard. This assumes variables are not inside quotes in the condition.
                // We match loopVar.field NOT preceded by a quote.
                // Updated to allow whitespace around the dot: member . field
                $replacedContent = preg_replace_callback('/(?<![\'"])' . preg_quote($loopVar, '/') . '\s*\.\s*([a-zA-Z0-9_]+)(?![\'"])/', function ($m) use ($row) {
                    $val = getCCFormsColumnValue([$row], $m[1], 0);
                    // Escape only double quotes and backslashes for the double-quoted string wrapper
                    // This avoids over-escaping single quotes which causes comparison mismatches
                    $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $val);
                    return '"' . $escaped . '"';
                }, $content);

                return '{% if ' . $replacedContent . ' %}';
            }, $rowHtml);

            $output .= $rowHtml;
        }

        return $output;
    }, $html);
}

/**
 * Process conditional logic (IF statements)
 * Supports: {% if var %} or {% if var == "value" %} or {% if var != "value" %}
 * 
 * @param string $html
 * @param int $ccProjectId
 * @return string
 */
function processConditions($html, $ccProjectId)
{
    // Regex for innermost IF blocks (ones that don't contain other IFs)
    // Group 1: Condition
    // Group 2: Content
    // We iterate until no more IF blocks are found/resolved to handle nesting
    $pattern = '/\{%\s*if\s+((?:(?!\{%\s*if\s).)*?)\s*%\}((?:(?!\{%\s*if\s).)*?)\{%\s*endif\s*%\}/s';

    $maxIterations = 50; // Safety limit

    do {
        $count = 0;
        $newHtml = preg_replace_callback($pattern, function ($matches) {
            $condition = trim($matches[1]);
            $content = $matches[2];
            // Handle optional {% else %} block
            $trueContent = $content;
            $falseContent = '';

            $elseSplit = preg_split('/\{%\s*else\s*%\}/', $content, 2);
            if (count($elseSplit) > 1) {
                $trueContent = $elseSplit[0];
                $falseContent = $elseSplit[1];
            }
            // Resolve variables in condition (table.col[i])
            // Matches table_name.column_name[index]
            $varPattern = '/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\[(\d+)\]/';
            $condition = preg_replace_callback($varPattern, function ($v) {
                $table = $v[1];
                $col = $v[2];
                $idx = intval($v[3]);
                $data = getCCFormsTableData($table);
                $val = getCCFormsColumnValue($data, $col, $idx);
                // Escape only double quotes and backslashes
                $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $val);
                return '"' . $escaped . '"';
            }, $condition);

            // Evaluate condition
            $isTrue = false;

            // Helper regexes for quoted strings with correct backreferences for equality checks
            // First string (Groups 1-2)
            $strRegex1 = '(["\'])((?:(?!\1|\\\\).|\\\\.)*)\1';
            // Second string (Groups 3-4)
            $strRegex2 = '(["\'])((?:(?!\3|\\\\).|\\\\.)*)\3';

            $matched = false;

            // 1. Equality: "a" == "b"
            if (preg_match('/^' . $strRegex1 . '\s*==\s*' . $strRegex2 . '$/s', $condition, $cMatches)) {
                $isTrue = ($cMatches[2] == $cMatches[4]);
                $matched = true;
            }
            // 2. Inequality: "a" != "b"
            elseif (preg_match('/^' . $strRegex1 . '\s*!=\s*' . $strRegex2 . '$/s', $condition, $cMatches)) {
                $isTrue = ($cMatches[2] != $cMatches[4]);
                $matched = true;
            }
            // 3. Existence/Truthiness: "value" (checks if not empty)
            elseif (preg_match('/^' . $strRegex1 . '$/s', $condition, $cMatches)) {
                $val = $cMatches[2];
                $isTrue = !empty($val) && $val !== 'false' && $val !== '0';
                $matched = true;
            }

            // If condition unrecognized (e.g. contains loop variables not yet replaced), preserve it
            // This is important because we might be in the first pass before processLoops
            if (!$matched) {
                return $matches[0];
            }

            return $isTrue ? $trueContent : $falseContent;
        }, $html, -1, $count);

        // Break if no matches found
        if ($count === 0) {
            break;
        }

        // Break if HTML didn't change (avoid infinite loop if matches are not being resolved/replaced)
        if ($newHtml === $html) {
            break;
        }

        $html = $newHtml;
        $maxIterations--;

    } while ($maxIterations > 0);

    return $html;
}

/**
 * Get data from CC Forms table
 * CC Forms creates tables with naming convention: {project}_{form_name}
 * 
 * @param string $tableName - The CC Forms table name (e.g., "myproject_kontaktformular")
 * @return array - Array of rows from the table
 */
function getCCFormsTableData($tableName)
{
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
function getCCFormsColumnValue($tableData, $columnName, $index)
{
    if (!is_array($tableData) || empty($tableData)) {
        return '';
    }

    if ($index < 0 || $index >= count($tableData)) {
        return '';
    }

    $row = $tableData[$index];

    // Case-insensitive check
    if (!isset($row[$columnName])) {
        // Try searching case-insensitively
        $lowerColumnName = strtolower($columnName);
        foreach ($row as $key => $value) {
            // Trim key to handle potential trailing spaces in DB column names
            if (strtolower(trim($key)) === $lowerColumnName) {
                return (string) $value;
            }
        }
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
function removeDynamicContentBadges($html)
{
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
function generatePageHtml($title, $metaDescription, $components, $projectSlug = '', $ccProjectId = null)
{
    $componentsHtml = '';
    foreach ($components as $component) {
        $componentsHtml .= $component['html_code'] . "\n";
    }

    // Extract {% raw %}...{% endraw %} blocks to prevent processing inside them
    $rawBlocks = [];
    $componentsHtml = preg_replace_callback('/\{%\s*raw\s*%\}(.*?)\{%\s*endraw\s*%\}/s', function ($matches) use (&$rawBlocks) {
        $placeholder = '<!--RAW_BLOCK_' . count($rawBlocks) . '-->';
        $rawBlocks[] = $matches[1]; // Store content without the raw tags
        return $placeholder;
    }, $componentsHtml);

    // Process dynamic content if project ID is available
    if ($ccProjectId) {
        $componentsHtml = processConditions($componentsHtml, $ccProjectId);
        $componentsHtml = processLoops($componentsHtml, $ccProjectId);
        // Run conditions again after loops to handle conditions inside loops
        $componentsHtml = processConditions($componentsHtml, $ccProjectId);
        $componentsHtml = processDynamicContent($componentsHtml, $ccProjectId);
    }

    // Restore raw blocks
    foreach ($rawBlocks as $index => $content) {
        $componentsHtml = str_replace('<!--RAW_BLOCK_' . $index . '-->', $content, $componentsHtml);
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
function getCCFormsScript($projectSlug)
{
    return <<<SCRIPT
<!-- CC Forms Integration -->
<script>
(function() {
    'use strict';
    
    const CC_FORMS_API = 'https://api.fringelo.com/api/public_form_submit.php';
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
function getStylesCSS()
{
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

/**
 * Apply filters to a value
 * 
 * @param string $value
 * @param string $modifiersStr - String of modifiers (e.g. "| upper | truncate:50")
 * @return string
 */
function applyValueFilters($value, $modifiersStr)
{
    if (empty($modifiersStr))
        return $value;

    // Split by pipe, but we need to respect quoted pipes if any (rare but possible in default:'..|..')
    // For simplicity, we split by pipe. If robust parsing is needed for pipes in args, a regex split is better.
    // Generally argument values shouldn't contain pipes.
    $modifiers = explode('|', $modifiersStr);

    foreach ($modifiers as $mod) {
        $mod = trim($mod);
        if (empty($mod))
            continue;

        // Parse modifier and arguments
        // Name is until first colon
        $parts = explode(':', $mod, 2);
        $name = strtolower(trim($parts[0]));
        $argsStr = isset($parts[1]) ? $parts[1] : '';

        $args = [];
        if ($argsStr !== '') {
            // Smart split of arguments respecting quotes
            // Regex to match quoted strings OR non-colon sequences
            if (preg_match_all('/(?:[\'"]([^\'"]*)[\'"]|([^:]+))/', $argsStr, $argMatches)) {
                foreach ($argMatches[0] as $fullMatch) {
                    $args[] = trim($fullMatch, ": \"'");
                }
            }
        }

        switch ($name) {
            case 'upper':
            case 'uppercase':
                $value = mb_strtoupper((string) $value, 'UTF-8');
                break;
            case 'lower':
            case 'lowercase':
                $value = mb_strtolower((string) $value, 'UTF-8');
                break;
            case 'capitalize':
            case 'capfirst':
                $val = (string) $value;
                if (mb_strlen($val) > 0) {
                    $first = mb_substr($val, 0, 1, 'UTF-8');
                    $rest = mb_substr($val, 1, null, 'UTF-8');
                    $value = mb_strtoupper($first, 'UTF-8') . $rest;
                }
                break;
            case 'truncate':
                $length = intval($args[0] ?? 50);
                $append = isset($args[1]) ? $args[1] : '...';
                if (mb_strlen((string) $value, 'UTF-8') > $length) {
                    $value = mb_substr((string) $value, 0, $length, 'UTF-8') . $append;
                }
                break;
            case 'default':
                $val = (string) $value;
                if (empty($val) || $val === '0' || $val === 'false') {
                    // For default, we might want the raw remainder if parsing failed or was complicated
                    $defaultVal = isset($args[0]) ? $args[0] : '';
                    $value = $defaultVal;
                }
                break;
            case 'slice':
                $start = intval($args[0] ?? 0);
                $length = isset($args[1]) ? intval($args[1]) : null;
                $value = mb_substr((string) $value, $start, $length, 'UTF-8');
                break;
            case 'trim':
                $value = trim((string) $value);
                break;
            case 'striptags':
                $value = strip_tags((string) $value);
                break;
        }
    }

    return $value;
}
