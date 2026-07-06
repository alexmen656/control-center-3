<?php

require_once __DIR__ . '/../project_helper.php';
require_once __DIR__ . '/../helpers/cloudflare.php';

class CodespacesController
{
    public function list(Request $request, Response $response): void
    {
        try {
            $projectID = getProjectID(escape_string($request->input('project', '')));
        } catch (Exception $e) {
            $response->error('Project not found', 404);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('No permission for this project', 403);
            return;
        }

        $codespaces = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");
        $result = [];

        foreach ($codespaces as $codespace) {
            $result[] = [
                'id' => $codespace['id'],
                'name' => $codespace['name'],
                'slug' => $codespace['slug'],
                'description' => $codespace['description'],
                'icon' => $codespace['icon'],
                'language' => $codespace['language'],
                'template' => $codespace['template'],
                'status' => $codespace['status'],
                'created_at' => $codespace['created_at'],
                'updated_at' => $codespace['updated_at'],
                'order_index' => $codespace['order_index']
            ];
        }

        $response->json(['success' => true, 'codespaces' => $result]);
    }

    public function create(Request $request, Response $response): void
    {
        $userID = $request->userID;

        try {
            $projectID = getProjectID(escape_string($request->input('project', '')));
        } catch (Exception $e) {
            $response->error('Project not found: ' . $request->input('project', ''), 404);
            return;
        }

        $name = escape_string($request->input('name', ''));
        if (!$name) {
            $response->error('Name is required', 400);
            return;
        }

        $description = escape_string($request->input('description', ''));
        $icon = escape_string($request->input('icon', 'code-outline'));
        $language = escape_string($request->input('language', 'javascript'));
        $template = escape_string($request->input('template', 'default'));
        $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');

        if (!checkUserProjectPermission($userID, $projectID)) {
            $response->error('No permission for this project', 403);
            return;
        }

        if ($this->slugExists($projectID, $slug)) {
            $response->error('A codespace with this name already exists', 409);
            return;
        }

        $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$projectID'");
        $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
        $newOrder = $maxOrder + 1;

        $result = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index)
                        VALUES ('$name', '$slug', '$description', '$icon', '$language', '$template', '$projectID', '$userID', '$newOrder')");

        if (!$result) {
            $response->error('Failed to create codespace', 500);
            return;
        }

        $codespaceId = mysqli_insert_id($GLOBALS['con']);

        $response->json([
            'success' => true,
            'message' => 'Codespace created successfully',
            'codespace' => [
                'id' => $codespaceId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'language' => $language,
                'status' => 'active'
            ]
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $codespaceID = (int) $request->params['id'];

        $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
        if (!$codespace || !checkUserProjectPermission($request->userID, $codespace['project_id'])) {
            $response->error('Codespace not found or no permission', 404);
            return;
        }

        $name = escape_string($request->input('name', ''));
        $description = escape_string($request->input('description', ''));
        $icon = escape_string($request->input('icon', ''));
        $language = escape_string($request->input('language', ''));
        $status = escape_string($request->input('status', ''));

        $updates = [];
        if (!empty($name)) {
            $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)), '-');
            $updates[] = "name='$name'";
            $updates[] = "slug='$slug'";
        }
        if (!empty($description)) $updates[] = "description='$description'";
        if (!empty($icon)) $updates[] = "icon='$icon'";
        if (!empty($language)) $updates[] = "language='$language'";
        if (!empty($status)) $updates[] = "status='$status'";

        if (empty($updates)) {
            $response->error('No fields to update', 400);
            return;
        }

        $result = query("UPDATE project_codespaces SET " . implode(', ', $updates) . " WHERE id='$codespaceID'");

        if ($result) {
            $response->success([], 'Codespace updated successfully');
        } else {
            $response->error('Failed to update codespace', 500);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $codespaceID = (int) $request->params['id'];

        $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
        if (!$codespace || !checkUserProjectPermission($request->userID, $codespace['project_id'])) {
            $response->error('Codespace not found or no permission', 404);
            return;
        }

        $project = getProjectByID($codespace['project_id']);
        if ($project) {
            $codespaceDir = dirname(__DIR__) . "/../data/projects/" . $request->userID . "/" . $project['link'] . "/" . $codespace['slug'];
            if (is_dir($codespaceDir)) {
                $this->deleteDirectory($codespaceDir);
            }
        }

        $result = query("DELETE FROM project_codespaces WHERE id='$codespaceID'");

        if ($result) {
            $response->success([], 'Codespace deleted successfully');
        } else {
            $response->error('Failed to delete codespace', 500);
        }
    }

    public function reorder(Request $request, Response $response): void
    {
        $projectID = escape_string($request->input('projectID', ''));
        $codespaces = $request->input('codespaces');

        if (!$projectID || !is_array($codespaces)) {
            $response->error('projectID and codespaces are required', 400);
            return;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('No permission for this project', 403);
            return;
        }

        foreach ($codespaces as $index => $codespaceData) {
            $codespaceID = (int) $codespaceData['id'];
            $orderIndex = (int) $index;
            query("UPDATE project_codespaces SET order_index='$orderIndex' WHERE id='$codespaceID' AND project_id='$projectID'");
        }

        $response->success([], 'Codespaces reordered successfully');
    }

    public function templates(Request $request, Response $response): void
    {
        $templatesDir = dirname(__DIR__) . "/templates/codespace/";
        $templates = [];

        if (is_dir($templatesDir)) {
            $templateDirs = array_filter(scandir($templatesDir), function ($item) use ($templatesDir) {
                return $item != '.' && $item != '..' && is_dir($templatesDir . $item);
            });

            foreach ($templateDirs as $templateDir) {
                $templates[] = [
                    'id' => $templateDir,
                    'name' => ucfirst(str_replace(['-', '_'], ' ', $templateDir)),
                    'description' => $this->getTemplateDescription($templateDir),
                    'icon' => $this->getTemplateIcon($templateDir)
                ];
            }
        }

        if (empty($templates)) {
            $templates[] = [
                'id' => 'vanilla-js',
                'name' => 'Vanilla JavaScript',
                'description' => 'Basic HTML, CSS and JavaScript setup',
                'icon' => 'logo-javascript'
            ];
        }

        $response->json(['success' => true, 'templates' => $templates]);
    }

    public function userProjects(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $projects = query("SELECT p.projectID, p.name, p.link, p.icon
                          FROM projects p
                          JOIN control_center_user_projects cup ON p.projectID = cup.projectID
                          WHERE cup.userID = '$userID'
                          ORDER BY p.name ASC");

        $result = [];
        foreach ($projects as $project) {
            $result[] = [
                'id' => $project['projectID'],
                'name' => $project['name'],
                'link' => $project['link'],
                'icon' => $project['icon'] ?? 'folder-outline'
            ];
        }

        $response->json(['success' => true, 'projects' => $result]);
    }

    public function transfer(Request $request, Response $response): void
    {
        $userID = $request->userID;
        $codespaceID = (int) $request->params['id'];
        $targetProjectLink = escape_string($request->input('targetProject', ''));

        $codespace = fetch_assoc(query("SELECT * FROM project_codespaces WHERE id='$codespaceID'"));
        if (!$codespace || !checkUserProjectPermission($userID, $codespace['project_id'])) {
            $response->error('Codespace not found or no permission', 404);
            return;
        }

        try {
            $targetProjectID = getProjectID($targetProjectLink);
        } catch (Exception $e) {
            $response->error('Target project not found', 404);
            return;
        }

        if (!checkUserProjectPermission($userID, $targetProjectID)) {
            $response->error('Target project not found or no permission', 403);
            return;
        }

        $targetSlug = $codespace['slug'];
        $conflictCount = 1;
        while ($this->slugExists($targetProjectID, $targetSlug)) {
            $targetSlug = $codespace['slug'] . '-' . $conflictCount;
            $conflictCount++;
        }

        $newCodespaceID = null;
        $targetDir = null;

        try {
            $sourceProject = getProjectByID($codespace['project_id']);
            $targetProject = getProjectByID($targetProjectID);

            if (!$sourceProject || !$targetProject) {
                $response->error('Project data error', 500);
                return;
            }

            $sourceDir = dirname(__DIR__) . "/../data/projects/" . $userID . "/" . $sourceProject['link'] . "/" . $codespace['slug'];
            $targetDir = dirname(__DIR__) . "/../data/projects/" . $userID . "/" . $targetProject['link'] . "/" . $targetSlug;

            if (!is_dir($sourceDir)) {
                $response->error('Source codespace directory not found', 404);
                return;
            }

            if (!$this->copyCodespaceDirectory($sourceDir, $targetDir)) {
                $response->error('Failed to copy codespace files', 500);
                return;
            }

            $orderResult = query("SELECT MAX(order_index) as max_order FROM project_codespaces WHERE project_id='$targetProjectID'");
            $maxOrder = fetch_assoc($orderResult)['max_order'] ?? 0;
            $newOrder = $maxOrder + 1;

            $newName = ($targetSlug !== $codespace['slug']) ? $codespace['name'] . ' (Copy)' : $codespace['name'];

            $insertResult = query("INSERT INTO project_codespaces (name, slug, description, icon, language, template, project_id, user_id, order_index, status)
                                  VALUES ('" . escape_string($newName) . "', '" . escape_string($targetSlug) . "', '" . escape_string($codespace['description']) . "', '" . escape_string($codespace['icon']) . "', '" . escape_string($codespace['language']) . "', '" . escape_string($codespace['template']) . "', '$targetProjectID', '$userID', '$newOrder', '" . escape_string($codespace['status']) . "')");

            if (!$insertResult) {
                $this->deleteDirectory($targetDir);
                $response->error('Failed to create codespace record', 500);
                return;
            }

            $newCodespaceID = mysqli_insert_id($GLOBALS['con']);

            $isMove = $request->input('moveCodespace') === 'true' || $request->input('moveCodespace') === true;

            if ($isMove) {
                $domainResult = query("SELECT * FROM codespace_domains WHERE codespace_id='$codespaceID'");
                if ($domainRow = fetch_assoc($domainResult)) {
                    removeDomainFromCloudflare($domainRow['domain']);
                }

                query("DELETE FROM codespace_domains WHERE codespace_id='$codespaceID'");
                query("DELETE FROM project_codespaces WHERE id='$codespaceID'");

                $this->deleteDirectory($sourceDir);

                $message = 'Codespace moved successfully';
            } else {
                $message = 'Codespace copied successfully';
            }

            $response->json([
                'success' => true,
                'message' => $message,
                'newCodespace' => [
                    'id' => $newCodespaceID,
                    'name' => $newName,
                    'slug' => $targetSlug,
                    'project_id' => $targetProjectID
                ]
            ]);
        } catch (Exception $e) {
            if ($targetDir && is_dir($targetDir)) {
                $this->deleteDirectory($targetDir);
            }
            if ($newCodespaceID) {
                query("DELETE FROM codespace_domains WHERE codespace_id='$newCodespaceID'");
                query("DELETE FROM project_codespaces WHERE id='$newCodespaceID'");
            }

            $response->error('Transfer failed: ' . $e->getMessage(), 500);
        }
    }

    private function slugExists($projectID, $slug): bool
    {
        $result = query("SELECT id FROM project_codespaces WHERE project_id='" . escape_string($projectID) . "' AND slug='" . escape_string($slug) . "'");
        return mysqli_num_rows($result) > 0;
    }

    private function deleteDirectory($dir): bool
    {
        if (!is_dir($dir)) return false;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    private function copyCodespaceDirectory($sourceDir, $targetDir): bool
    {
        if (!is_dir($sourceDir)) {
            return false;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
            return false;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $sourcePath = $item->getPathname();
                $relativePath = substr($sourcePath, strlen($sourceDir) + 1);
                $targetPath = $targetDir . '/' . $relativePath;

                if ($item->isDir()) {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0777, true);
                    }
                } else {
                    $targetDirPath = dirname($targetPath);
                    if (!is_dir($targetDirPath)) {
                        mkdir($targetDirPath, 0777, true);
                    }
                    copy($sourcePath, $targetPath);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error copying codespace directory: " . $e->getMessage());
            return false;
        }
    }

    private function getTemplateDescription($templateDir): string
    {
        $descriptions = [
            'vanilla-js' => 'Basic HTML, CSS and JavaScript setup',
            'react' => 'React application with Vite build tool',
            'vue' => 'Vue.js application with Vite build tool',
            'node' => 'Node.js server with Express framework',
            'angular' => 'Angular application with TypeScript',
            'svelte' => 'Svelte application with modern tooling',
            'next' => 'Next.js React framework',
            'nuxt' => 'Nuxt.js Vue framework'
        ];

        return $descriptions[$templateDir] ?? 'Custom development environment';
    }

    private function getTemplateIcon($templateDir): string
    {
        $icons = [
            'vanilla-js' => 'logo-javascript',
            'react' => 'logo-react',
            'vue' => 'logo-vue',
            'node' => 'logo-nodejs',
            'angular' => 'logo-angular',
            'svelte' => 'logo-web-component',
            'next' => 'logo-react',
            'nuxt' => 'logo-vue'
        ];

        return $icons[$templateDir] ?? 'code-outline';
    }
}

function removeDomainFromCloudflare($domain)
{
    $result = cloudflare_deleteRecordByDomain($domain, 'CNAME');

    if ($result['success']) {
        error_log("Successfully removed domain $domain from Cloudflare");
        return true;
    }

    error_log("Failed to remove domain $domain from Cloudflare: " . ($result['message'] ?? 'Unknown error'));
    return false;
}
