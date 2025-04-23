<?php
include "head.php";

$headers = getRequestHeaders();

function createFileSystemMainDir($projectID)
{

    mkdir("/data/project_filesystems/" . $projectID, 0777);
    echo "/data/project_filesystems/" . $projectID;
    $chmod = chmod("/data/project_filesystems/" . $projectID, 0777);
    //$chmod = true;
    if ($chmod) {
        return true;
    } else {
        return false;
    }
}
function createFileSystem($projectID)
{
    if (query("INSERT INTO project_filesystem VALUES (0, '', '', NULL, 0, '$projectID')")) {
        if (createFileSystemMainDir($projectID)) {
            return true;
        }
    } else {
        return false;
    }
}

if (isset($headers['Authorization'])) {
    //echo 0;
    if (isset($_POST['createProject']) && isset($_POST['projectName'])) {
        $name = escape_string($_POST['projectName']);
        $icon = escape_string($_POST['projectIcon']);
        $href = str_replace("\\", "", createLink($name));
        echo $href;
        //$createFolder = createFolder("/www/paxar/projects/".$href, 0777);
        //$createFile = createFile("/www/paxar/projects/".$href."/index.php", "", 0777);
        $projectID = generateRandomString(20);
        $mysqli = query("INSERT INTO projects VALUES (0, '$icon', '$name', '$href', CURDATE(), '$projectID')");
        $endpoints = [
            "",
            "new-tool",
            "manage/tools",
            "manage/pages",
            "new/page",
            "info",
            "page/main",
            "module-store",
            "package-manager",
            "filesystem"
        ];

        $urls = [];
        foreach ($endpoints as $endpoint) {
            $urls[] = "project/" . $href . ($endpoint ? "/" . $endpoint : "");
        }

        // Zugriff auf die URLs
        $url1 = $urls[0];
        $url2 = $urls[1];
        $url3 = $urls[2];
        $url4 = $urls[3];
        $url5 = $urls[4];
        $url6 = $urls[5];
        $url7 = $urls[6];
        $url8 = $urls[7];
        $url9 = $urls[8];
        $url10 = $urls[9];

        //$url9 = "project/" . $href . "/package-manager";

        mkdir("/www/" . $href, 0777);
        //echo $href;
        chmod("/www/" . $href, 0777);
        file_put_contents("/www/" . $href . "/index.php", str_replace(array('[{[pLink]}]', '[{[pName]}]', '[{[pID]}]'), array($href, $name, $projectID), file_get_contents("templates/website/index.php")), 0777);
        chmod("/www/" . $href . "/index.php", 0777);
        file_put_contents("/www/" . $href . "/main.php", "//Put here main content of your site", 0777);
        chmod("/www/" . $href . "/main.php", 0777);
        $mainComponent = query("INSERT INTO project_components VALUES (0, 'main.php', 'script', 'Main', 'main', NOW(), NOW(), 'System', '1234567890', '$projectID', NULL)");
        $pages = query("INSERT INTO control_center_pages VALUES (0, '$url1', 'true', '', 'Project Dashboard', '', 0), (0, '$url2', 'true', '', 'Create new tool', '', 0), (0, '$url3', 'true', '', 'Manage Tools', '', 0), (0, '$url4', 'true', '', 'Manage Pages', '', 0), (0, '$url5', 'true', '', 'Create New Component', '', 0), (0, '$url6', 'true', '', 'Project Info', '', 0), (0, '$url7', 'true', '', 'Main', '', 0), (0, '$url8', 'false', '', 'Module Store', '', 0), (0, '$url9', 'true', '', 'Package Manager', '', 0), (0, '$url10', 'true', 'file-tray-full-outlinepr', 'Filesystem', '', 0)");
        foreach($urls as $u){
            $page = query("SELECT * FROM control_center_pages WHERE url='$u'");
            if(mysqli_num_rows($page) == 1){
                $page = fetch_assoc($page);
                $pageID = $page['id'];
                query("INSERT INTO control_center_project_views VALUES (0, $pageID, '$projectID')");
            }
        }
        query("INSERT INTO project_tools (`id`, `icon`, `name`, `link`, `hasConfig`, `order`, `projectID`) VALUES (0, 'file-tray-full-outline', 'Filesystem', 'filesystem', 0, 0, '$projectID'), (0, 'storefront-outline', 'Module Store', 'module-store', 0, 1, '$projectID')");
        if ($mysqli) {//$createFolder && $createFile && 
            $token = escape_string($headers['Authorization']);
            $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
            if (mysqli_num_rows($data) == 1) {
                $data = fetch_assoc($data);
                $userID = $data['userID'];
                if (query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1)")) {
                    if (createFileSystem($projectID)) {
                        echo alert('succes', 'The project was created successfully. <a href="/paxar/projects/' . $href . '/">Go to the project</a>');
                    }
                }
            }

        }

    } elseif (isset($_POST['deleteProject']) && isset($_POST['projectID'])) {
        $name = escape_string($_POST['projectName']);
        $id = escape_string($_POST['projectID']);
        //$href = createLink($name);
        //$createFolder = createFolder("/www/paxar/projects/".$href, 0777);
        //$createFile = createFile("/www/paxar/projects/".$href."/index.php", "", 0777);
        $projectID = generateRandomString(20);
        $mysqli = query("DELETE FROM projects WHERE id='$id'");
        if ($mysqli) {//$createFolder && $createFile && 
            echo alert('succes', 'The project was created successfully. <a href="/paxar/projects/' . $href . '/">Go to the project</a>');
        }

    } elseif (isset($_POST['getProjectInfo']) && isset($_POST['project'])) {
        $link = escape_string($_POST['project']);
        $query = query("SELECT * FROM projects WHERE link='$link'");
        if (mysqli_num_rows($query) == 1) {
            $project = fetch_assoc($query);
            $json['icon'] = $project['icon'];
            $json['name'] = $project['name'];
            $json['createdOn'] = $project['createdOn'];
            echo echoJSON($json);
        } else {
            echo "No project found";
        }
    } elseif (isset($_POST['getProjectUsers']) && isset($_POST['project'])) {
        $link = escape_string($_POST['project']);
        $query = query("SELECT * FROM projects WHERE link='$link'");
        if (mysqli_num_rows($query) == 1) {
            $projectID = fetch_assoc($query)['projectID'];
            $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
            if (mysqli_num_rows($users) > 0) {

                $i = 0;

                foreach ($users as $user) {
                    $userID = $user['userID'];
                    $user = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));
                    $json[$i]['id'] = $user['userID'];
                    $json[$i]['name'] = $user['firstname'] . " " . $user['lastname'];
                    $json[$i]['email'] = $user['email'];
                    $i++;
                }
                echo echoJSON($json);
            } else {
                echo echoJSON([]);

            }

        } else {
            echo "No project found";
        }
    }elseif (isset($_POST['getProjectViews']) && isset($_POST['project'])) {
        $link = escape_string($_POST['project']);
        $query = query("SELECT * FROM projects WHERE link='$link'");
        if (mysqli_num_rows($query) == 1) {
            $projectID = fetch_assoc($query)['projectID'];
            $views = query("SELECT * FROM control_center_project_views WHERE projectID='$projectID'");
            if (mysqli_num_rows($views) > 0) {

                $i = 0;

                foreach ($views as $view) {
                    $viewID = $view['pageID'];
                    $view = fetch_assoc(query("SELECT * FROM control_center_pages WHERE id='$viewID'"));
                    $json[$i]['id'] = $view['id'];
                    $json[$i]['name'] = $view['title'];
                    $json[$i]['url'] = $view['url'];
                    $json[$i]['icon'] = $view['icon'];
                    $i++;
                }
                echo echoJSON($json);
            } else {
                echo echoJSON([]);

            }

        } else {
            echo "No project found";
        }

    } elseif (isset($_POST['addUserToProject']) && isset($_POST['project']) && isset($_POST['email'])) {
        $link = escape_string($_POST['project']);
        $email = escape_string($_POST['email']);
        $query = query("SELECT * FROM projects WHERE link='$link'");
        if (mysqli_num_rows($query) == 1) {
            $projectID = fetch_assoc($query)['projectID'];
            $user = query("SELECT * FROM control_center_users WHERE email='$email'");
            if (mysqli_num_rows($user) == 1) {
                $userID = fetch_assoc($user)['userID'];
                $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
                if (mysqli_num_rows($check) == 0) {
                    query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1)");
                }
            }
        } else {
            echo "No project found";
        }
    } elseif (isset($_POST['checkUserPermissions']) && isset($_POST['project'])) {
        // echo 1;
        $link = escape_string($_POST['project']);
        $token = escape_string($headers['Authorization']);
        $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
        if (mysqli_num_rows($data) == 1) {
            //  echo 2;
            $userID = fetch_assoc($data)['userID'];
            $query = query("SELECT * FROM projects WHERE link='$link'");
            if (mysqli_num_rows($query) == 1) {
                $projectID = fetch_assoc($query)['projectID'];
                //  echo 3;
                //  echo "SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'";
                $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
                if (mysqli_num_rows($check) == 1) {
                    $json = ["success" => "authorized"];
                } else {
                    $json = ["error" => "permission"];
                }
                echo echoJson($json);
            } else {
                echo "No project found";
            }
        }




    } else {
        //echo 1;
        $token = escape_string($headers['Authorization']);
        $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
        if (mysqli_num_rows($data) == 1) {
            //echo 2;
            $userID = fetch_assoc($data)['userID'];
            $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
            $i = 0;
            foreach ($projects as $p) {
                $projectID = $p['projectID'];
                $project = query("SELECT * FROM projects WHERE projectID='$projectID'");
                if (mysqli_num_rows($project) == 1) {
                    $project = fetch_assoc($project);
                    $json[$i]["id"] = $project['id'];
                    $json[$i]["icon"] = $project['icon'];
                    $json[$i]["name"] = $project['name'];
                    $json[$i]["link"] = $project['link'];

                }
                $i++;
            }
        }
        echo echoJson($json);

    }
}

?>