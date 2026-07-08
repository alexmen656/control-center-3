<?php
require_once "head.php";

function getAllRoles()
{
    $roles = query("SELECT * FROM project_roles ORDER BY is_system_role DESC, name ASC");
    $result = [];

    if ($roles && mysqli_num_rows($roles) > 0) {
        foreach ($roles as $role) {
            $result[] = [
                'id' => $role['id'],
                'name' => $role['name'],
                'slug' => $role['slug'],
                'description' => $role['description'],
                'permissions' => json_decode($role['permissions'], true),
                'is_system_role' => (bool) $role['is_system_role']
            ];
        }
    }

    return $result;
}

function getRoleById($roleId)
{
    $roleId = (int) escape_string($roleId);
    $role = query("SELECT * FROM project_roles WHERE id=$roleId");

    if ($role && mysqli_num_rows($role) == 1) {
        $data = fetch_assoc($role);
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'permissions' => json_decode($data['permissions'], true),
            'is_system_role' => (bool) $data['is_system_role']
        ];
    }

    return null;
}

function getRoleBySlug($slug)
{
    $slug = escape_string($slug);
    $role = query("SELECT * FROM project_roles WHERE slug='$slug'");

    if ($role && mysqli_num_rows($role) == 1) {
        $data = fetch_assoc($role);
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'permissions' => json_decode($data['permissions'], true),
            'is_system_role' => (bool) $data['is_system_role']
        ];
    }

    return null;
}

function getUserRoleInProject($userID, $projectID)
{
    $userID = (int) escape_string($userID);
    $projectID = escape_string($projectID);

    $result = query("SELECT role_id FROM control_center_user_projects 
                     WHERE userID=$userID AND projectID='$projectID'");

    if ($result && mysqli_num_rows($result) == 1) {
        $data = fetch_assoc($result);
        if ($data['role_id']) {
            return getRoleById($data['role_id']);
        }
    }

    return null;
}

function checkUserPermission($userID, $projectID, $resource, $action)
{
    $role = getUserRoleInProject($userID, $projectID);

    if (!$role) {
        return false;
    }

    $permissions = $role['permissions'];

    if (isset($permissions[$resource]) && isset($permissions[$resource][$action])) {
        return (bool) $permissions[$resource][$action];
    }

    return false;
}

function createRole($name, $slug, $description, $permissions)
{
    $name = escape_string($name);
    $slug = escape_string($slug);
    $description = escape_string($description);
    $permissionsJson = mysqli_real_escape_string($GLOBALS['con'], json_encode($permissions));

    $check = query("SELECT * FROM project_roles WHERE slug='$slug'");
    if ($check && mysqli_num_rows($check) > 0) {
        return false;
    }

    $result = query("INSERT INTO project_roles (name, slug, description, permissions, is_system_role) 
                     VALUES ('$name', '$slug', '$description', '$permissionsJson', FALSE)");

    if ($result) {
        return mysqli_insert_id($GLOBALS['con']);
    }

    return false;
}

function updateRole($roleId, $name, $slug, $description, $permissions)
{
    $roleId = (int) escape_string($roleId);

    $role = getRoleById($roleId);
    if (!$role || $role['is_system_role']) {
        return false;
    }

    $name = escape_string($name);
    $slug = escape_string($slug);
    $description = escape_string($description);
    $permissionsJson = mysqli_real_escape_string($GLOBALS['con'], json_encode($permissions));

    return (bool) query("UPDATE project_roles 
                         SET name='$name', slug='$slug', description='$description', 
                             permissions='$permissionsJson' 
                         WHERE id=$roleId AND is_system_role=FALSE");
}

function deleteRole($roleId)
{
    $roleId = (int) escape_string($roleId);
    $role = getRoleById($roleId);

    if (!$role || $role['is_system_role']) {
        return false;
    }

    return (bool) query("DELETE FROM project_roles WHERE id=$roleId AND is_system_role=FALSE");
}

function assignRoleToUser($userID, $projectID, $roleId)
{
    $userID = (int) escape_string($userID);
    $projectID = escape_string($projectID);
    $roleId = (int) escape_string($roleId);

    $role = getRoleById($roleId);
    if (!$role) {
        return false;
    }

    return (bool) query("UPDATE control_center_user_projects 
                         SET role_id=$roleId 
                         WHERE userID=$userID AND projectID='$projectID'");
}

function getUsersWithRolesByProjectID($projectID)
{
    $projectID = escape_string($projectID);
    $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
    $result = [];

    if ($users && mysqli_num_rows($users) > 0) {
        foreach ($users as $user) {
            $userID = $user['userID'];
            $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));

            if ($userData) {
                $role = null;
                if ($user['role_id']) {
                    $role = getRoleById($user['role_id']);
                }

                $result[] = [
                    'id' => $userData['userID'],
                    'name' => $userData['firstname'] . " " . $userData['lastname'],
                    'email' => $userData['email'],
                    'role' => $role
                ];
            }
        }
    }

    return $result;
}

function handleGetAllRoles()
{
    echo echoJson(['roles' => getAllRoles()]);
}

function handleGetRole()
{
    if (!isset($_POST['roleId'])) {
        echo echoJson("Role ID is required", false);
        return;
    }

    $role = getRoleById($_POST['roleId']);
    echo $role
        ? echoJson(['role' => $role])
        : echoJson("Role not found", false);
}

function handleGetUserRoleInProject($userID)
{
    if (!isset($_POST['project'])) {
        echo echoJson("Project is required", false);
        return;
    }

    require_once "helpers/project.php";
    $project = getProjectByLink(escape_string($_POST['project']));

    if (!$project) {
        echo echoJson("Project not found", false);
        return;
    }

    $role = getUserRoleInProject($userID, $project['projectID']);
    echo $role
        ? echoJson(['role' => $role])
        : echoJson("User has no role in this project", false);
}

function handleCheckPermission($userID)
{
    if (!isset($_POST['project'], $_POST['resource'], $_POST['action'])) {
        echo echoJson("Project, resource and action are required", false);
        return;
    }

    require_once "helpers/project.php";
    $project = getProjectByLink(escape_string($_POST['project']));

    if (!$project) {
        echo echoJson("Project not found", false);
        return;
    }

    $hasPermission = checkUserPermission(
        $userID,
        $project['projectID'],
        $_POST['resource'],
        $_POST['action']
    );

    echo echoJson([
        'hasPermission' => $hasPermission,
        'resource' => $_POST['resource'],
        'action' => $_POST['action']
    ]);
}

function handleCreateRole($userID)
{
    if (!isset($_POST['name'], $_POST['slug'], $_POST['permissions'])) {
        echo echoJson("Name, slug and permissions are required", false);
        return;
    }

    $description = $_POST['description'] ?? '';
    $permissions = json_decode($_POST['permissions'], true);

    if (!$permissions) {
        echo echoJson("Invalid permissions format", false);
        return;
    }

    $roleId = createRole($_POST['name'], $_POST['slug'], $description, $permissions);

    echo $roleId
        ? echoJson(['roleId' => $roleId, 'message' => 'Role created successfully'])
        : echoJson("Failed to create role", false);
}

function handleUpdateRole($userID)
{
    if (!isset($_POST['roleId'], $_POST['name'], $_POST['slug'], $_POST['permissions'])) {
        echo echoJson("Role ID, name, slug and permissions are required", false);
        return;
    }

    $description = $_POST['description'] ?? '';
    $permissions = json_decode($_POST['permissions'], true);

    if (!$permissions) {
        echo echoJson("Invalid permissions format", false);
        return;
    }

    $success = updateRole(
        $_POST['roleId'],
        $_POST['name'],
        $_POST['slug'],
        $description,
        $permissions
    );

    echo $success
        ? echoJson("Role updated successfully")
        : echoJson("Failed to update role or role is a system role", false);
}

function handleDeleteRole($userID)
{
    if (!isset($_POST['roleId'])) {
        echo echoJson("Role ID is required", false);
        return;
    }

    $success = deleteRole($_POST['roleId']);

    echo $success
        ? echoJson("Role deleted successfully")
        : echoJson("Failed to delete role or role is a system role", false);
}

function handleAssignRole($userID)
{
    if (!isset($_POST['project'], $_POST['targetUserId'], $_POST['roleId'])) {
        echo echoJson("Project, target user ID and role ID are required", false);
        return;
    }

    require_once "helpers/project.php";
    $project = getProjectByLink(escape_string($_POST['project']));

    if (!$project) {
        echo echoJson("Project not found", false);
        return;
    }

    if (!checkUserPermission($userID, $project['projectID'], 'project', 'manage_users')) {
        echo echoJson("You don't have permission to manage users", false);
        return;
    }

    $success = assignRoleToUser(
        $_POST['targetUserId'],
        $project['projectID'],
        $_POST['roleId']
    );

    echo $success
        ? echoJson("Role assigned successfully")
        : echoJson("Failed to assign role", false);
}

function handleGetUsersWithRoles()
{
    if (!isset($_POST['project'])) {
        echo echoJson("Project is required", false);
        return;
    }

    require_once "helpers/project.php";
    $project = getProjectByLink(escape_string($_POST['project']));

    if (!$project) {
        echo echoJson("Project not found", false);
        return;
    }

    echo echoJson(['users' => getUsersWithRolesByProjectID($project['projectID'])]);
}

if (isset($_POST['getAllRoles'])) {
    handleGetAllRoles();
} elseif (isset($_POST['getRole'])) {
    handleGetRole();
} elseif (isset($_POST['getUserRole'])) {
    handleGetUserRoleInProject($userID);
} elseif (isset($_POST['checkPermission'])) {
    handleCheckPermission($userID);
} elseif (isset($_POST['createRole'])) {
    handleCreateRole($userID);
} elseif (isset($_POST['updateRole'])) {
    handleUpdateRole($userID);
} elseif (isset($_POST['deleteRole'])) {
    handleDeleteRole($userID);
} elseif (isset($_POST['assignRole'])) {
    handleAssignRole($userID);
} elseif (isset($_POST['getUsersWithRoles'])) {
    handleGetUsersWithRoles();
}
