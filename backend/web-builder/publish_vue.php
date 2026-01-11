<?php
/**
 * Vue-based Web Builder Publisher
 *
 * Generates a Vue 3 SPA with:
 * - Vue Router for client-side routing
 * - Dynamic data loading from CC API (remote)
 * - No rebuild needed for content updates
 * - Pure HTML/JS output - no PHP on target server
 */

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

// Get all pages for routing
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

// Generate files
$generatedFiles = [];

// 1. Generate index.html (pure Vue SPA - no PHP on target server!)
$indexHtml = generateIndexHtml($project, $pages, $projectSlug);
$generatedFiles[] = [
    'filename' => 'index.html',
    'content' => $indexHtml
];

// 2. Generate styles.css
$cssContent = getStylesCSS();
$generatedFiles[] = [
    'filename' => 'styles.css',
    'content' => $cssContent
];

// Deploy if requested
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

/**
 * Generate the main index.html with Vue 3 SPA
 * Pure HTML/JS - API calls go to Control Center server
 */
function generateIndexHtml($project, $pages, $projectSlug)
{
    $projectName = htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8');
    $projectSlugJs = htmlspecialchars($projectSlug, ENT_QUOTES, 'UTF-8');
    $apiBase = CC_API_BASE;

    // Build routes array for Vue Router
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

    // If no home page set, use first page
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

    <!-- Tailwind CSS via CDN -->
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

    <!-- Custom Styles -->
    <link rel="stylesheet" href="styles.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <!-- Vue 3 + Vue Router via CDN -->
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

    // Configuration - API runs on Control Center server
    const CONFIG = {
        projectSlug: '{$projectSlugJs}',
        apiBase: '{$apiBase}',
        routes: {$routesJson}
    };

    // API Helper - calls CC server remotely
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

    // Dynamic Content Parser
    const ContentParser = {
        parseVariables(html, data) {
            return html.replace(/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*(\d+)\s*\]((?:\s*\|\s*[^\}]+)*)\}\}/g,
                (match, table, column, index, modifiers) => {
                    const tableData = data[table];
                    if (!tableData || !tableData[index]) return '';
                    let value = tableData[index][column] || '';
                    if (modifiers) value = this.applyFilters(value, modifiers);
                    return this.escapeHtml(value);
                }
            );
        },

        parseLoops(html, data) {
            const loopPattern = /\{%\s*for\s+([a-zA-Z0-9_]+)\s+in\s+([a-zA-Z0-9_]+)((?:\s*\|\s*[a-zA-Z0-9_=:\"'-]+)*)\s*%\}([\s\S]*?)\{%\s*endfor\s*%\}/g;

            return html.replace(loopPattern, (match, loopVar, tableName, modifiersStr, template) => {
                let tableData = data[tableName];
                if (!tableData || !Array.isArray(tableData)) return '';

                tableData = this.applyLoopModifiers([...tableData], modifiersStr);

                return tableData.map(row => {
                    let rowHtml = template;
                    const varPattern = new RegExp('\\{\\{\\s*' + loopVar + '\\s*\\.\\s*([a-zA-Z0-9_]+)((?:\\s*\\|\\s*[^\\}]+)*)\\s*\\}\\}', 'g');
                    rowHtml = rowHtml.replace(varPattern, (m, column, mods) => {
                        let value = row[column] || '';
                        if (mods) value = this.applyFilters(value, mods);
                        return this.escapeHtml(value);
                    });
                    return rowHtml;
                }).join('');
            });
        },

        parseConditions(html, data) {
            const condPattern = /\{%\s*if\s+(.+?)\s*%\}([\s\S]*?)(?:\{%\s*else\s*%\}([\s\S]*?))?\{%\s*endif\s*%\}/g;
            let result = html;
            let iterations = 0;

            while (iterations < 50) {
                const newResult = result.replace(condPattern, (match, condition, trueContent, falseContent = '') => {
                    return this.evaluateCondition(condition, data) ? trueContent : falseContent;
                });
                if (newResult === result) break;
                result = newResult;
                iterations++;
            }
            return result;
        },

        evaluateCondition(condition, data) {
            const resolved = condition.replace(/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\[(\d+)\]/g,
                (match, table, column, index) => {
                    const tableData = data[table];
                    if (!tableData || !tableData[index]) return '""';
                    return JSON.stringify(tableData[index][column] || '');
                }
            );

            const eqMatch = resolved.match(/^"([^"]*)"\s*==\s*"([^"]*)"$/);
            if (eqMatch) return eqMatch[1] === eqMatch[2];

            const neqMatch = resolved.match(/^"([^"]*)"\s*!=\s*"([^"]*)"$/);
            if (neqMatch) return neqMatch[1] !== neqMatch[2];

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
                if (mod.startsWith('filter:')) {
                    const [key, val] = mod.substring(7).split('=').map(s => s.trim().replace(/['"]/g, ''));
                    data = data.filter(row => String(row[key]) === val);
                } else if (mod.startsWith('sort:')) {
                    const parts = mod.substring(5).split(':');
                    const key = parts[0].trim().replace(/['"]/g, '');
                    const order = (parts[1] || 'asc').toLowerCase();
                    data.sort((a, b) => {
                        const cmp = String(a[key] || '').localeCompare(String(b[key] || ''), undefined, { numeric: true });
                        return order === 'desc' ? -cmp : cmp;
                    });
                } else if (mod.startsWith('limit:')) {
                    const limit = parseInt(mod.substring(6));
                    if (limit > 0) data = data.slice(0, limit);
                } else if (mod === 'reverse') {
                    data.reverse();
                }
            });
            return data;
        },

        applyFilters(value, modifiersStr) {
            const modifiers = modifiersStr.split('|').map(m => m.trim()).filter(Boolean);
            let result = String(value);

            modifiers.forEach(mod => {
                const [name, ...args] = mod.split(':');
                switch (name.toLowerCase()) {
                    case 'upper': case 'uppercase': result = result.toUpperCase(); break;
                    case 'lower': case 'lowercase': result = result.toLowerCase(); break;
                    case 'capitalize': result = result.charAt(0).toUpperCase() + result.slice(1); break;
                    case 'truncate':
                        const len = parseInt(args[0]) || 50;
                        if (result.length > len) result = result.substring(0, len) + (args[1] || '...');
                        break;
                    case 'default':
                        if (!result || result === '0' || result === 'false') result = args.join(':').replace(/['"]/g, '');
                        break;
                    case 'trim': result = result.trim(); break;
                }
            });
            return result;
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        async parse(html) {
            // Extract table references
            const tables = new Set();
            let match;
            const tablePattern = /\{\{([a-zA-Z_][a-zA-Z0-9_]*)\./g;
            const loopPattern = /\{%\s*for\s+\w+\s+in\s+([a-zA-Z0-9_]+)/g;

            while ((match = tablePattern.exec(html)) !== null) tables.add(match[1]);
            while ((match = loopPattern.exec(html)) !== null) tables.add(match[1]);

            // Fetch table data from CC API
            const data = {};
            await Promise.all([...tables].map(async table => {
                try {
                    const result = await api.getTableData(table);
                    if (result.success) data[table] = result.data;
                } catch (e) {
                    console.warn('Failed to load table:', table, e);
                    data[table] = [];
                }
            }));

            // Process template
            let result = html;
            result = this.parseLoops(result, data);
            result = this.parseConditions(result, data);
            result = this.parseVariables(result, data);
            result = result.replace(/\{%\s*raw\s*%\}|\{%\s*endraw\s*%\}/g, '');
            result = result.replace(/<span[^>]*data-cc-dynamic="true"[^>]*>[^<]*<\/span>/gi, '');

            return result;
        }
    };

    // Page Component
    const PageComponent = defineComponent({
        name: 'PageView',
        props: ['slug'],
        setup(props) {
            const loading = ref(true);
            const error = ref(null);
            const content = ref('');

            const loadPage = async (slug) => {
                loading.value = true;
                error.value = null;

                try {
                    const result = await api.getPage(slug);
                    if (result.success) {
                        const rawHtml = result.data.components.map(c => c.html).join('\\n');
                        content.value = await ContentParser.parse(rawHtml);
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

    // Create routes
    const routes = CONFIG.routes.map(route => ({
        path: route.path,
        component: PageComponent,
        props: { slug: route.slug },
        meta: { title: route.title, description: route.meta_description }
    }));

    // 404 route
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

    // Create router
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

    // CC Forms Integration
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

/**
 * Get styles CSS
 */
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
/* Custom styles for Web Builder Vue */

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
