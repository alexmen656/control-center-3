<?php

function createFileSystemMainDir($projectID)
{
    $dir = "/data/project_filesystems/" . $projectID;
    if (!mkdir($dir, 0777, true)) {
        return false;
    }

    return chmod($dir, 0777);
}

function createFileSystem($projectID)
{
    if (!query("INSERT INTO project_filesystem VALUES (0, '', '', NULL, 0, '$projectID')")) {
        return false;
    }

    return createFileSystemMainDir($projectID);
}
function getProjectByLink($link)
{
    $link = escape_string($link);
    $query = query("SELECT * FROM projects WHERE link='$link'");

    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

function getProjectByID($projectID)
{
    $projectID = escape_string($projectID);
    $query = query("SELECT * FROM projects WHERE projectID='$projectID'");

    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

function getUserByToken($token)
{
    $token = escape_string($token);
    $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");

    return (mysqli_num_rows($data) == 1) ? fetch_assoc($data) : null;
}

function getUsersByProjectID($projectID)
{
    $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
    $result = [];

    if (mysqli_num_rows($users) > 0) {
        foreach ($users as $user) {
            $userID = $user['userID'];
            $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));

            if ($userData) {
                $result[] = [
                    'id' => $userData['userID'],
                    'name' => $userData['firstname'] . " " . $userData['lastname'],
                    'email' => $userData['email']
                ];
            }
        }
    }

    return $result;
}

function getProjectViewsByProjectID($projectID)
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

function getUserProjectsByUserID($userID)
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

function jsonResponse($data, $success = true)
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

function projectExists($name)
{
    $name = escape_string($name);
    $href = str_replace("\\", "", createLink($name));
    $check = query("SELECT * FROM projects WHERE link='$href' OR name='$name'");

    return mysqli_num_rows($check) > 0;
}

function addUserToProject($userID, $projectID)
{
    $userID = (int) $userID;
    $projectID = escape_string($projectID);
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");

    if (mysqli_num_rows($check) == 0) {
        return (bool) query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1)");
    }

    return true;
}