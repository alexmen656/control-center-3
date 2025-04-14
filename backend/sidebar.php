<?php
$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
//  header('HTTP/1.0 403 Forbidden');
//die('You are not allowed to access this.');     
//}
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';

function randomNumber()
{
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json)
{
    return json_encode($json, JSON_PRETTY_PRINT);
}

$headers = getRequestHeaders();

if ($headers['Authorization']) {
    //$projects = 
    if (isset($_REQUEST['getSideBarByProjectName'])) {

        $projectName = $_REQUEST['getSideBarByProjectName'];
        $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
        $tools = query("SELECT * FROM project_tools WHERE projectID='$projectID' ORDER BY `project_tools`.`order` ASC");
        if (mysqli_num_rows($tools) == 0) {
            $json['tools'] = [];
        } else {
            $i = 0;
            foreach ($tools as $t) {
                $json['tools'][$i]["id"] = $t['id'];
                $json['tools'][$i]["icon"] = $t['icon'];
                $json['tools'][$i]["name"] = $t['name'];
                $json['tools'][$i]["hasConfig"] = $t['hasConfig'];
                $json['tools'][$i]["order"] = $t['order'];
                $i++;
            }
        }

        $components = query("SELECT * FROM project_components WHERE projectID='$projectID' ORDER BY `project_components`.`id` ASC");

        $z = 0;
        foreach ($components as $c) {
            $json['components'][$z]["id"] = $c['id'];
            // $json['tools'][$z]["icon"] = $c['icon'];
            $json['components'][$z]["name"] = $c['name'];
            $json['components'][$z]["type"] = $c['type'];
            $z++;
        }

        // Get services for this project
        $services = query("SELECT * FROM project_services WHERE projectID='$projectID'");
        
        if (mysqli_num_rows($services) == 0) {
            $json['services'] = [];
        } else {
            $s = 0;
            foreach ($services as $service) {
                $json['services'][$s]["id"] = $service['id'];
                $json['services'][$s]["icon"] = $service['icon'];
                $json['services'][$s]["name"] = $service['name'];
                $json['services'][$s]["link"] = $service['link'];
                $s++;
            }
        }

    } else {
        $tools = query("SELECT * FROM tools");
        $i = 0;
        foreach ($tools as $t) {
            $json['tools'][$i]["id"] = $t['id'];
            $json['tools'][$i]["icon"] = $t['icon'];
            $json['tools'][$i]["name"] = $t['name'];
            $i++;
        }


        $token = escape_string($headers['Authorization']);
        $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
        if (mysqli_num_rows($data) == 1) {
            $userID = fetch_assoc($data)['userID'];
            $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
            $i = 0;
            foreach ($projects as $p) {
                $projectID = $p['projectID'];
                $project = query("SELECT * FROM projects WHERE projectID='$projectID'");
                if (mysqli_num_rows($project) == 1) {
                    $project = fetch_assoc($project);
                    $json['projects'][$i]["id"] = $project['id'];
                    $json['projects'][$i]["icon"] = $project['icon'];
                    $json['projects'][$i]["name"] = $project['name'];
                    $json['projects'][$i]["link"] = $project['link'];
                    
                }
                $i++;

            }
        }
    }

    echo echoJson($json);
}
?>