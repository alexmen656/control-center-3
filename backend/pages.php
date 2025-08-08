<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
  //  header('HTTP/1.0 403 Forbidden');
    //die('You are not allowed to access this.');     
//}

include_once 'jwt_helper.php';
include_once 'config.php';
include '/www/paxar/components/php_head.php';

// JWT prüfen
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'No valid token']);
        exit;
    }
} else {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'No valid token']);
    exit;
}

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

$projects = query("SELECT projectID, link, name FROM projects");
foreach($projects as $project) {
    $projectID = $project['projectID'];
    $projectLink = $project['link'];
    $projectName = $project['name'];
    
    $json[$i]['id'] = 'manage_codespaces_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/manage/codespaces';
    $json[$i]['showTitle'] = true;
    $json[$i]['icon'] = 'code-outline';
    $json[$i]['title'] = 'Manage Codespaces - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'manage_codespaces_' . $projectID;
    $i++;
    
    $json[$i]['id'] = 'new_codespace_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/new/codespace';
    $json[$i]['showTitle'] = true;
    $json[$i]['icon'] = 'add-circle-outline';
    $json[$i]['title'] = 'New Codespace - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'new_codespace_' . $projectID;
    $i++;
    
    $codespaces = query("SELECT id, name, slug, description, language, template, status FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");
    
    foreach($codespaces as $codespace) {
        $codespaceId = $codespace['id'];
        $codespaceName = $codespace['name'];
        $codespaceSlug = $codespace['slug'];
        $codespaceDescription = $codespace['description'];
        $codespaceLanguage = $codespace['language'];
        $codespaceTemplate = $codespace['template'];
        $codespaceStatus = $codespace['status'];
        
        // Add monaco editor page for each codespace
        $json[$i]['id'] = 'codespace_monaco_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/monaco/' . $codespaceSlug;
        $json[$i]['showTitle'] = false;
        $json[$i]['icon'] = 'code-working-outline';
        $json[$i]['title'] = $codespaceName . ' - Monaco Editor';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_monaco_' . $codespaceId;
        $i++;
        
        // Add codespace dashboard/overview page
        $json[$i]['id'] = 'codespace_dashboard_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/codespace/' . $codespaceSlug;
        $json[$i]['showTitle'] = false;
        $json[$i]['icon'] = 'analytics-outline';
        $json[$i]['title'] = $codespaceName . ' - Dashboard';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_dashboard_' . $codespaceId;
        $i++;
        
        // Add codespace settings page
        /*$json[$i]['id'] = 'codespace_settings_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/codespace/' . $codespaceSlug . '/settings';
        $json[$i]['showTitle'] = true;
        $json[$i]['icon'] = 'settings-outline';
        $json[$i]['title'] = $codespaceName . ' - Settings';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_settings_' . $codespaceId;
        $i++;
        
        // Add codespace terminal page
        $json[$i]['id'] = 'codespace_terminal_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/codespace/' . $codespaceSlug . '/terminal';
        $json[$i]['showTitle'] = true;
        $json[$i]['icon'] = 'terminal-outline';
        $json[$i]['title'] = $codespaceName . ' - Terminal';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_terminal_' . $codespaceId;
        $i++;
        
        // Add codespace file browser page
        $json[$i]['id'] = 'codespace_files_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/codespace/' . $codespaceSlug . '/files';
        $json[$i]['showTitle'] = true;
        $json[$i]['icon'] = 'folder-outline';
        $json[$i]['title'] = $codespaceName . ' - File Browser';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_files_' . $codespaceId;
        $i++;*/
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