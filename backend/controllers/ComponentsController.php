<?php

class ComponentsController
{
    public function getComponentsByProject(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];

        $components = query("SELECT * FROM project_components WHERE projectID='$projectID' ORDER BY `project_components`.`id` ASC");
        $json = [];
        $i = 0;
        foreach ($components as $c) {
            $json[$i]['name'] = $c['name'];
            $json[$i]['code'] = $c['code'];
            $i++;
        }
        $response->json($json);
    }

    public function getComponent(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $name = escape_string($request->input('name', ''));
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $components = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC");
        $json = [];
        $content = null;
        $c = null;
        foreach ($components as $c) {
            if ($c['type'] == "script" || $c['type'] == "menu") {
                $content = file_get_contents("/www/" . $projectName . "/" . $c['file']);
                if ($c['type'] == "menu") {
                    if (empty($content)) {
                        $content = "{'content': [], 'style': {'nav1': '', 'nav2': '', 'par1': '', 'par2': '', 'logo': ''}}";
                    }
                    $content = json_decode($content);
                }
            } elseif ($c['type'] == "audio" || $c['type'] == "video" || $c['type'] == "image") {
                $content = $projectName . "/" . $c['file'];
            }
            $last_change_by = $c['last_change_by'];
            $userQuery = query("SELECT * FROM control_center_users WHERE userID='$last_change_by'");
            if (mysqli_num_rows($userQuery) == 1) {
                $json['last_change'] = $c['last_change'];
                $uD = fetch_assoc($userQuery);
                $json['last_change_by'] = $uD['firstname'] . " " . $uD['lastname'];
            }
        }
        $json['createdOn'] = $c['createdOn'];
        $json['name'] = $c['name'];
        $json['type'] = $c['type'];
        $json['content'] = $content;
        $response->json($json);
    }

    public function deleteComponent(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $name = escape_string($request->input('name', ''));
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name' ORDER BY `project_components`.`id` ASC");

        if (mysqli_num_rows($component) != 1) {
            echo "error 1";
        } else {
            $component = fetch_assoc($component);
            $delete = query("DELETE FROM project_components WHERE projectID='$projectID' AND code='$name'");
            if ($delete) {
                if (unlink("/www/" . $projectName . "/" . $component['file'])) {
                    echo "component/s successful deleted";
                }
            }
        }
    }

    public function updateHTML(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $name = escape_string($request->input('name', ''));
        $html = $request->input('html', '');

        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $component = query("SELECT * FROM project_components WHERE projectID='$projectID' AND code='$name'");

        if (mysqli_num_rows($component) == 1) {
            $filename = fetch_assoc($component)['file'];
            $location = "/www/" . $projectName . "/" . $filename;
            $oldHTML = file_get_contents($location);

            if ($oldHTML != $html) {
                unlink($location);
                file_put_contents($location, $html, 0777);
                chmod($location, 0777);

                $userID = $request->userID;
                if (query("UPDATE project_components SET last_change=NOW(), last_change_by='$userID' WHERE projectID='$projectID' AND code='$name'")) {
                    echo "Success";
                }
            } else {
                echo "0 change!";
            }
        } else {
            echo "error 1";
        }
    }

    public function newComponent(Request $request, Response $response): void
    {
        $projectName = escape_string($request->input('project', ''));
        $name = escape_string($request->input('name', ''));
        $code = escape_string($request->input('code', ''));
        $type = "script";
        $icon = "code-slash-outline";

        if (isset($_FILES["files"])) {
            $type = "image";
            $icon = "image-outline";
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                $name2 = $_FILES['files']['name'][$key];
                $file_info = pathinfo($name2, PATHINFO_EXTENSION);
                $fileName = $code . "." . $file_info;
                $file_destination = '/www/' . $projectName . '/' . $fileName;
                move_uploaded_file($tmp_name, $file_destination);
            }
        } elseif ($request->input('type', '') == 'menu') {
            $type = "menu";
            $icon = "menu-outline";
            $fileName = str_replace(" ", "-", strtolower($code)) . ".json";
        } else {
            $fileName = str_replace(" ", "-", strtolower($code)) . ".php";
        }

        $userID = $request->userID;
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $insert = query("INSERT INTO project_components VALUES(0, '$fileName', '$type', '$name', '$code', NOW(), NOW(), '$userID', '1234567890', '$projectID')");
        $url = "project/" . $projectName . "/components/" . $code;
        query("INSERT INTO control_center_pages VALUES (0, '$url', 'true', '$icon', '$name', '', 0)");
        if ($type == "menu") {
            $url2 = $url . "/config";
            $name2 = $name . " settings";
            query("INSERT INTO control_center_pages VALUES (0, '$url2', 'true', 'cog-outline', '$name2', '', 0)");
        }
        if ($insert) {
            $content = "";
            if ($type == "menu") {
                $content = '{"content": [], "style":{}}';
            }
            $location = "/www/" . $projectName . "/" . $fileName;
            file_put_contents($location, $content, 0777);
            chmod($location, 0777);
        } else {
            echo "error 1";
        }
    }
}
