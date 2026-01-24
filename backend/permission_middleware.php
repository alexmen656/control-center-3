<?php
require_once "roles.php";
require_once "project_helper.php";

function requirePermission($userID, $projectIdentifier, $resource, $action)
{
    $project = is_array($projectIdentifier)
        ? $projectIdentifier
        : getProjectByLink($projectIdentifier);

    if (!$project) {
        http_response_code(404);
        echo jsonResponse("Project not found", false);
        exit;
    }

    if (!checkUserPermission($userID, $project['projectID'], $resource, $action)) {
        http_response_code(403);
        echo jsonResponse("You don't have permission to $action $resource", false);
        exit;
    }

    return true;
}

function requireAnyPermission($userID, $projectIdentifier, array $permissions)
{
    $project = is_array($projectIdentifier)
        ? $projectIdentifier
        : getProjectByLink($projectIdentifier);

    if (!$project) {
        http_response_code(404);
        echo jsonResponse("Project not found", false);
        exit;
    }

    foreach ($permissions as $permission) {
        list($resource, $action) = $permission;
        if (checkUserPermission($userID, $project['projectID'], $resource, $action)) {
            return true;
        }
    }

    http_response_code(403);
    echo jsonResponse("You don't have the required permissions", false);
    exit;
}

function requireAllPermissions($userID, $projectIdentifier, array $permissions)
{
    $project = is_array($projectIdentifier)
        ? $projectIdentifier
        : getProjectByLink($projectIdentifier);

    if (!$project) {
        http_response_code(404);
        echo jsonResponse("Project not found", false);
        exit;
    }

    foreach ($permissions as $permission) {
        list($resource, $action) = $permission;
        if (!checkUserPermission($userID, $project['projectID'], $resource, $action)) {
            http_response_code(403);
            echo jsonResponse("You don't have permission to $action $resource", false);
            exit;
        }
    }

    return true;
}

function getUserRoleOrFail($userID, $projectIdentifier)
{
    $project = is_array($projectIdentifier)
        ? $projectIdentifier
        : getProjectByLink($projectIdentifier);

    if (!$project) {
        http_response_code(404);
        echo jsonResponse("Project not found", false);
        exit;
    }

    $role = getUserRoleInProject($userID, $project['projectID']);

    if (!$role) {
        http_response_code(403);
        echo jsonResponse("You are not a member of this project", false);
        exit;
    }

    return $role;
}

function requireRole($userID, $projectIdentifier, $requiredRoleSlugs)
{
    if (!is_array($requiredRoleSlugs)) {
        $requiredRoleSlugs = [$requiredRoleSlugs];
    }

    $role = getUserRoleOrFail($userID, $projectIdentifier);

    if (!in_array($role['slug'], $requiredRoleSlugs)) {
        http_response_code(403);
        echo jsonResponse("You need one of these roles: " . implode(', ', $requiredRoleSlugs), false);
        exit;
    }

    return true;
}

function withPermission($resource, $action, callable $callback)
{
    global $userID;

    if (!isset($_POST['project'])) {
        echo jsonResponse("Project parameter is required", false);
        exit;
    }

    $project = getProjectByLink(escape_string($_POST['project']));

    if (!$project) {
        http_response_code(404);
        echo jsonResponse("Project not found", false);
        exit;
    }

    if (!checkUserPermission($userID, $project['projectID'], $resource, $action)) {
        http_response_code(403);
        echo jsonResponse("You don't have permission to $action $resource", false);
        exit;
    }

    return $callback($userID, $project);
}

function getAllowedActions($userID, $projectID, $resource)
{
    $role = getUserRoleInProject($userID, $projectID);

    if (!$role || !isset($role['permissions'][$resource])) {
        return [];
    }

    $allowedActions = [];
    foreach ($role['permissions'][$resource] as $action => $allowed) {
        if ($allowed) {
            $allowedActions[] = $action;
        }
    }

    return $allowedActions;
}

function getUserPermissions($userID, $projectID)
{
    $role = getUserRoleInProject($userID, $projectID);

    if (!$role) {
        return [];
    }

    return $role['permissions'];
}
