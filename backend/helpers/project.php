<?php

class ProjectHelper
{
    public static function createFileSystemMainDir($projectID)
    {
        $dir = "/var/www/api.fringelo.com/project_filesystems/" . $projectID;
        if (!mkdir($dir, 0777, true)) {
            return false;
        }

        return chmod($dir, 0777);
    }

    public static function createFileSystem($projectID)
    {
        if (!query("INSERT INTO project_filesystem VALUES (0, '', '', NULL, 0, '$projectID')")) {
            return false;
        }

        $rootQuery = query("SELECT id FROM project_filesystem WHERE name = '' AND projectID = '$projectID' ORDER BY id DESC LIMIT 1");

        if (!$rootQuery || mysqli_num_rows($rootQuery) == 0) {
            return false;
        }

        $rootId = fetch_assoc($rootQuery)['id'];

        if (!query("INSERT INTO project_filesystem VALUES (0, '.dev', NULL, '$rootId', 0, '$projectID')")) {
            return false;
        }

        return createFileSystemMainDir($projectID);
    }

    public static function getProjectByLink($link)
    {
        $link = escape_string($link);
        $query = query("SELECT * FROM projects WHERE link='$link'");

        return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
    }

    public static function getProjectByID($projectID)
    {
        $projectID = escape_string($projectID);
        $query = query("SELECT * FROM projects WHERE projectID='$projectID'");

        return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
    }

    public static function getUserByToken($token)
    {
        $token = escape_string($token);
        $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

        return (mysqli_num_rows($data) == 1) ? fetch_assoc($data) : null;
    }

    public static function getUsersByProjectID($projectID)
    {
        $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
        $result = [];

        if (mysqli_num_rows($users) > 0) {
            foreach ($users as $user) {
                $userID = $user['userID'];
                $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));

                if ($userData) {
                    $role = null;
                    if (isset($user['role_id']) && $user['role_id']) {
                        $roleData = fetch_assoc(query("SELECT * FROM project_roles WHERE id={$user['role_id']}"));
                        if ($roleData) {
                            $role = [
                                'id' => $roleData['id'],
                                'name' => $roleData['name'],
                                'slug' => $roleData['slug']
                            ];
                        }
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

    public static function getProjectViewsByProjectID($projectID)
    {
        $views = query("SELECT * FROM control_center_project_views WHERE projectID='$projectID'");
        $result = [];

        if (mysqli_num_rows($views) > 0) {
            foreach ($views as $view) {
                $viewID = $view['pageID'];
                $viewData = fetch_assoc(query("SELECT * FROM control_center_pages WHERE id='$viewID'"));

                if ($viewData) {
                    $result[] = [
                        'id' => $viewData['id'],
                        'name' => $viewData['title'],
                        'url' => $viewData['url'],
                        'icon' => $viewData['icon']
                    ];
                }
            }
        }

        return $result;
    }

    public static function getUserProjectsByUserID($userID)
    {
        $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
        $result = [];

        foreach ($projects as $p) {
            $projectID = $p['projectID'];
            $project = query("SELECT * FROM projects WHERE projectID='$projectID'");

            if (mysqli_num_rows($project) == 1) {
                $projectData = fetch_assoc($project);
                $result[] = [
                    "id" => $projectData['id'],
                    "icon" => $projectData['icon'],
                    "name" => $projectData['name'],
                    "link" => $projectData['link'],
                    "hidden" => isset($projectData['hidden']) ? (bool) $projectData['hidden'] : false,
                    "createdOn" => isset($projectData['createdOn']) ? $projectData['createdOn'] : null
                ];
            }
        }

        return $result;
    }

    public static function jsonResponse($data, $success = true)
    {
        if ($success) {
            if (is_string($data)) {
                $response = ['success' => true, 'message' => $data];
            } else {
                $response = array_merge(['success' => true], $data);
            }
        } else {
            $response = ['success' => false, 'message' => $data];
        }

        return echoJson($response);
    }

    public static function projectExists($name)
    {
        $name = escape_string($name);
        $href = str_replace("\\", "", createLink($name));
        $check = query("SELECT * FROM projects WHERE link='$href' OR name='$name'");

        return mysqli_num_rows($check) > 0;
    }

    public static function addUserToProject($userID, $projectID, $roleId = null)
    {
        $userID = (int) $userID;
        $projectID = escape_string($projectID);

        if ($roleId === null) {
            $ownerRole = query("SELECT id FROM project_roles WHERE slug='owner' LIMIT 1");
            if ($ownerRole && mysqli_num_rows($ownerRole) == 1) {
                $roleId = fetch_assoc($ownerRole)['id'];
            }
        }

        $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");

        if (mysqli_num_rows($check) == 0) {
            $roleIdValue = $roleId ? (int) $roleId : 'NULL';
            return (bool) query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1, $roleIdValue)");
        }

        return true;
    }
}

function createFileSystemMainDir($projectID)
{
    return ProjectHelper::createFileSystemMainDir($projectID);
}

function createFileSystem($projectID)
{
    return ProjectHelper::createFileSystem($projectID);
}

function getProjectByLink($link)
{
    return ProjectHelper::getProjectByLink($link);
}

function getProjectByID($projectID)
{
    return ProjectHelper::getProjectByID($projectID);
}

function getUserByToken($token)
{
    return ProjectHelper::getUserByToken($token);
}

function getUsersByProjectID($projectID)
{
    return ProjectHelper::getUsersByProjectID($projectID);
}

function getProjectViewsByProjectID($projectID)
{
    return ProjectHelper::getProjectViewsByProjectID($projectID);
}

function getUserProjectsByUserID($userID)
{
    return ProjectHelper::getUserProjectsByUserID($userID);
}

function jsonResponse($data, $success = true)
{
    return ProjectHelper::jsonResponse($data, $success);
}

function projectExists($name)
{
    return ProjectHelper::projectExists($name);
}

function addUserToProject($userID, $projectID, $roleId = null)
{
    return ProjectHelper::addUserToProject($userID, $projectID, $roleId);
}
