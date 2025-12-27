<?php
include "head.php";
include "project_helper.php";

function handleCreateProject($userID)
{
    if (!isset($_POST['projectName']))
        return;

    $name = escape_string($_POST['projectName']);
    $icon = escape_string($_POST['projectIcon']);
    $href = str_replace("\\", "", createLink($name));
    $projectID = generateRandomString(20);

    if (projectExists($name)) {
        echo jsonResponse("A project with this name already exists", false);
        exit;
    }

    if (!query("INSERT INTO projects VALUES (0, '$icon', '$name', '$href', CURDATE(), '$projectID', 0)")) {
        echo jsonResponse("Failed to create project", false);
        exit;
    }

    $endpoints = [
        ['', 'Project Dashboard', '', 'true'],
        ['new-tool', 'Create new tool', '', 'true'],
        ['manage/tools', 'Manage Tools', '', 'true'],
        ['manage/pages', 'Manage Pages', '', 'true'],
        ['new/page', 'Create New Component', '', 'true'],
        ['info', 'Project Info', '', 'true'],
        ['page/main', 'Main', '', 'true'],
        ['module-store', 'Module Store', '', 'false'],
        ['package-manager', 'Package Manager', '', 'true'],
        ['filesystem', 'Filesystem', 'file-tray-full-outlinepr', 'true'],
        ['new/service', 'New Service', '', 'true'],
        ['manage/services', 'Manage Services', '', 'true'],
        ['web-builder', 'Web Builder', 'globe-outline', 'true']
    ];

    $urls = [];
    $pageValues = [];
    foreach ($endpoints as [$path, $title, $icon, $visible]) {
        $url = "project/$href" . ($path ? "/$path" : "");
        $urls[] = $url;
        $pageValues[] = "(0, '$url', '$visible', '$icon', '$title', '', 0)";
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
        (0, 'storefront-outline', 'Module Store', 'module-store', 0, 1, '$projectID'),
        (0, 'globe-outline', 'Web Builder', 'web-builder', 0, 2, '$projectID')");

    if (!addUserToProject($userID, $projectID)) {
        echo jsonResponse("Failed to add user to project", false);
        exit;
    }

    $serviceUrl = "project/$href/services/my-service";
    query("INSERT INTO project_services VALUES (0, 'cog-outline', 'My Service', 'my-service', 'Default service for your project', 'active', '$projectID')");
    query("INSERT INTO control_center_pages VALUES 
        (0, '$serviceUrl', 'true', 'cog-outline', 'My Service', '', 0),
        (0, '$serviceUrl/config', 'true', 'cog-outline', 'My Service Config', '', 0)");

    echo createFileSystem($projectID)
        ? jsonResponse('The project was created successfully. <a href="/paxar/projects/' . $href . '/">Go to the project</a>')
        : jsonResponse('Project created but file system setup failed', false);
}

function handleDeleteProject()
{
    if (!isset($_POST['projectID']))
        return;

    $id = escape_string($_POST['projectID']);
    echo query("DELETE FROM projects WHERE id='$id'")
        ? jsonResponse('Project deleted successfully')
        : jsonResponse('Failed to delete project', false);
}

function handleUpdateProject($userID)
{
    if (!isset($_POST['projectID']))
        return;

    $id = escape_string($_POST['projectID']);
    $name = isset($_POST['projectName']) ? escape_string($_POST['projectName']) : '';
    $icon = isset($_POST['projectIcon']) ? escape_string($_POST['projectIcon']) : '';

    $project = query("SELECT * FROM projects WHERE id='$id'");
    if (mysqli_num_rows($project) == 0) {
        echo jsonResponse('Project not found', false);
        exit;
    }

    $projectID = fetch_assoc($project)['projectID'];
    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse('Permission denied', false);
        exit;
    }

    $fields = [];
    if ($name !== '')
        $fields[] = "name='$name'";
    if ($icon !== '')
        $fields[] = "icon='$icon'";

    if (empty($fields)) {
        echo jsonResponse('No fields to update', false);
        exit;
    }

    echo query("UPDATE projects SET " . implode(", ", $fields) . " WHERE id='$id'")
        ? jsonResponse('Project updated successfully')
        : jsonResponse('Failed to update project', false);
}

function handleToggleVisibility($userID)
{
    if (!isset($_POST['projectID']))
        return;

    $id = escape_string($_POST['projectID']);
    $hidden = isset($_POST['hidden']) ? ($_POST['hidden'] === 'true' || $_POST['hidden'] === true) : false;

    $project = query("SELECT * FROM projects WHERE id='$id'");
    if (mysqli_num_rows($project) == 0) {
        echo jsonResponse('Project not found', false);
        exit;
    }

    $projectID = fetch_assoc($project)['projectID'];
    if (!checkUserProjectPermission($userID, $projectID)) {
        echo jsonResponse('Permission denied', false);
        exit;
    }

    $checkColumn = query("SHOW COLUMNS FROM projects LIKE 'hidden'");
    if (mysqli_num_rows($checkColumn) == 0) {
        query("ALTER TABLE projects ADD COLUMN hidden BOOLEAN DEFAULT FALSE");
    }

    echo query("UPDATE projects SET hidden=" . ($hidden ? 1 : 0) . " WHERE id='$id'")
        ? jsonResponse('Project visibility updated successfully')
        : jsonResponse('Failed to update project visibility', false);
}

function handleGetProjectInfo()
{
    if (!isset($_POST['project']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    echo $project
        ? jsonResponse(['icon' => $project['icon'], 'name' => $project['name'], 'createdOn' => $project['createdOn']])
        : jsonResponse("No project found", false);
}

function handleGetProject()
{
    if (!isset($_POST['link']))
        return;

    $project = getProjectByLink(escape_string($_POST['link']));
    echo $project
        ? jsonResponse($project)
        : jsonResponse("No project found", false);
}

function handleGetProjectUsers()
{
    if (!isset($_POST['project']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    echo $project
        ? jsonResponse(['users' => getUsersByProjectID($project['projectID'])])
        : jsonResponse("No project found", false);
}

function handleGetProjectViews()
{
    if (!isset($_POST['project']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    echo $project
        ? jsonResponse(['views' => getProjectViewsByProjectID($project['projectID'])])
        : jsonResponse("No project found", false);
}

function handleAddUserToProject()
{
    if (!isset($_POST['project'], $_POST['email']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    if (!$project) {
        echo jsonResponse("No project found", false);
        exit;
    }

    $user = query("SELECT * FROM control_center_users WHERE email='" . escape_string($_POST['email']) . "'");
    if (mysqli_num_rows($user) != 1) {
        echo jsonResponse("User not found", false);
        exit;
    }

    $newUserID = fetch_assoc($user)['userID'];
    echo addUserToProject($newUserID, $project['projectID'])
        ? jsonResponse("User added to project successfully")
        : jsonResponse("Failed to add user to project", false);
}

function handleCheckPermissions($userID)
{
    if (!isset($_POST['project']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    if (!$project) {
        echo jsonResponse("No project found", false);
        exit;
    }

    echo checkUserProjectPermission($userID, $project['projectID'])
        ? jsonResponse(["success" => "authorized"])
        : jsonResponse(["error" => "permission"], false);
}

function handleOpenWebBuilder()
{
    if (!isset($_POST['project']))
        return;

    $project = getProjectByLink(escape_string($_POST['project']));
    if (!$project) {
        echo jsonResponse("No project found", false);
        exit;
    }

    echo jsonResponse([
        "url" => getWebBuilderUrl($project['link']),
        "projectID" => $project['projectID'],
        "projectName" => $project['name']
    ]);
}

function handleGetAllProjects()
{
    try {
        $projects = query("SELECT projectID, icon, name, link FROM projects ORDER BY name ASC");
        $list = [];
        foreach ($projects as $p) {
            $list[] = ['id' => $p['projectID'], 'icon' => $p['icon'], 'name' => $p['name'], 'link' => $p['link']];
        }
        echo jsonResponse(['success' => true, 'projects' => $list]);
    } catch (Exception $e) {
        echo jsonResponse(['success' => false, 'message' => 'Error loading projects: ' . $e->getMessage()]);
    }
}

function handleGetProjectsForImport($userID)
{
    if (!isset($_POST['current_project']))
        return;

    if (!$userID) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    $current = escape_string($_POST['current_project']);
    $projects = [];
    foreach (getUserProjectsByUserID($userID) as $p) {
        if ($p['name'] !== $current) {
            $projects[] = ['name' => $p['name'], 'display_name' => $p['name'], 'icon' => $p['icon']];
        }
    }
    echo json_encode($projects);
}

if (isset($_POST['createProject']))
    handleCreateProject($userID);
elseif (isset($_POST['deleteProject']))
    handleDeleteProject();
elseif (isset($_POST['updateProject']))
    handleUpdateProject($userID);
elseif (isset($_POST['toggleProjectVisibility']))
    handleToggleVisibility($userID);
elseif (isset($_POST['getProject']))
    handleGetProject();
elseif (isset($_POST['getProjectInfo']))
    handleGetProjectInfo();
elseif (isset($_POST['getProjectUsers']))
    handleGetProjectUsers();
elseif (isset($_POST['getProjectViews']))
    handleGetProjectViews();
elseif (isset($_POST['addUserToProject']))
    handleAddUserToProject();
elseif (isset($_POST['checkUserPermissions']))
    handleCheckPermissions($userID);
elseif (isset($_POST['openWebBuilder']))
    handleOpenWebBuilder();
elseif (isset($_POST['getAllProjects']))
    handleGetAllProjects();
elseif (isset($_POST['get_projects_for_import']))
    handleGetProjectsForImport($userID);
else
    echo json_encode(getUserProjectsByUserID($userID));
