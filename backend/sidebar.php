<?php









session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
include './helpers/db_connection.php';
include 'functions.php';
require_once 'helpers/jwt.php';
require_once 'config.php';

$headers = getRequestHeaders();
if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload || empty($payload['sub'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'No valid token']);
        exit;
    }
    $userID = intval($payload['sub']);

    if (isset($_REQUEST['getSideBarByProjectName'])) {
        $projectName = $_REQUEST['getSideBarByProjectName'];
        $projectData = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"));
        $projectID = $projectData['projectID'];

        $sections = query("SELECT * FROM project_sidebar_sections WHERE projectID='$projectID' ORDER BY order_index ASC");
        $json['sections'] = [];

        if (mysqli_num_rows($sections) > 0) {
            $sectionIndex = 0;
            foreach ($sections as $section) {
                $sectionId = $section['id'];
                $json['sections'][$sectionIndex] = [
                    'id' => $sectionId,
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'icon' => $section['icon'],
                    'order_index' => $section['order_index'],
                    'is_default' => (bool) $section['is_default'],
                    'is_collapsible' => (bool) $section['is_collapsible'],
                    'show_add_button' => (bool) $section['show_add_button'],
                    'add_button_route' => $section['add_button_route'],
                    'info_route' => $section['info_route'],
                    'manage_route' => $section['manage_route'],
                    'items' => [] 
                ];

                
                $sectionTools = query("SELECT *, 'tool' as item_type FROM project_tools WHERE projectID='$projectID' AND section_id='$sectionId' ORDER BY `order` ASC");
                $items = [];

                if (mysqli_num_rows($sectionTools) > 0) {
                    foreach ($sectionTools as $t) {
                        $items[] = [
                            'id' => $t['id'],
                            'item_type' => 'tool',
                            'icon' => $t['icon'],
                            'name' => $t['name'],
                            'link' => $t['link'],
                            'hasConfig' => $t['hasConfig'],
                            'order' => (int) $t['order'],
                            'section_id' => $sectionId
                        ];
                    }
                }

                
                $sectionForms = query("SELECT * FROM table_settings WHERE project='$projectName' AND section_id='$sectionId' ORDER BY order_index ASC");
                if (mysqli_num_rows($sectionForms) > 0) {
                    foreach ($sectionForms as $form) {
                        $items[] = [
                            'id' => 'table_' . $form['table_id'],
                            'table_id' => $form['table_id'],
                            'item_type' => 'table',
                            'icon' => $form['icon'] ?? 'list-outline',
                            'name' => $form['table_name'],
                            'link' => 'forms/' . $form['table_name'],
                            'hasConfig' => 0,
                            'order' => (int) ($form['order_index'] ?? 999),
                            'section_id' => $sectionId
                        ];
                    }
                }

                
                usort($items, function ($a, $b) {
                    return $a['order'] - $b['order'];
                });

                $json['sections'][$sectionIndex]['items'] = $items;
                
                $json['sections'][$sectionIndex]['tools'] = array_filter($items, fn($item) => $item['item_type'] === 'tool');
                $json['sections'][$sectionIndex]['tools'] = array_values($json['sections'][$sectionIndex]['tools']);

                $sectionIndex++;
            }
        }

        $tools = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND (section_id IS NULL OR section_id = 0) ORDER BY `project_tools`.`order` ASC");
        if (mysqli_num_rows($tools) == 0) {
            $json['tools'] = [];
        } else {
            $i = 0;
            foreach ($tools as $t) {
                $json['tools'][$i]["id"] = $t['id'];
                $json['tools'][$i]["icon"] = $t['icon'];
                $json['tools'][$i]["name"] = $t['name'];
                $json['tools'][$i]["link"] = $t['link'];
                $json['tools'][$i]["hasConfig"] = $t['hasConfig'];
                $json['tools'][$i]["order"] = $t['order'];
                $i++;
            }
        }

        $forms = query("SELECT * FROM table_settings WHERE project='$projectName' ORDER BY order_index ASC, created_at DESC");

        if (mysqli_num_rows($forms) == 0) {
            $json['forms'] = [];
        } else {
            $f = 0;
            foreach ($forms as $form) {
                $json['forms'][$f]["table_id"] = $form['table_id'];
                $json['forms'][$f]["table_name"] = $form['table_name'];
                $json['forms'][$f]["name"] = $form['table_name'];
                $json['forms'][$f]["icon"] = $form['icon'] ?? "list-outline";
                $sectionId = $form['section_id'];
                $json['forms'][$f]["section_id"] = ($sectionId === null || $sectionId === '' || $sectionId === '0' || $sectionId === 0) ? null : (int) $sectionId;
                $json['forms'][$f]["order_index"] = $form['order_index'] ?? 0;
                $json['forms'][$f]["created_at"] = $form['created_at'];
                $f++;
            }
        }

        $subscribed_apis = query("
            SELECT pas.*, ca.name, ca.slug, ca.icon, ca.category
            FROM project_api_subscriptions pas
            JOIN cms_apis ca ON pas.api_id = ca.id
            WHERE pas.projectID='$projectID' AND pas.is_enabled=1
            ORDER BY ca.category, ca.name ASC
        ");

        if (mysqli_num_rows($subscribed_apis) == 0) {
            $json['apis'] = [];
        } else {
            $a = 0;
            foreach ($subscribed_apis as $api) {
                $json['apis'][$a]["id"] = $api['api_id'];
                $json['apis'][$a]["subscription_id"] = $api['id'];
                $json['apis'][$a]["icon"] = $api['icon'];
                $json['apis'][$a]["name"] = $api['name'];
                $json['apis'][$a]["slug"] = $api['slug'];
                $json['apis'][$a]["category"] = $api['category'];
                $json['apis'][$a]["status"] = $api['is_enabled'] ? 'active' : 'inactive';
                $json['apis'][$a]["usage_count"] = $api['usage_count'];
                $a++;
            }
        }

        $codespaces = query("SELECT * FROM project_codespaces WHERE project_id='$projectID' ORDER BY order_index ASC");

        if (mysqli_num_rows($codespaces) == 0) {
            $json['codespaces'] = [];
        } else {
            $c = 0;
            foreach ($codespaces as $codespace) {
                $json['codespaces'][$c]["id"] = $codespace['id'];
                $json['codespaces'][$c]["name"] = $codespace['name'];
                $json['codespaces'][$c]["slug"] = $codespace['slug'];
                $json['codespaces'][$c]["description"] = $codespace['description'];
                $json['codespaces'][$c]["icon"] = $codespace['icon'];
                $json['codespaces'][$c]["language"] = $codespace['language'];
                $json['codespaces'][$c]["template"] = $codespace['template'];
                $json['codespaces'][$c]["status"] = $codespace['status'];
                $json['codespaces'][$c]["order_index"] = $codespace['order_index'];
                $c++;
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

        
        $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
        $i = 0;
        foreach ($projects as $p) {
            $projectID = $p['projectID'];
            $project = query("SELECT * FROM projects WHERE projectID='$projectID'");
            if (mysqli_num_rows($project) == 1) {
                $project = fetch_assoc($project);
                if ($project['hidden'] != true) {
                    $json['projects'][$i]["id"] = $project['id'];
                    $json['projects'][$i]["icon"] = $project['icon'];
                    $json['projects'][$i]["name"] = $project['name'];
                    $json['projects'][$i]["link"] = $project['link'];
                    $i++;
                }
            }
        }
    }

    echo echoJson($json);
}
