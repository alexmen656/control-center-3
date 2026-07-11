<?php

require_once __DIR__ . '/../helpers/project.php';

class RolesController
{
    public function getAllRoles(Request $request, Response $response): void
    {
        $response->json(['roles' => $this->fetchAllRoles()]);
    }

    public function getRole(Request $request, Response $response): void
    {
        $roleId = $request->params['id'] ?? $request->input('roleId');

        if ($roleId === null) {
            $response->error('Role ID is required', 400);
            return;
        }

        $role = $this->fetchRoleById($roleId);

        if ($role) {
            $response->json(['role' => $role]);
        } else {
            $response->error('Role not found', 404);
        }
    }

    public function getUserRole(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');

        if ($projectLink === null) {
            $response->error('Project is required', 400);
            return;
        }

        $project = getProjectByLink(escape_string($projectLink));

        if (!$project) {
            $response->error('Project not found', 404);
            return;
        }

        $role = $this->fetchUserRoleInProject($request->userID, $project['projectID']);

        if ($role) {
            $response->json(['role' => $role]);
        } else {
            $response->error('User has no role in this project', 404);
        }
    }

    public function checkPermission(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $resource = $request->input('resource');
        $action = $request->input('action');

        if ($projectLink === null || $resource === null || $action === null) {
            $response->error('Project, resource and action are required', 400);
            return;
        }

        $project = getProjectByLink(escape_string($projectLink));

        if (!$project) {
            $response->error('Project not found', 404);
            return;
        }

        $hasPermission = $this->checkUserPermission(
            $request->userID,
            $project['projectID'],
            $resource,
            $action
        );

        $response->json([
            'hasPermission' => $hasPermission,
            'resource' => $resource,
            'action' => $action
        ]);
    }

    public function createRole(Request $request, Response $response): void
    {
        $name = $request->input('name');
        $slug = $request->input('slug');
        $permissionsInput = $request->input('permissions');

        if ($name === null || $slug === null || $permissionsInput === null) {
            $response->error('Name, slug and permissions are required', 400);
            return;
        }

        $description = $request->input('description', '');
        $permissions = is_array($permissionsInput) ? $permissionsInput : json_decode($permissionsInput, true);

        if (!$permissions) {
            $response->error('Invalid permissions format', 400);
            return;
        }

        $roleId = $this->insertRole($name, $slug, $description, $permissions);

        if ($roleId) {
            $response->json(['roleId' => $roleId, 'message' => 'Role created successfully']);
        } else {
            $response->error('Failed to create role', 500);
        }
    }

    public function updateRole(Request $request, Response $response): void
    {
        $roleId = $request->params['id'] ?? $request->input('roleId');
        $name = $request->input('name');
        $slug = $request->input('slug');
        $permissionsInput = $request->input('permissions');

        if ($roleId === null || $name === null || $slug === null || $permissionsInput === null) {
            $response->error('Role ID, name, slug and permissions are required', 400);
            return;
        }

        $description = $request->input('description', '');
        $permissions = is_array($permissionsInput) ? $permissionsInput : json_decode($permissionsInput, true);

        if (!$permissions) {
            $response->error('Invalid permissions format', 400);
            return;
        }

        $success = $this->modifyRole($roleId, $name, $slug, $description, $permissions);

        if ($success) {
            $response->success([], 'Role updated successfully');
        } else {
            $response->error('Failed to update role or role is a system role', 400);
        }
    }

    public function deleteRole(Request $request, Response $response): void
    {
        $roleId = $request->params['id'] ?? $request->input('roleId');

        if ($roleId === null) {
            $response->error('Role ID is required', 400);
            return;
        }

        $success = $this->removeRole($roleId);

        if ($success) {
            $response->success([], 'Role deleted successfully');
        } else {
            $response->error('Failed to delete role or role is a system role', 400);
        }
    }

    public function assignRole(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');
        $targetUserId = $request->input('targetUserId');
        $roleId = $request->input('roleId');

        if ($projectLink === null || $targetUserId === null || $roleId === null) {
            $response->error('Project, target user ID and role ID are required', 400);
            return;
        }

        $project = getProjectByLink(escape_string($projectLink));

        if (!$project) {
            $response->error('Project not found', 404);
            return;
        }

        if (!$this->checkUserPermission($request->userID, $project['projectID'], 'project', 'manage_users')) {
            $response->error("You don't have permission to manage users", 403);
            return;
        }

        $success = $this->assignRoleToUser($targetUserId, $project['projectID'], $roleId);

        if ($success) {
            $response->success([], 'Role assigned successfully');
        } else {
            $response->error('Failed to assign role', 500);
        }
    }

    public function getUsersWithRoles(Request $request, Response $response): void
    {
        $projectLink = $request->input('project');

        if ($projectLink === null) {
            $response->error('Project is required', 400);
            return;
        }

        $project = getProjectByLink(escape_string($projectLink));

        if (!$project) {
            $response->error('Project not found', 404);
            return;
        }

        $response->json(['users' => $this->fetchUsersWithRolesByProjectID($project['projectID'])]);
    }

    private function fetchAllRoles(): array
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

    private function fetchRoleById($roleId)
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

    private function fetchUserRoleInProject($userID, $projectID)
    {
        $userID = (int) escape_string($userID);
        $projectID = escape_string($projectID);

        $result = query("SELECT role_id FROM control_center_user_projects
                     WHERE userID=$userID AND projectID='$projectID'");

        if ($result && mysqli_num_rows($result) == 1) {
            $data = fetch_assoc($result);
            if ($data['role_id']) {
                return $this->fetchRoleById($data['role_id']);
            }
        }

        return null;
    }

    private function checkUserPermission($userID, $projectID, $resource, $action)
    {
        $role = $this->fetchUserRoleInProject($userID, $projectID);

        if (!$role) {
            return false;
        }

        $permissions = $role['permissions'];

        if (isset($permissions[$resource]) && isset($permissions[$resource][$action])) {
            return (bool) $permissions[$resource][$action];
        }

        return false;
    }

    private function insertRole($name, $slug, $description, $permissions)
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

    private function modifyRole($roleId, $name, $slug, $description, $permissions)
    {
        $roleId = (int) escape_string($roleId);

        $role = $this->fetchRoleById($roleId);
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

    private function removeRole($roleId)
    {
        $roleId = (int) escape_string($roleId);
        $role = $this->fetchRoleById($roleId);

        if (!$role || $role['is_system_role']) {
            return false;
        }

        return (bool) query("DELETE FROM project_roles WHERE id=$roleId AND is_system_role=FALSE");
    }

    private function assignRoleToUser($userID, $projectID, $roleId)
    {
        $userID = (int) escape_string($userID);
        $projectID = escape_string($projectID);
        $roleId = (int) escape_string($roleId);

        $role = $this->fetchRoleById($roleId);
        if (!$role) {
            return false;
        }

        return (bool) query("UPDATE control_center_user_projects
                         SET role_id=$roleId
                         WHERE userID=$userID AND projectID='$projectID'");
    }

    private function fetchUsersWithRolesByProjectID($projectID)
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
                        $role = $this->fetchRoleById($user['role_id']);
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
}
