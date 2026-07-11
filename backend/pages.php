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











require_once 'helpers/jwt.php';
require_once 'config.php';
include './helpers/db_connection.php';
include 'functions.php';

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

$i = 0;

$pages = query("SELECT * FROM control_center_pages");
foreach ($pages as $p) {
    $pageID = $p['pageID'];
    $data = query("SELECT * FROM control_center_page_data WHERE pageID='$pageID'");
    foreach ($data as $d) {
        $replaces[$d['key']] = $d['value'];
    }
    $json[$i]['id'] = $p['id'];
    $json[$i]['url'] = $p['url'];
    $json[$i]['showTitle'] = $p['showTitle'];
    $json[$i]['icon'] = $p['icon'];
    $json[$i]['title'] = $p['title'];
    $json[$i]['pageID'] = $p['pageID'];
    $i++;
}


$forms = query("SELECT * FROM table_settings ORDER BY project, table_name");
foreach ($forms as $form) {
    $json[$i]['id'] = 'table_' . $form['table_id'];
    $json[$i]['url'] = 'project/' . $form['project'] . '/tables/' . $form['table_name'];
    $json[$i]['showTitle'] = true;
    $json[$i]['icon'] = 'list-outline';
    $json[$i]['title'] = $form['table_name'];
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'table_' . $form['table_id'];
    $i++;

    $json[$i]['id'] = 'table_' . $form['table_id'] . '_edit';
    $json[$i]['url'] = 'project/' . $form['project'] . '/tables/' . $form['table_name'] . '/edit';
    $json[$i]['showTitle'] = true;
    $json[$i]['icon'] = 'list-outline';
    $json[$i]['title'] = $form['table_name'];
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'table_' . $form['table_id'] . '_edit';
    $i++;
}

$projects = query("SELECT projectID, link, name FROM projects");
foreach ($projects as $project) {
    $projectID = $project['projectID'];
    $projectLink = $project['link'];
    $projectName = $project['name'];

    $json[$i]['id'] = 'manage_codespaces_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/manage/codespaces';
    $json[$i]['showTitle'] = false; 
    $json[$i]['icon'] = 'code-outline';
    $json[$i]['title'] = 'Manage Codespaces - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'manage_codespaces_' . $projectID;
    $i++;

    $json[$i]['id'] = 'new_codespace_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/new/codespace';
    $json[$i]['showTitle'] = false; 
    $json[$i]['icon'] = 'add-circle-outline';
    $json[$i]['title'] = 'New Codespace - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'new_codespace_' . $projectID;
    $i++;

    
    $json[$i]['id'] = 'manage_tables_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/manage/tables';
    $json[$i]['showTitle'] = false; 
    $json[$i]['icon'] = 'document-outline';
    $json[$i]['title'] = 'Manage Tables - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'manage_tables_' . $projectID;
    $i++;

    
    $json[$i]['id'] = 'new_table_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/new/table';
    $json[$i]['showTitle'] = false; 
    $json[$i]['icon'] = 'document-outline';
    $json[$i]['title'] = 'New Table - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'new_table_' . $projectID;
    $i++;

    
    $json[$i]['id'] = 'manage_apis_' . $projectID;
    $json[$i]['url'] = 'project/' . $projectLink . '/manage/apis';
    $json[$i]['showTitle'] = false; 
    $json[$i]['icon'] = 'albums-outline';
    $json[$i]['title'] = 'Manage APIs - ' . $projectName;
    $json[$i]['html'] = '';
    $json[$i]['pageID'] = 'manage_apis_' . $projectID;
    $i++;

    
    $apis = query("
        SELECT ca.id, ca.name, ca.slug, ca.description, ca.icon, ca.category, pas.id as subscription_id
        FROM project_api_subscriptions pas
        JOIN cms_apis ca ON pas.api_id = ca.id
        WHERE pas.projectID='$projectID' AND pas.is_enabled=1
        ORDER BY ca.category, ca.name ASC
    ");

    foreach ($apis as $api) {
        $apiId = $api['id'];
        $apiName = $api['name'];
        $apiSlug = $api['slug'];
        $apiDescription = $api['description'];
        $apiIcon = $api['icon'] ?: 'cloud-outline';
        $apiCategory = $api['category'];
        $subscriptionId = $api['subscription_id'];

        
        $json[$i]['id'] = 'api_dashboard_' . $subscriptionId;
        $json[$i]['url'] = 'project/' . $projectLink . '/apis/' . $apiSlug;
        $json[$i]['showTitle'] = false;
        $json[$i]['icon'] = $apiIcon;
        $json[$i]['title'] = $apiName . ' - Dashboard';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'api_dashboard_' . $subscriptionId;
        $i++;

        
        
    }

    
    $codespaces = query("SELECT id, name, slug, description, language, template, status FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");

    foreach ($codespaces as $codespace) {
        $codespaceId = $codespace['id'];
        $codespaceName = $codespace['name'];
        $codespaceSlug = $codespace['slug'];
        $codespaceDescription = $codespace['description'];
        $codespaceLanguage = $codespace['language'];
        $codespaceTemplate = $codespace['template'];
        $codespaceStatus = $codespace['status'];

        
        $json[$i]['id'] = 'codespace_monaco_' . $codespaceId;
        $json[$i]['url'] = 'project/' . $projectLink . '/codespace/' . $codespaceSlug;
        $json[$i]['showTitle'] = false;
        $json[$i]['icon'] = 'code-working-outline';
        $json[$i]['title'] = $codespaceName . ' - Monaco Editor';
        $json[$i]['html'] = '';
        $json[$i]['pageID'] = 'codespace_monaco_' . $codespaceId;
        $i++;

        
        
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
