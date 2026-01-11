<?php
require_once __DIR__ . '/api_base.php';

define('PUBLISH_WEBHOOK_URL', 'https://webhook.control-center.eu/publish_web_builder.php');
define('PUBLISH_WEBHOOK_SECRET', 'cc_web_builder_publish_secret_2025');
define('CC_API_BASE', 'https://alex.polan.sk/control-center/web-builder');

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
    sendError('Linked Control Center project not found', 404);
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

$indexHtml = generateIndexHtml($project, $pages, $projectSlug);
$generatedFiles[] = [
    'filename' => 'index.html',
    'content' => $indexHtml
];

$cssContent = getStylesCSS();
$generatedFiles[] = [
    'filename' => 'styles.css',
    'content' => $cssContent
];

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

        $filesToDeploy = array_map(fn($file) => [
            'filename' => $file['filename'],
            'content' => $file['content']
        ], $generatedFiles);

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

        $deploymentResult = $response === false
            ? ['success' => false, 'error' => 'Could not reach publish server']
            : json_decode($response, true);
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
        'files' => array_map(fn($f) => [
            'filename' => $f['filename'],
            'size' => strlen($f['content'])
        ], $generatedFiles),
        'totalFiles' => count($generatedFiles)
    ],
    'features' => [
        'vueVersion' => '3.4',
        'routing' => 'vue-router',
        'dynamicData' => true,
        'noRebuildRequired' => true,
        'server' => 'nginx',
        'apiLocation' => 'remote (CC Server)'
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
// Generator Functions
// ============================================

function generateIndexHtml($project, $pages, $projectSlug)
{
    $projectName = htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8');
    $projectSlugJs = htmlspecialchars($projectSlug, ENT_QUOTES, 'UTF-8');
    $apiBase = CC_API_BASE;

    $routes = [];
    $homePageTitle = $projectName;
    $homePageDesc = '';

    foreach ($pages as $page) {
        $routes[] = [
            'path' => $page['is_home'] ? '/' : '/' . $page['slug'],
            'slug' => $page['slug'],
            'name' => $page['name'],
            'title' => $page['title'] ?: $page['name'],
            'meta_description' => $page['meta_description'] ?: ''
        ];

        if ($page['is_home']) {
            $homePageTitle = $page['title'] ?: $page['name'];
            $homePageDesc = $page['meta_description'] ?: '';
        }
    }

    if (empty($routes)) {
        $routes[] = ['path' => '/', 'slug' => 'home', 'name' => 'Home', 'title' => $projectName, 'meta_description' => ''];
    } elseif ($routes[0]['path'] !== '/') {
        $routes[0]['path'] = '/';
        $homePageTitle = $routes[0]['title'];
        $homePageDesc = $routes[0]['meta_description'];
    }

    $routesJson = json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $homePageTitleEsc = htmlspecialchars($homePageTitle, ENT_QUOTES, 'UTF-8');
    $homePageDescEsc = htmlspecialchars($homePageDesc, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{$homePageDescEsc}">
    <title>{$homePageTitleEsc}</title>

    <!-- Tailwind -->
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

    <!-- Fonts & Custom Styles -->
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <!-- Vue 3 + Vue Router -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/vue-router@4/dist/vue-router.global.prod.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        [v-cloak] { display: none; }
        .page-transition-enter-active,
        .page-transition-leave-active {
            transition: opacity 0.2s ease;
        }
        .page-transition-enter-from,
        .page-transition-leave-to {
            opacity: 0;
        }
        .wb-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }
        .wb-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .cc-form-loading { opacity: 0.6; pointer-events: none; }
        .cc-form-success { background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; }
        .cc-form-error { background: #ef4444; color: white; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; }
    </style>
</head>
<body class="antialiased">
    <div id="app" v-cloak>
        <router-view v-slot="{ Component, route }">
            <transition name="page-transition" mode="out-in">
                <component :is="Component" :key="route.path" />
            </transition>
        </router-view>
    </div>

    <script>
    const { createApp, ref, onMounted, watch, defineComponent } = Vue;
    const { createRouter, createWebHistory } = VueRouter;

    const CONFIG = {
        projectSlug: '{$projectSlugJs}',
        apiBase: '{$apiBase}',
        routes: {$routesJson}
    };

    const api = {
        async get(endpoint, params = {}) {
            const url = new URL(CONFIG.apiBase + '/public_api.php');
            url.searchParams.set('action', endpoint);
            url.searchParams.set('project', CONFIG.projectSlug);
            Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

            const response = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) throw new Error('API request failed');
            return response.json();
        },

        async getPage(slug) {
            return this.get('page', { slug });
        },

        async getTableData(table, options = {}) {
            return this.get('table', { table, ...options });
        }
    };

    const ContentParser = {
        loopContext: {},

        findBalancedClose(html, startIndex) {
            let depth = 1;
            let pos = startIndex;

            while (pos < html.length) {
                const openIdx = html.indexOf('{%', pos);
                if (openIdx === -1) return -1;
                
                const tagStr = html.substring(openIdx, openIdx + 20);
                
                if (/^\{%\s*for\s/.test(tagStr)) {
                    depth++;
                    pos = openIdx + 2;
                } else if (/^\{%\s*endfor\s*%\}/.test(html.substring(openIdx))) {
                    depth--;
                    if (depth === 0) return openIdx;
                    pos = openIdx + 2;
                } else {
                    pos = openIdx + 2;
                }
            }
            return -1;
        },

        parseVariables(html, data, routeParams = {}) {
            let result = html;

            // 1. Replace route params: {{ route.params.id }}
            result = result.replace(/\{\{\s*route\.params\.([a-zA-Z0-9_]+)\s*\}\}/g, (match, param) => {
                return routeParams[param] || '';
            });

            // 2. Pattern: {{table_name.column_name[index] | filters}} -> Indexed Access
            result = result.replace(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]((?:\s*\|\s*[^\}]+)*)\s*\}\}/g,
                (match, table, column, index, modifiers) => {
                    const tableData = data[table];
                    if (!tableData || !Array.isArray(tableData) || !tableData[index]) return '';
                    let value = tableData[index][column] || '';
                    if (modifiers) value = this.applyFilters(value, modifiers);
                    return this.escapeHtml(value);
                }
            );

            // 3. Pattern: {{table_name.column_name | filters}} -> Singleton/Context Access
            result = result.replace(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)(?!\w)(?![\[])((?:\s*\|\s*[^\}]+)*)\s*\}\}/g,
                (match, table, column, modifiers) => {
                    let contextObject = null;
                    if (data[table + '_singleton']) {
                         contextObject = data[table + '_singleton'];
                    } else if (data[table] && !Array.isArray(data[table])) {
                         contextObject = data[table];
                    }

                    if (contextObject) {
                        let value = contextObject[column];
                        if (value === undefined || value === null) value = '';
                        if (modifiers) value = this.applyFilters(value, modifiers);
                        return this.escapeHtml(value);
                    }
                    return '';
                }
            );

            // 4. Pattern: {{table_name | modifiers}} -> Aggregations (like length on a filtered table)
            result = result.replace(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]+)\s*((?:\|\s*[a-zA-Z0-9_:\-=.']+\s*)+)\}\}/g, 
                (match, table, modifiersStr) => {
                    const tableData = data[table];
                    if (!tableData || !Array.isArray(tableData)) return match;

                    const filtered = this.applyLoopModifiers([...tableData], modifiersStr);
                    const mods = modifiersStr.split('|').map(m => m.trim().toLowerCase());
                    
                    if (mods.includes('length')) {
                         return String(filtered.length);
                    }
                    return match;
                }
            );

            return result;
        },

        parseLoops(html, data) {
            const startRegex = /\{%\s*for\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+in\s+([a-zA-Z_][a-zA-Z0-9_]*)((?:\s*\|\s*[a-zA-Z0-9_=:\"'\-]+)*)\s*%\}/;
            const match = startRegex.exec(html);

            if (!match) return html;

            const startPos = match.index;
            const innerStartPos = startPos + match[0].length;
            const endPos = this.findBalancedClose(html, innerStartPos);
            
            if (endPos === -1) {
                console.warn('Unbalanced loop detected, skipping interpretation');
                return html; 
            }

            const before = html.substring(0, startPos);
            const closeTagMatch = html.substring(endPos).match(/^\{%\s*endfor\s*%\}/);
            const closeTagLen = closeTagMatch ? closeTagMatch[0].length : 0;
            const after = html.substring(endPos + closeTagLen);
            const loopBody = html.substring(innerStartPos, endPos);
            const loopVar = match[1];
            const tableName = match[2];
            const modifiersStr = match[3];

            let processed = '';
            let tableData = data[tableName];

            if (tableData && Array.isArray(tableData)) {
                tableData = this.applyLoopModifiers([...tableData], modifiersStr);

                processed = tableData.map(row => {
                    let item = loopBody;

                    const varPattern = new RegExp(
                        '\\\\{\\\\{\\\\s*' + this.escapeRegex(loopVar) + '\\\\s*\\\\.\\\\s*([a-zA-Z_][a-zA-Z0-9_]*)((?:\\\\s*\\\\|\\\\s*.*?)?)\\\\s*\\\\}\\\\}',
                        'g'
                    );

                    item = item.replace(varPattern, (m, col, mods) => {
                         let val = '';
                         const lowerCol = col.toLowerCase();
                         for (const k in row) {
                             if (k.toLowerCase() === lowerCol) { val = row[k]; break; }
                         }
                         if (val === undefined || val === null) val = '';
                         if (mods) val = this.applyFilters(String(val), mods);
                         return this.escapeHtml(String(val));
                    });

                    item = this.parseLoopConditions(item, loopVar, row);
                    return item;
                }).join('');
            }

            return this.parseLoops(before + processed + after, data);
        },

        parseLoopConditions(html, loopVar, row) {
            const ifPattern = /\{%\s*if\s+(.+?)\s*%\}/g;

            html = html.replace(ifPattern, (match, condition) => {
                const varRefPattern = new RegExp(
                    this.escapeRegex(loopVar) + '\\\\s*\\\\.\\\\s*([a-zA-Z_][a-zA-Z0-9_]*)',
                    'g'
                );

                const resolvedCondition = condition.replace(varRefPattern, (m, column) => {
                    const lowerColumn = column.toLowerCase();
                    let value = '';

                    for (const key in row) {
                        if (key.toLowerCase() === lowerColumn) {
                            value = row[key];
                            break;
                        }
                    }

                    if (value === undefined || value === null) value = '';

                    const escaped = String(value).replace(/\\\\/g, '\\\\\\\\').replace(/"/g, '\\\\\\"');
                    return '"' + escaped + '"';
                });

                return '{% if ' + resolvedCondition + ' %}';
            });

            return html;
        },

        parseConditions(html, data) {
            // Pattern: {% if condition %} content {% else %} alt {% endif %}
            const condPattern = /\{%\s*if\s+(.+?)\s*%\}([\s\S]*?)(?:\{%\s*else\s*%\}([\s\S]*?))?\{%\s*endif\s*%\}/g;
            let result = html;
            let iterations = 0;

            while (iterations < 50) {
                const newResult = result.replace(condPattern, (match, condition, trueContent, falseContent = '') => {
                    const evaluated = this.evaluateCondition(condition, data);
                    return evaluated ? trueContent : falseContent;
                });
                if (newResult === result) break;
                result = newResult;
                iterations++;
            }
            return result;
        },

        evaluateCondition(condition, data) {
            // First resolve any table.column[index] references
            let resolved = condition.replace(
                /([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]/g,
                (match, table, column, index) => {
                    const tableData = data[table];
                    if (!tableData || !tableData[index]) return '""';
                    let value = '';
                    const lowerColumn = column.toLowerCase();
                    for (const key in tableData[index]) {
                        if (key.toLowerCase() === lowerColumn) {
                            value = tableData[index][key];
                            break;
                        }
                    }
                    const escaped = String(value || '').replace(/\\\\/g, '\\\\\\\\').replace(/"/g, '\\\\\\"');
                    return '"' + escaped + '"';
                }
            );

            // Equality check: "a" == "b"
            const eqMatch = resolved.match(/^"([^"]*)"\s*==\s*"([^"]*)"$/);
            if (eqMatch) return eqMatch[1] === eqMatch[2];

            // Inequality check: "a" != "b"
            const neqMatch = resolved.match(/^"([^"]*)"\s*!=\s*"([^"]*)"$/);
            if (neqMatch) return neqMatch[1] !== neqMatch[2];

            // Truthiness check: "value"
            const truthyMatch = resolved.match(/^"([^"]*)"$/);
            if (truthyMatch) {
                const val = truthyMatch[1];
                return val !== '' && val !== 'false' && val !== '0';
            }

            return false;
        },

        applyLoopModifiers(data, modifiersStr) {
            if (!modifiersStr) return data;
            const modifiers = modifiersStr.split('|').map(m => m.trim()).filter(Boolean);

            modifiers.forEach(mod => {
                // Filter: filter:key=value
                if (mod.startsWith('filter:')) {
                    const filterPart = mod.substring(7);
                    const eqIndex = filterPart.indexOf('=');
                    if (eqIndex > 0) {
                        const key = filterPart.substring(0, eqIndex).trim().replace(/['"]/g, '');
                        const val = filterPart.substring(eqIndex + 1).trim().replace(/['"]/g, '');
                        data = data.filter(row => {
                            const lowerKey = key.toLowerCase();
                            for (const k in row) {
                                if (k.toLowerCase() === lowerKey) {
                                    return String(row[k]) === val;
                                }
                            }
                            return false;
                        });
                    }
                }
                // Sort: sort:key:order
                else if (mod.startsWith('sort:')) {
                    const parts = mod.substring(5).split(':');
                    const key = parts[0].trim().replace(/['"]/g, '');
                    const order = (parts[1] || 'asc').toLowerCase().trim();
                    const lowerKey = key.toLowerCase();

                    data.sort((a, b) => {
                        let valA = '', valB = '';
                        for (const k in a) {
                            if (k.toLowerCase() === lowerKey) { valA = a[k] || ''; break; }
                        }
                        for (const k in b) {
                            if (k.toLowerCase() === lowerKey) { valB = b[k] || ''; break; }
                        }

                        const numA = parseFloat(valA);
                        const numB = parseFloat(valB);
                        let cmp;

                        if (!isNaN(numA) && !isNaN(numB)) {
                            cmp = numA - numB;
                        } else {
                            cmp = String(valA).localeCompare(String(valB), undefined, { numeric: true });
                        }

                        return order === 'desc' ? -cmp : cmp;
                    });
                }
                // Limit: limit:n
                else if (mod.startsWith('limit:')) {
                    const limit = parseInt(mod.substring(6));
                    if (limit > 0) data = data.slice(0, limit);
                }
                // Reverse
                else if (mod === 'reverse') {
                    data.reverse();
                }
            });
            return data;
        },

        applyFilters(value, modifiersStr) {
            const modifiers = modifiersStr.split('|').map(m => m.trim()).filter(Boolean);
            let result = String(value);

            modifiers.forEach(mod => {
                const colonIndex = mod.indexOf(':');
                const name = colonIndex > 0 ? mod.substring(0, colonIndex).toLowerCase().trim() : mod.toLowerCase().trim();
                const argsStr = colonIndex > 0 ? mod.substring(colonIndex + 1) : '';

                switch (name) {
                    case 'upper':
                    case 'uppercase':
                        result = result.toUpperCase();
                        break;
                    case 'lower':
                    case 'lowercase':
                        result = result.toLowerCase();
                        break;
                    case 'capitalize':
                    case 'capfirst':
                        if (result.length > 0) {
                            result = result.charAt(0).toUpperCase() + result.slice(1);
                        }
                        break;
                    case 'truncate':
                        const truncArgs = argsStr.split(':');
                        const len = parseInt(truncArgs[0]) || 50;
                        const suffix = truncArgs[1] ? truncArgs[1].replace(/['"]/g, '') : '...';
                        if (result.length > len) {
                            result = result.substring(0, len) + suffix;
                        }
                        break;
                    case 'default':
                        if (!result || result === '0' || result === 'false' || result === 'null' || result === 'undefined') {
                            result = argsStr.replace(/['"]/g, '');
                        }
                        break;
                    case 'trim':
                        result = result.trim();
                        break;
                    case 'slice':
                        const sliceArgs = argsStr.split(':');
                        const start = parseInt(sliceArgs[0]) || 0;
                        const end = sliceArgs[1] ? parseInt(sliceArgs[1]) : undefined;
                        result = result.slice(start, end);
                        break;
                    case 'striptags':
                        result = result.replace(/<[^>]*>/g, '');
                        break;
                    case 'length':
                        result = String(result.length);
                        break;
                    case 'date':
                        result = this.formatDate(result, argsStr.replace(/['"]/g, ''));
                        break;
                    case 'timeago':
                        result = this.timeAgo(result);
                        break;
                    case 'replace':
                        // Extract quoted arguments: "old":"new"
                        const args = argsStr.match(/(["'])(?:(?=(\\?))\2.)*?\1/g);
                        if (args && args.length >= 2) {
                            const search = args[0].slice(1, -1);
                            const replacement = args[1].slice(1, -1);
                            result = result.split(search).join(replacement);
                        }
                        break;
                }
            });
            return result;
        },

        formatDate(dateStr, format) {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            
            const pad = (n) => n.toString().padStart(2, '0');
            const months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
            
            return format.replace(/[a-zA-Z]/g, (char) => {
                switch(char) {
                    case 'd': return pad(date.getDate()); // 01-31
                    case 'j': return date.getDate(); // 1-31
                    case 'm': return pad(date.getMonth() + 1); // 01-12
                    case 'M': return months[date.getMonth()].substring(0, 3); // Jan
                    case 'F': return months[date.getMonth()]; // Januar
                    case 'Y': return date.getFullYear(); // 2026
                    case 'y': return String(date.getFullYear()).slice(-2); // 26
                    case 'H': return pad(date.getHours()); // 00-23
                    case 'h': return pad(date.getHours() % 12 || 12); // 01-12
                    case 'i': return pad(date.getMinutes()); // 00-59
                    case 's': return pad(date.getSeconds()); // 00-59
                    default: return char;
                }
            });
        },

        timeAgo(dateStr) {
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            const seconds = Math.floor((new Date() - date) / 1000);
            
            if (seconds < 60) return 'gerade eben';
            
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return 'vor ' + minutes + ' Minute' + (minutes !== 1 ? 'n' : '');
            
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return 'vor ' + hours + ' Stunde' + (hours !== 1 ? 'n' : '');
            
            const days = Math.floor(hours / 24);
            if (days < 30) return 'vor ' + days + ' Tag' + (days !== 1 ? 'n' : '');
            
            const months = Math.floor(days / 30);
            if (months < 12) return 'vor ' + months + ' Monat' + (months !== 1 ? 'en' : '');
            
            const years = Math.floor(days / 365);
            return 'vor ' + years + ' Jahr' + (years !== 1 ? 'en' : '');
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        escapeRegex(str) {
            return str.replace(/[.*+?^\${}()|[\]\\x5c]/g, '\\\\\\\\$&');
        },

        async parse(html, routeParams = {}) {
            const tables = new Set();
            let match;

            // Find tables in for loops: {% for x in TABLE_NAME %}
            const loopTablePattern = /\{%\s*for\s+[a-zA-Z_][a-zA-Z0-9_]*\s+in\s+([a-zA-Z_][a-zA-Z0-9_]*)/g;
            while ((match = loopTablePattern.exec(html)) !== null) {
                tables.add(match[1]);
            }

            // Find direct table references (indexed): {{TABLE_NAME.column[index]}}
            const directTablePattern = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*[a-zA-Z_][a-zA-Z0-9_]*\s*\[\s*\d+\s*\]/g;
            while ((match = directTablePattern.exec(html)) !== null) {
                tables.add(match[1]);
            }

            // Find direct singleton references: {{TABLE_NAME.column}}
            // If we have routeParams, these might be detail lookups
            const singletonTablePattern = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*[a-zA-Z_][a-zA-Z0-9_]*(?!\w)(?![\[])/g;
            while ((match = singletonTablePattern.exec(html)) !== null) {
                tables.add(match[1]);
            }

            // Find direct table references with modifiers (aggregations): {{TABLE_NAME | modifiers}}
            const aggrTablePattern = /\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\|/g;
            while ((match = aggrTablePattern.exec(html)) !== null) {
                tables.add(match[1]);
            }

            const data = {};
            await Promise.all([...tables].map(async table => {
                try {
                    const result = await api.getTableData(table);
                    if (result.success) {
                        data[table] = result.data;
                        
                        // Automatic Detail Resolution
                        // If we have route params (id or slug), try to create a singleton for this table
                        // e.g. products/:id -> data['products_singleton'] = filtered row
                        if (Object.keys(routeParams).length > 0 && Array.isArray(result.data)) {
                             let found = null;
                             
                             // 1. Try ID
                             if (routeParams.id) {
                                 found = result.data.find(r => String(r.id) === String(routeParams.id));
                             }
                             
                             // 2. Try Slug (if no ID match or no ID param)
                             if (!found && routeParams.slug) {
                                 found = result.data.find(r => r.slug === routeParams.slug);
                             }

                             // 3. Try generic param matching table name (e.g. products/:product_id)
                             // This is looser but might be helpful
                             if (!found) {
                                 const paramName = table.toLowerCase() + '_id';

                                 if (routeParams[paramName]) {
                                      found = result.data.find(r => String(r.id) === String(routeParams[paramName]));
                                 }
                             }
                             
                             if (found) {
                                 data[table + '_singleton'] = found;
                             }
                        }
                    } else {
                        console.warn('Failed to load table:', table, result.message);
                        data[table] = [];
                    }
                } catch (e) {
                    console.warn('Error loading table:', table, e);
                    data[table] = [];
                }
            }));

            let result = html;

            // 0. Pre-process route params and context variables globally
            // This allows them to be used inside loop definitions (e.g. filter:slug={{slug}})
            if (routeParams) {
                // Replace {{ route.params.key }}
                Object.keys(routeParams).forEach(key => {
                    const val = routeParams[key];
                    const safeVal = String(val).replace(/['"]/g, ''); // Simple sanitization for usage in attributes/filters
                    
                    // Specific: {{ route.params.key }}
                    result = result.replace(new RegExp('\\{\\{\\s*route\\.params\\.' + key + '\\s*\\}\\}', 'g'), safeVal);
                    
                    // Shorthand: {{ key }}
                    result = result.replace(new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'g'), safeVal);
                });
            }

            // 1. First process loops (this replaces loop variables with actual values)
            result = this.parseLoops(result, data);

            // 2. Then process conditions (after loop variables are resolved)
            result = this.parseConditions(result, data);

            // 3. Finally process any remaining direct variable references (including singletons)
            result = this.parseVariables(result, data, routeParams);

            // Clean up raw tags and dynamic badges
            result = result.replace(/\{%\s*raw\s*%\}|\{%\s*endraw\s*%\}/g, '');
            result = result.replace(/<span[^>]*data-cc-dynamic="true"[^>]*>[^<]*<\/span>/gi, '');

            return result;
        }
    };

    const PageComponent = defineComponent({
        name: 'PageView',
        props: ['slug'],
        setup(props) {
            const loading = ref(true);
            const error = ref(null);
            const content = ref('');
            const route = VueRouter.useRoute();

            const loadPage = async (slug) => {
                loading.value = true;
                error.value = null;

                try {
                    const result = await api.getPage(slug);

                    if (result.success) {
                        const rawHtml = result.data.components.map(c => c.html).join('\\n');
                        content.value = await ContentParser.parse(rawHtml, route.params);
                        document.title = result.data.title;

                        const metaDesc = document.querySelector('meta[name="description"]');
                        if (metaDesc) metaDesc.content = result.data.meta_description || '';
                    } else {
                        error.value = result.message || 'Page not found';
                    }
                } catch (e) {
                    error.value = 'Failed to load page';
                    console.error(e);
                }
                loading.value = false;
            };

            onMounted(() => loadPage(props.slug));
            watch(() => props.slug, loadPage);
            watch(() => route.params, () => loadPage(props.slug));

            return { loading, error, content };
        },
        template: `
            <div v-if="loading" class="wb-loading"><div class="wb-spinner"></div></div>
            <div v-else-if="error" class="min-h-screen flex items-center justify-center">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Seite nicht gefunden</h1>
                    <p class="text-gray-600">{{ error }}</p>
                    <a href="/" class="mt-4 inline-block text-blue-600 hover:underline">Zur Startseite</a>
                </div>
            </div>
            <div v-else v-html="content"></div>
        `
    });

    const routes = CONFIG.routes.map(route => ({
        path: route.path,
        component: PageComponent,
        props: { slug: route.slug },
        meta: { title: route.title, description: route.meta_description }
    }));

    routes.push({
        path: '/:pathMatch(.*)*',
        component: {
            template: `
                <div class="min-h-screen flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-gray-800 mb-2">404</h1>
                        <p class="text-gray-600 mb-4">Seite nicht gefunden</p>
                        <a href="/" class="text-blue-600 hover:underline">Zur Startseite</a>
                    </div>
                </div>
            `
        }
    });

    const router = createRouter({
        history: createWebHistory(),
        routes,
        scrollBehavior(to, from, savedPosition) {
            if (savedPosition) return savedPosition;
            if (to.hash) return { el: to.hash, behavior: 'smooth' };
            return { top: 0, behavior: 'smooth' };
        }
    });

    router.afterEach((to) => {
        if (to.meta.title) document.title = to.meta.title;
    });

    createApp({}).use(router).mount('#app');

    (function() {
        const CC_FORMS_API = 'https://alex.polan.sk/control-center/api/public_form_submit.php';
        const CC_PROJECT = '{$projectSlugJs}';

        function initCCForms() {
            document.querySelectorAll('form[data-cc-form]').forEach(setupCCForm);
            new MutationObserver(mutations => {
                mutations.forEach(m => m.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.matches?.('form[data-cc-form]')) setupCCForm(node);
                        node.querySelectorAll?.('form[data-cc-form]').forEach(setupCCForm);
                    }
                }));
            }).observe(document.body, { childList: true, subtree: true });
        }

        function setupCCForm(form) {
            if (form.dataset.ccFormInitialized) return;
            form.dataset.ccFormInitialized = 'true';

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formName = form.dataset.ccForm;
                const project = form.dataset.ccProject || CC_PROJECT;
                const successMsg = form.dataset.ccSuccess || 'Erfolgreich gesendet!';
                const errorMsg = form.dataset.ccError || 'Fehler beim Senden.';
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                const originalText = submitBtn?.textContent || submitBtn?.value || '';

                form.classList.add('cc-form-loading');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.tagName === 'BUTTON' ? submitBtn.textContent = 'Wird gesendet...' : submitBtn.value = 'Wird gesendet...';
                }
                form.querySelector('.cc-form-message')?.remove();

                const data = {};
                new FormData(form).forEach((v, k) => {
                    if (data[k]) Array.isArray(data[k]) ? data[k].push(v) : data[k] = [data[k], v];
                    else data[k] = v;
                });

                try {
                    const res = await fetch(CC_FORMS_API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ project, form_name: formName, data, source: 'web-builder-vue' })
                    });
                    const result = await res.json();
                    const msg = document.createElement('div');
                    msg.className = 'cc-form-message ' + (result.success ? 'cc-form-success' : 'cc-form-error');
                    msg.textContent = result.success ? successMsg : (result.error || errorMsg);
                    form.appendChild(msg);
                    if (result.success) form.reset();
                    setTimeout(() => msg.remove(), 5000);
                } catch (err) {
                    const msg = document.createElement('div');
                    msg.className = 'cc-form-message cc-form-error';
                    msg.textContent = errorMsg;
                    form.appendChild(msg);
                    setTimeout(() => msg.remove(), 5000);
                } finally {
                    form.classList.remove('cc-form-loading');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.tagName === 'BUTTON' ? submitBtn.textContent = originalText : submitBtn.value = originalText;
                    }
                }
            });
        }

        document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', initCCForms) : initCCForms();
    })();
    </script>
</body>
</html>
HTML;
}

function getStylesCSS()
{
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

    return <<<CSS

html {
    scroll-behavior: smooth;
}

*:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
CSS;
}
