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

function randomNumber(){
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json){
    return json_encode($json, JSON_PRETTY_PRINT);
}

function useTemplate2($temp,$data=[]){
    $co=[];$zaco=[];
    foreach ($data as $key=>$val):
      $co[]='{{'.$key.'}}';
      $zaco[]=$val;
    endforeach;
    return str_replace($co,$zaco,$temp);
  }

$i=0;
// Get regular pages
$pages = query("SELECT * FROM control_center_pages");
foreach($pages as $p){
    $pageID = $p['pageID'];
    $data = query("SELECT * FROM control_center_page_data WHERE pageID='$pageID'");
    foreach($data as $d){
        $replaces[$d['key']] = $d['value'];
    }
    $json[$i]['id']=$p['id'];
    $json[$i]['url']=$p['url'];
    $json[$i]['showTitle']=$p['showTitle'];
    $json[$i]['icon']=$p['icon'];
    $json[$i]['title']=$p['title'];
    $json[$i]['html']= useTemplate2($p['html'], $replaces);
    $json[$i]['pageID']=$p['pageID'];
    $i++;
}

// Get all webbuilder projects
$webbuilderProjects = query("SELECT id, name, description FROM control_center_web_builder_projects");
foreach($webbuilderProjects as $project) {
    $projectId = $project['id'];
    $projectName = $project['name'];
    $projectSlug = strtolower(str_replace(" ", "-", $projectName));
    
    // Get all pages for this project
    $projectPages = query("SELECT id, name, slug, title, meta_description, is_home FROM control_center_web_builder_pages WHERE project_id='$projectId'");
    
    foreach($projectPages as $page) {
        $pageId = $page['id'];
        $pageName = $page['name'];
        $pageSlug = $page['slug'];
        
        // Get all components for this page
        $components = query("SELECT id, component_id, html_code, position, original_template_id FROM control_center_web_builder_components WHERE page_id='$pageId' ORDER BY position ASC");
        
        // Combine all HTML code from components for the page view
     /*   $htmlContent = "";
        foreach($components as $component) {
            $htmlContent .= $component['html_code'] . "\n";
        }*/
        
        // Add the page to the JSON response
        $json[$i]['id'] = "web_" . $projectId . "_" . $pageId;
        $json[$i]['url'] = "project/" . $projectSlug . "/page/" . $pageSlug;
        $json[$i]['showTitle'] = true;
        $json[$i]['icon'] = $page['is_home'] ? "home-outline" : "document-outline";
        $json[$i]['title'] = $page['title'] ?: $pageName;
        $json[$i]['html'] = "";
        $json[$i]['pageID'] = "web_" . $projectId . "_" . $pageId;
     /*   $json[$i]['meta_description'] = $page['meta_description'];
        $json[$i]['is_webbuilder'] = true;
        $json[$i]['project_id'] = $projectId;
        $json[$i]['page_id'] = $pageId;*/
        $i++;
        
        // Add individual component routes
        foreach($components as $component) {
            $componentId = $component['component_id'];
            // Create a meaningful name for the component
            $componentName = "Component " . ($component['position'] + 1);
            
            // Check if this is a template-based component and get the template name
            if (!empty($component['original_template_id'])) {
                $templateQuery = query("SELECT title FROM control_center_web_builder_templates WHERE id=" . $component['original_template_id']);
                if (mysqli_num_rows($templateQuery) > 0) {
                    $template = fetch_assoc($templateQuery);
                    $componentName = $template['title'];
                }
            }
            
            // Add component as a route
            $json[$i]['id'] = "component_" . $component['id'];
            $json[$i]['url'] = "project/" . $projectSlug . "/page/" . $pageSlug . "/" .str_replace(["ö", "ü", "ä", " "], ["oe", "ue", "ae", "-"], strtolower($componentName));// $componentId;
            $json[$i]['showTitle'] = true;
            $json[$i]['icon'] = "cube-outline";
            $json[$i]['title'] = $componentName;
            $json[$i]['html'] = "";
            $json[$i]['pageID'] = "component_" . $component['id'];
            /*$json[$i]['is_webbuilder'] = true;
            $json[$i]['is_component'] = true;
            $json[$i]['project_id'] = $projectId;
            $json[$i]['page_id'] = $pageId;
            $json[$i]['component_id'] = $componentId;
            $json[$i]['position'] = $component['position'];*/
            $i++;
        }
    }
}

$tables = [];
$result = query("SHOW TABLES");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
}

foreach ($tables as $table) {
    $json[$i]['id'] = 'table_' . $table;
    $json[$i]['url'] = 'databases/table/' . $table;
    $json[$i]['showTitle'] = true;
    $json[$i]['icon'] = 'grid-outline';
    $json[$i]['title'] = $table;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'table_' . $table;
    $i++;
}

echo echoJson($json);
?>