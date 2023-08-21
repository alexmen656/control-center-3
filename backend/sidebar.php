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

//$projects = 
if ($_REQUEST['getSideBarByProjectName']) {

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






} else {
    $tools = query("SELECT * FROM tools");
    $i = 0;
    foreach ($tools as $t) {
        $json['tools'][$i]["id"] = $t['id'];
        $json['tools'][$i]["icon"] = $t['icon'];
        $json['tools'][$i]["name"] = $t['name'];
        $i++;
    }

    $projects = query("SELECT * FROM projects");
    $i = 0;
    foreach ($projects as $p) {
        $json['projects'][$i]["id"] = $p['id'];
        $json['projects'][$i]["icon"] = $p['icon'];
        $json['projects'][$i]["name"] = $p['name'];
        $i++;
    }
}

echo echoJson($json);
?>