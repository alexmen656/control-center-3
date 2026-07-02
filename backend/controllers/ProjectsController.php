<?php

require_once __DIR__ . '/../project_helper.php';

class ProjectsController
{
    /**
     * GET /v2/projects
     */
    public function getUserProjects(Request $request, Response $response): void
    {
        $response->json(getUserProjectsByUserID($request->userID));
    }

    /**
     * GET /v2/projects/all
     */
    public function getAll(Request $request, Response $response): void
    {
        $projects = query("SELECT projectID, icon, name, link FROM projects ORDER BY name ASC");
        $list = [];
        foreach ($projects as $p) {
            $list[] = ['id' => $p['projectID'], 'icon' => $p['icon'], 'name' => $p['name'], 'link' => $p['link']];
        }
        $response->success(['projects' => $list]);
    }

    /**
     * GET /v2/projects/import
     */
    public function getForImport(Request $request, Response $response): void
    {
        $current = $request->input('current_project', '');
        $projects = [];
        foreach (getUserProjectsByUserID($request->userID) as $p) {
            if ($p['name'] !== $current) {
                $projects[] = ['name' => $p['name'], 'display_name' => $p['name'], 'icon' => $p['icon']];
            }
        }
        $response->json($projects);
    }

    /**
     * GET /v2/projects/{link}
     */
    public function getByLink(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }
        $response->success($project);
    }

    /**
     * GET /v2/projects/{link}/info
     */
    public function getInfo(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }
        $response->success([
            'icon' => $project['icon'],
            'name' => $project['name'],
            'projectID' => $project['projectID'],
            'createdOn' => $project['createdOn']
        ]);
    }

    /**
     * GET /v2/projects/{link}/users
     */
    public function getUsers(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }
        $response->success(['users' => getUsersByProjectID($project['projectID'])]);
    }

    /**
     * GET /v2/projects/{link}/views
     */
    public function getViews(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }
        $response->success(['views' => getProjectViewsByProjectID($project['projectID'])]);
    }

    /**
     * GET /v2/projects/{link}/permissions
     */
    public function checkPermissions(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }

        if (checkUserProjectPermission($request->userID, $project['projectID'])) {
            $response->success(['success' => 'authorized']);
        } else {
            $response->error('permission', 200);//403
        }
    }

    /**
     * GET /v2/projects/{link}/web-builder
     */
    public function openWebBuilder(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }

        $response->success([
            'url' => getWebBuilderUrl($project['link']),
            'projectID' => $project['projectID'],
            'projectName' => $project['name']
        ]);
    }

    /**
     * POST /v2/projects
     */
    public function create(Request $request, Response $response): void
    {
        $name = escape_string($request->input('projectName', ''));
        $icon = escape_string($request->input('projectIcon', ''));

        if (empty($name)) {
            $response->error('projectName is required', 400);
            return;
        }

        $href = str_replace("\\", "", createLink($name));
        $projectID = generateRandomString(20);

        if (projectExists($name)) {
            $response->error('A project with this name already exists', 409);
            return;
        }

        if (!query("INSERT INTO projects VALUES (0, '$icon', '$name', '$href', CURDATE(), '$projectID', 0)")) {
            $response->error('Failed to create project', 500);
            return;
        }

        $endpoints = [
            ['', 'Project Dashboard', '', 'true'],
            ['new/tool', 'Create new tool', '', 'true'],
            ['manage/tools', 'Manage Tools', '', 'true'],
            ['manage/pages', 'Manage Pages', '', 'true'],
            ['new/wb', 'Create New Web Builder Project', '', 'true'],
            ['info', 'Project Info', '', 'true'],
            ['page/main', 'Main', '', 'true'],
            ['module-store', 'Module Store', '', 'false'],
            ['package-manager', 'Package Manager', '', 'true'],
            ['filesystem', 'Filesystem', 'file-tray-full-outlinepr', 'true']
        ];

        $urls = [];
        $pageValues = [];
        foreach ($endpoints as [$path, $title, $epIcon, $visible]) {
            $url = "project/$href" . ($path ? "/$path" : "");
            $urls[] = $url;
            $pageValues[] = "(0, '$url', '$visible', '$epIcon', '$title', '', 0)";
        }

        query("INSERT INTO control_center_pages VALUES " . implode(', ', $pageValues));

        foreach ($urls as $u) {
            $page = query("SELECT id FROM control_center_pages WHERE url='$u'");
            if (mysqli_num_rows($page) == 1) {
                $pageID = fetch_assoc($page)['id'];
                query("INSERT INTO control_center_project_views VALUES (0, $pageID, '$projectID')");
            }
        }

        query("INSERT INTO project_components VALUES (0, 'main.php', 'script', 'Main', 'main', NOW(), NOW(), 'System', '1234567890', '$projectID', NULL)");

        query("INSERT INTO project_tools (`id`, `icon`, `name`, `link`, `hasConfig`, `order`, `projectID`) VALUES
            (0, 'file-tray-full-outline', 'Filesystem', 'filesystem', 0, 0, '$projectID'),
            (0, 'storefront-outline', 'Module Store', 'module-store', 0, 1, '$projectID')");

        query("INSERT INTO project_sidebar_sections
            (projectID, name, slug, icon, order_index, is_default, is_collapsible, show_add_button, add_button_route, info_route, manage_route)
            VALUES ('$projectID', 'Tables', 'tables', 'list-outline', 1, 1, 1, 1, '/project/$href/new/form', '/info/forms/', '/project/$href/manage/forms')");

        if (!addUserToProject($request->userID, $projectID)) {
            $response->error('Failed to add user to project', 500);
            return;
        }

        if (createFileSystem($projectID)) {
            $response->success(['projectID' => $projectID, 'link' => $href], 'The project was created successfully.');
        } else {
            $response->success(['projectID' => $projectID, 'link' => $href], 'Project created but file system setup failed');
        }
    }

    /**
     * PUT /v2/projects/{id}
     */
    public function update(Request $request, Response $response): void
    {
        $id = escape_string($request->params['id']);
        $name = escape_string($request->input('projectName', ''));
        $icon = escape_string($request->input('projectIcon', ''));

        $project = query("SELECT * FROM projects WHERE id='$id'");
        if (mysqli_num_rows($project) == 0) {
            $response->error('Project not found', 404);
            return;
        }

        $projectData = fetch_assoc($project);
        if (!checkUserProjectPermission($request->userID, $projectData['projectID'])) {
            $response->error('Permission denied', 403);
            return;
        }

        $fields = [];
        if ($name !== '')
            $fields[] = "name='$name'";
        if ($icon !== '')
            $fields[] = "icon='$icon'";

        if (empty($fields)) {
            $response->error('No fields to update', 400);
            return;
        }

        if (query("UPDATE projects SET " . implode(", ", $fields) . " WHERE id='$id'")) {
            $response->success([], 'Project updated successfully');
        } else {
            $response->error('Failed to update project', 500);
        }
    }

    /**
     * DELETE /v2/projects/{id}
     */
    public function delete(Request $request, Response $response): void
    {
        $id = escape_string($request->params['id']);

        if (query("DELETE FROM projects WHERE id='$id'")) {
            $response->success([], 'Project deleted successfully');
        } else {
            $response->error('Failed to delete project', 500);
        }
    }

    /**
     * PUT /v2/projects/{id}/visibility
     */
    public function toggleVisibility(Request $request, Response $response): void
    {
        $id = escape_string($request->params['id']);
        $hidden = $request->input('hidden');
        $hidden = ($hidden === 'true' || $hidden === true);

        $project = query("SELECT * FROM projects WHERE id='$id'");
        if (mysqli_num_rows($project) == 0) {
            $response->error('Project not found', 404);
            return;
        }

        $projectData = fetch_assoc($project);
        if (!checkUserProjectPermission($request->userID, $projectData['projectID'])) {
            $response->error('Permission denied', 403);
            return;
        }

        $checkColumn = query("SHOW COLUMNS FROM projects LIKE 'hidden'");
        if (mysqli_num_rows($checkColumn) == 0) {
            query("ALTER TABLE projects ADD COLUMN hidden BOOLEAN DEFAULT FALSE");
        }

        if (query("UPDATE projects SET hidden=" . ($hidden ? 1 : 0) . " WHERE id='$id'")) {
            $response->success([], 'Project visibility updated successfully');
        } else {
            $response->error('Failed to update project visibility', 500);
        }
    }

    /**
     * POST /v2/projects/{link}/users
     */
    public function addUser(Request $request, Response $response): void
    {
        $project = getProjectByLink(escape_string($request->params['link']));
        if (!$project) {
            $response->error('No project found', 404);
            return;
        }

        $email = escape_string($request->input('email', ''));
        if (empty($email)) {
            $response->error('email is required', 400);
            return;
        }

        $user = query("SELECT * FROM control_center_users WHERE email='$email'");
        if (mysqli_num_rows($user) != 1) {
            $response->error('User not found', 404);
            return;
        }

        $newUserID = fetch_assoc($user)['userID'];

        $roleId = null;
        $roleIdInput = $request->input('roleId');
        $roleSlugInput = $request->input('role');

        if (!empty($roleIdInput)) {
            $roleId = (int) $roleIdInput;
        } elseif (!empty($roleSlugInput)) {
            $roleSlug = escape_string($roleSlugInput);
            $roleQuery = query("SELECT id FROM project_roles WHERE slug='$roleSlug' LIMIT 1");
            if ($roleQuery && mysqli_num_rows($roleQuery) == 1) {
                $roleId = fetch_assoc($roleQuery)['id'];
            }
        }

        if (addUserToProject($newUserID, $project['projectID'], $roleId)) {
            $response->success([], 'User added to project successfully');
        } else {
            $response->error('Failed to add user to project', 500);
        }
    }
}
