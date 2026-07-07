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
            $response->json([
                'exists' => true,
                'page' => [
                    'id' => $p['id'],
                    'url' => $p['url'],
                    'showTitle' => $p['showTitle'],
                    'icon' => $p['icon'],
                    'title' => $p['title'],
                    'pageID' => $p['pageID'],
                ]
            ]);
            return;
        }

        // 2. Check form pages: project/{link}/tables/{table_name} or .../edit
        if (preg_match('#^project/([^/]+)/tables/([^/]+?)(/edit)?$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $tableName = escape_string($m[2]);
            $isEdit = !empty($m[3]);

            $form = query("SELECT fs.* FROM table_settings fs JOIN projects p ON fs.project = p.link WHERE p.link='$projectLink' AND fs.table_name='$tableName'");
            if ($form && mysqli_num_rows($form) > 0) {
                $f = fetch_assoc($form);
                $suffix = $isEdit ? '_edit' : '';
                $response->json([
                    'exists' => true,
                    'page' => [
                        'id' => 'table_' . $f['table_id'] . $suffix,
                        'url' => $url,
                        'showTitle' => true,
                        'icon' => 'list-outline',
                        'title' => $f['table_name'],
                        'html' => '',
                        'pageID' => 'table_' . $f['table_id'] . $suffix,
                    ]
                ]);
                return;
            }
        }

        // 6. Check project management routes
        if (preg_match('#^project/([^/]+)/(manage|new)/(codespaces|codespace|tables|table|apis)$#', $url, $m)) {
            $projectLink = escape_string($m[1]);
            $action = $m[2];
            $type = $m[3];
            $project = query("SELECT projectID, name FROM projects WHERE link='$projectLink'");
            if ($project && mysqli_num_rows($project) > 0) {
                $p = fetch_assoc($project);
                $icons = ['codespaces' => 'code-outline', 'codespace' => 'add-circle-outline', 'tables' => 'document-outline', 'table' => 'document-outline', 'apis' => 'albums-outline'];
                $titles = ['codespaces' => 'Manage Codespaces', 'codespace' => 'New Codespace', 'tables' => 'Manage Tables', 'table' => 'New Table', 'apis' => 'Manage APIs'];
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

        $response->json(['exists' => false, 'page' => null]);
    }
}
