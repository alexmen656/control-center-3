<?php

class PagesController
{
    /**
     * GET /v2/pages/check?url=...
     */
    public function check(Request $request, Response $response): void
    {
        $url = $request->input('url', '');

        if (empty($url)) {
            $response->error('url parameter is required', 400);
            return;
        }

        $url = trim($url, '/');

        // 1. Check control_center_pages
        $escapedUrl = escape_string($url);
        $result = query("SELECT * FROM control_center_pages WHERE url='$escapedUrl'");
        if ($result && mysqli_num_rows($result) > 0) {
            $p = fetch_assoc($result);
            $pageID = $p['pageID'];
            $replaces = [];
            $data = query("SELECT * FROM control_center_page_data WHERE pageID='$pageID'");
            foreach ($data as $d) {
                $replaces[$d['key']] = $d['value'];
            }
            $response->json([
                'exists' => true,
                'page' => [
                    'id' => $p['id'],
                    'url' => $p['url'],
                    'showTitle' => $p['showTitle'],
                    'icon' => $p['icon'],
                    'title' => $p['title'],
                    'html' => $this->useTemplate($p['html'], $replaces),
                    'pageID' => $p['pageID'],
                ]
            ]);
            return;
        }

        // 2. Check form pages: project/{link}/forms/{form_name} or .../edit
        if (preg_match('#^project/([^/]+)/forms/([^/]+?)(/edit)?$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $formName = escape_string($m[2]);
            $isEdit = !empty($m[3]);

            $form = query("SELECT fs.* FROM form_settings fs JOIN projects p ON fs.project = p.link WHERE p.link='$projectLink' AND fs.form_name='$formName'");
            if ($form && mysqli_num_rows($form) > 0) {
                $f = fetch_assoc($form);
                $suffix = $isEdit ? '_edit' : '';
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'form_' . $f['form_id'] . $suffix,
                        'url' => $url,
                        'showTitle' => true,
                        'icon' => 'list-outline',
                        'title' => $f['form_name'],
                        'html' => '',
                        'pageID' => 'form_' . $f['form_id'] . $suffix,
                    ]
                ]);
                return;
            }
        }

        // 3. Check web builder pages: project/{slug}/page/{pageSlug}
        if (preg_match('#^project/([^/]+)/page/([^/]+)$#', $url, $m)) {
            $projectSlug = escape_string($m[1]);
            $pageSlug = escape_string($m[2]);

            $projects = query("SELECT id, name FROM control_center_web_builder_projects");
            foreach ($projects as $proj) {
                $slug = strtolower(str_replace(" ", "-", $proj['name']));
                if ($slug === $projectSlug) {
                    $page = query("SELECT * FROM control_center_web_builder_pages WHERE project_id='{$proj['id']}' AND slug='$pageSlug'");
                    if ($page && mysqli_num_rows($page) > 0) {
                        $pg = fetch_assoc($page);
                        $response->json([
                            'exists' => true,
                            'page' => [
                                'id' => 'web_' . $proj['id'] . '_' . $pg['id'],
                                'url' => $url,
                                'showTitle' => true,
                                'icon' => $pg['is_home'] ? 'home-outline' : 'document-outline',
                                'title' => $pg['title'] ?: $pg['name'],
                                'html' => '',
                                'pageID' => 'web_' . $proj['id'] . '_' . $pg['id'],
                            ]
                        ]);
                        return;
                    }
                }
            }
        }

        // 4. Check web builder component routes: project/{slug}/page/{pageSlug}/{componentName}
        if (preg_match('#^project/([^/]+)/page/([^/]+)/([^/]+)$#', $url, $m)) {
            $projectSlug = $m[1];
            $pageSlug = escape_string($m[2]);
            $componentSlug = $m[3];

            $projects = query("SELECT id, name FROM control_center_web_builder_projects");
            foreach ($projects as $proj) {
                $slug = strtolower(str_replace(" ", "-", $proj['name']));
                if ($slug === $projectSlug) {
                    $page = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='{$proj['id']}' AND slug='$pageSlug'");
                    if ($page && mysqli_num_rows($page) > 0) {
                        $pg = fetch_assoc($page);
                        $components = query("SELECT c.*, t.title as template_title FROM control_center_web_builder_components c LEFT JOIN control_center_web_builder_templates t ON c.original_template_id = t.id WHERE c.page_id='{$pg['id']}' ORDER BY c.position ASC");
                        foreach ($components as $comp) {
                            $compName = !empty($comp['template_title']) ? $comp['template_title'] : 'Component ' . ($comp['position'] + 1);
                            $compSlug = str_replace(["ö", "ü", "ä", " "], ["oe", "ue", "ae", "-"], strtolower($compName));
                            if ($compSlug === $componentSlug) {
                                $response->json([
                                    'exists' => true,
                                    'page' => [
                                        'id' => 'component_' . $comp['id'],
                                        'url' => $url,
                                        'showTitle' => true,
                                        'icon' => 'cube-outline',
                                        'title' => $compName,
                                        'html' => '',
                                        'pageID' => 'component_' . $comp['id'],
                                    ]
                                ]);
                                return;
                            }
                        }
                    }
                }
            }
        }

        // 5. Check modul web builder: project/{link}/wb/{projectId}/overview or project/{link}/wb/{projectId}/{pageSlug}
        if (preg_match('#^project/([^/]+)/wb/(\d+)/overview$#', $url, $m)) {
            $wbProjectId = escape_string($m[2]);
            $wbProject = query("SELECT id, name FROM control_center_modul_web_builder_projects WHERE id='$wbProjectId'");
            if ($wbProject && mysqli_num_rows($wbProject) > 0) {
                $wp = fetch_assoc($wbProject);
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'wb_overview_' . $wp['id'],
                        'url' => $url,
                        'showTitle' => true,
                        'icon' => 'apps-outline',
                        'title' => $wp['name'] . ' - Overview',
                        'html' => '',
                        'pageID' => 'wb_overview_' . $wp['id'],
                    ]
                ]);
                return;
            }
        }

        if (preg_match('#^project/([^/]+)/wb/(\d+)/([^/]+)$#', $url, $m)) {
            $wbProjectId = escape_string($m[2]);
            $wbPageSlug = escape_string($m[3]);
            if ($wbPageSlug !== 'overview') {
                $wbPage = query("SELECT p.*, wp.name as project_name FROM control_center_modul_web_builder_pages p JOIN control_center_modul_web_builder_projects wp ON p.project_id = wp.id WHERE p.project_id='$wbProjectId' AND p.slug='$wbPageSlug'");
                if ($wbPage && mysqli_num_rows($wbPage) > 0) {
                    $pg = fetch_assoc($wbPage);
                    $response->json([
                        'exists' => true,
                        'page' => [
                            'id' => 'wb_page_' . $pg['id'],
                            'url' => $url,
                            'showTitle' => true,
                            'icon' => $pg['is_home'] ? 'home-outline' : 'document-text-outline',
                            'title' => $pg['title'] ?: $pg['name'],
                            'html' => '',
                            'pageID' => 'wb_page_' . $pg['id'],
                        ]
                    ]);
                    return;
                }
            }
        }

        // 6. Check project management routes
        if (preg_match('#^project/([^/]+)/(manage|new)/(codespaces|codespace|forms|form|apis)$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $action = $m[2];
            $type = $m[3];
            $project = query("SELECT projectID, name FROM projects WHERE link='$projectLink'");
            if ($project && mysqli_num_rows($project) > 0) {
                $p = fetch_assoc($project);
                $icons = ['codespaces' => 'code-outline', 'codespace' => 'add-circle-outline', 'forms' => 'document-outline', 'form' => 'document-outline', 'apis' => 'albums-outline'];
                $titles = ['codespaces' => 'Manage Codespaces', 'codespace' => 'New Codespace', 'forms' => 'Manage Forms', 'form' => 'New Form', 'apis' => 'Manage APIs'];
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => $action . '_' . $type . '_' . $p['projectID'],
                        'url' => $url,
                        'showTitle' => false,
                        'icon' => $icons[$type] ?? 'document-outline',
                        'title' => ($titles[$type] ?? ucfirst($type)) . ' - ' . $p['name'],
                        'html' => '',
                        'pageID' => $action . '_' . $type . '_' . $p['projectID'],
                    ]
                ]);
                return;
            }
        }

        // 7. Check API dashboard routes: project/{link}/apis/{slug}
        if (preg_match('#^project/([^/]+)/apis/([^/]+)$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $apiSlug = escape_string($m[2]);
            $api = query("
                SELECT ca.id, ca.name, ca.icon, pas.id as subscription_id
                FROM project_api_subscriptions pas
                JOIN cms_apis ca ON pas.api_id = ca.id
                JOIN projects p ON pas.projectID = p.projectID
                WHERE p.link='$projectLink' AND ca.slug='$apiSlug' AND pas.is_enabled=1
            ");
            if ($api && mysqli_num_rows($api) > 0) {
                $a = fetch_assoc($api);
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'api_dashboard_' . $a['subscription_id'],
                        'url' => $url,
                        'showTitle' => false,
                        'icon' => $a['icon'] ?: 'cloud-outline',
                        'title' => $a['name'] . ' - Dashboard',
                        'html' => '',
                        'pageID' => 'api_dashboard_' . $a['subscription_id'],
                    ]
                ]);
                return;
            }
        }

        // 8. Check codespace routes: project/{link}/codespace/{slug}
        if (preg_match('#^project/([^/]+)/codespace/([^/]+)$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $codespaceSlug = escape_string($m[2]);
            $cs = query("
                SELECT pc.id, pc.name, pc.slug
                FROM project_codespaces pc
                JOIN projects p ON pc.project_id = p.projectID
                WHERE p.link='$projectLink' AND pc.slug='$codespaceSlug'
            ");
            if ($cs && mysqli_num_rows($cs) > 0) {
                $c = fetch_assoc($cs);
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'codespace_monaco_' . $c['id'],
                        'url' => $url,
                        'showTitle' => false,
                        'icon' => 'code-working-outline',
                        'title' => $c['name'] . ' - Monaco Editor',
                        'html' => '',
                        'pageID' => 'codespace_monaco_' . $c['id'],
                    ]
                ]);
                return;
            }
        }

        // 9. Check database table routes: databases/table/{name}
        if (preg_match('#^databases/table/([^/]+)$#', $url, $m)) {
            $tableName = escape_string($m[1]);
            $tables = query("SHOW TABLES LIKE '$tableName'");
            if ($tables && mysqli_num_rows($tables) > 0) {
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'table_' . $tableName,
                        'url' => $url,
                        'showTitle' => true,
                        'icon' => 'grid-outline',
                        'title' => $tableName,
                        'html' => '',
                        'pageID' => 'table_' . $tableName,
                    ]
                ]);
                return;
            }
        }

        // 10. Check appstore metadata routes
        if (preg_match('#^project/([^/]+)/appstore-metadata(?:/(.*))?$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $subPath = $m[2] ?? '';

            $project = query("SELECT projectID, id, name FROM projects WHERE link='$projectLink'");
            if ($project && mysqli_num_rows($project) > 0) {
                $p = fetch_assoc($project);
                $projectID = $p['projectID'];
                $ID = $p['id'];

                // Check if appstore-metadata tool is enabled
                $toolCheck = query("SELECT id FROM project_tools WHERE projectID='$projectID' AND link='appstore-metadata'");
                if (mysqli_num_rows($toolCheck) > 0) {
                    // Dashboard
                    if ($subPath === '' || $subPath === null) {
                        $response->json([
                            'exists' => true,
                            'page' => [
                                'id' => 'appstore_metadata_' . $projectID,
                                'url' => $url,
                                'showTitle' => false,
                                'icon' => 'logo-apple-appstore',
                                'title' => 'App Store Metadata - ' . $p['name'],
                                'html' => '',
                                'pageID' => 'appstore_metadata_' . $projectID,
                            ]
                        ]);
                        return;
                    }

                    // Config
                    if ($subPath === 'config') {
                        $response->json([
                            'exists' => true,
                            'page' => [
                                'id' => 'appstore_metadata_config_' . $projectID,
                                'url' => $url,
                                'showTitle' => false,
                                'icon' => 'key-outline',
                                'title' => 'App Store API Settings - ' . $p['name'],
                                'html' => '',
                                'pageID' => 'appstore_metadata_config_' . $projectID,
                            ]
                        ]);
                        return;
                    }

                    // App detail: app/{appId}
                    if (preg_match('#^app/(\d+)$#', $subPath, $am)) {
                        $appId = escape_string($am[1]);
                        $app = query("SELECT id, name FROM appstore_apps WHERE id='$appId' AND project_id='$ID'");
                        if ($app && mysqli_num_rows($app) > 0) {
                            $a = fetch_assoc($app);
                            $response->json([
                                'exists' => true,
                                'page' => [
                                    'id' => 'appstore_app_' . $a['id'],
                                    'url' => $url,
                                    'showTitle' => false,
                                    'icon' => 'apps-outline',
                                    'title' => $a['name'] . ' - Details',
                                    'html' => '',
                                    'pageID' => 'appstore_app_' . $a['id'],
                                ]
                            ]);
                            return;
                        }
                    }

                    // App localization: app/{appId}/localization
                    if (preg_match('#^app/(\d+)/localization$#', $subPath, $am)) {
                        $appId = escape_string($am[1]);
                        $app = query("SELECT id, name FROM appstore_apps WHERE id='$appId' AND project_id='$ID'");
                        if ($app && mysqli_num_rows($app) > 0) {
                            $a = fetch_assoc($app);
                            $response->json([
                                'exists' => true,
                                'page' => [
                                    'id' => 'appstore_app_localization_' . $a['id'],
                                    'url' => $url,
                                    'showTitle' => false,
                                    'icon' => 'language-outline',
                                    'title' => $a['name'] . ' - Localization',
                                    'html' => '',
                                    'pageID' => 'appstore_app_localization_' . $a['id'],
                                ]
                            ]);
                            return;
                        }
                    }

                    // Version: app/{appId}/version/{versionId}
                    if (preg_match('#^app/(\d+)/version/(\d+)$#', $subPath, $am)) {
                        $appId = escape_string($am[1]);
                        $versionId = escape_string($am[2]);
                        $app = query("SELECT id, name FROM appstore_apps WHERE id='$appId' AND project_id='$ID'");
                        if ($app && mysqli_num_rows($app) > 0) {
                            $a = fetch_assoc($app);
                            $version = query("SELECT id, version_string FROM appstore_app_versions WHERE id='$versionId' AND app_id='$appId'");
                            if ($version && mysqli_num_rows($version) > 0) {
                                $v = fetch_assoc($version);
                                $response->json([
                                    'exists' => true,
                                    'page' => [
                                        'id' => 'appstore_version_' . $v['id'],
                                        'url' => $url,
                                        'showTitle' => false,
                                        'icon' => 'git-branch-outline',
                                        'title' => $a['name'] . ' - Version ' . $v['version_string'],
                                        'html' => '',
                                        'pageID' => 'appstore_version_' . $v['id'],
                                    ]
                                ]);
                                return;
                            }
                        }
                    }

                    // Screenshots: app/{appId}/screenshots/{versionId}
                    if (preg_match('#^app/(\d+)/screenshots/(\d+)$#', $subPath, $am)) {
                        $appId = escape_string($am[1]);
                        $versionId = escape_string($am[2]);
                        $app = query("SELECT id, name FROM appstore_apps WHERE id='$appId' AND project_id='$ID'");
                        if ($app && mysqli_num_rows($app) > 0) {
                            $a = fetch_assoc($app);
                            $version = query("SELECT id, version_string FROM appstore_app_versions WHERE id='$versionId' AND app_id='$appId'");
                            if ($version && mysqli_num_rows($version) > 0) {
                                $v = fetch_assoc($version);
                                $response->json([
                                    'exists' => true,
                                    'page' => [
                                        'id' => 'appstore_screenshots_' . $v['id'],
                                        'url' => $url,
                                        'showTitle' => false,
                                        'icon' => 'images-outline',
                                        'title' => $a['name'] . ' - Screenshots (v' . $v['version_string'] . ')',
                                        'html' => '',
                                        'pageID' => 'appstore_screenshots_' . $v['id'],
                                    ]
                                ]);
                                return;
                            }
                        }
                    }
                }
            }
        }

        $response->json(['exists' => false, 'page' => null]);
    }

    private function useTemplate(string $template, array $data = []): string
    {
        $keys = [];
        $values = [];
        foreach ($data as $key => $val) {
            $keys[] = '{{' . $key . '}}';
            $values[] = $val;
        }
        return str_replace($keys, $values, $template);
    }
}
