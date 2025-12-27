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
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';
include_once 'jwt_helper.php';
include_once 'config.php';

function randomNumber()
{
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json)
{
    return json_encode($json, JSON_PRETTY_PRINT);
}

// JWT prüfen
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

        // Get forms for this project from form_settings table
        $forms = query("SELECT * FROM form_settings WHERE project='$projectName' ORDER BY created_at DESC");

        if (mysqli_num_rows($forms) == 0) {
            $json['forms'] = [];
        } else {
            $f = 0;
            foreach ($forms as $form) {
                $json['forms'][$f]["id"] = $form['form_id'];
                $json['forms'][$f]["name"] = $form['form_name'];
                $json['forms'][$f]["icon"] = "list-outline"; // Default icon for forms
                $json['forms'][$f]["created_at"] = $form['created_at'];
                $f++;
            }
        }

        $webBuilderProjects = query("SELECT wb.id, wb.name, wb.description, wb.created_at, wb.updated_at
                                     FROM control_center_modul_web_builder_projects wb
                                     WHERE wb.project_id = '$projectID' AND wb.user_id = '$userID'
                                     ORDER BY wb.updated_at DESC");

        $json['components'] = [];
        $json['componentSubItems'] = [];

        if (mysqli_num_rows($webBuilderProjects) > 0) {
            $z = 0;
            foreach ($webBuilderProjects as $wbProject) {
                $wbProjectId = $wbProject['id'];
                $wbProjectName = $wbProject['name'];

                $json['components'][$z]["id"] = $wbProjectId;
                $json['components'][$z]["name"] = $wbProjectName;
                $json['components'][$z]["slug"] = 'wb-project-' . $wbProjectId;
                $json['components'][$z]["type"] = 'web-builder';

                $json['componentSubItems'][$wbProjectId] = [];

                $json['componentSubItems'][$wbProjectId][] = [
                    'id' => 'overview-' . $wbProjectId,
                    'name' => 'Overview',
                    'type' => 'overview',
                    'icon' => 'apps',
                    'position' => 0
                ];

                $pages = query("SELECT id, name, slug, title, is_home
                               FROM control_center_modul_web_builder_pages
                               WHERE project_id = '$wbProjectId'
                               ORDER BY is_home DESC, name ASC");

                $pageCounter = 1;
                if (mysqli_num_rows($pages) > 0) {
                    foreach ($pages as $page) {
                        $componentsResult = query("SELECT COUNT(*) as count 
                                                  FROM control_center_modul_web_builder_components 
                                                  WHERE page_id = '{$page['id']}'");
                        $componentsCount = 0;
                        if (mysqli_num_rows($componentsResult) > 0) {
                            $countRow = fetch_assoc($componentsResult);
                            $componentsCount = $countRow['count'];
                        }

                        $json['componentSubItems'][$wbProjectId][] = [
                            'id' => $page['id'],
                            'name' => $page['name'],
                            'slug' => $page['slug'],
                            'type' => 'page',
                            'icon' => $page['is_home'] == 1 ? 'home' : 'document-text',
                            'position' => $pageCounter,
                            'components_count' => $componentsCount
                        ];
                        $pageCounter++;
                    }
                }

                $z++;
            }
        }

        $services = query("SELECT * FROM project_services WHERE projectID='$projectID'");

        if (mysqli_num_rows($services) == 0) {
            $json['services'] = [];
        } else {
            $s = 0;
            foreach ($services as $service) {
                $service_id = $service['id'];
                $service_link = $service['link'];

                // Status ermitteln - 'down' als Standardwert
                $status = 'down';

                // Überprüfen des letzten Logs (Ping) für diesen Service
                $sql = "SELECT * FROM control_center_services_logs 
                        WHERE project_id = '$projectID' 
                        AND service = '$service_link'
                        ORDER BY timestamp DESC 
                        LIMIT 1";

                $last_log = query($sql);

                if (mysqli_num_rows($last_log) > 0) {
                    $log = fetch_assoc($last_log);
                    $last_ping_time = strtotime($log['timestamp']);
                    $current_time = time();

                    // Wenn der letzte Ping weniger als 30 Minuten zurückliegt, betrachten wir den Service als 'up'
                    if (($current_time - $last_ping_time) < (30 * 60)) {
                        $status = 'up';
                    }
                }

                $json['services'][$s]["id"] = $service['id'];
                $json['services'][$s]["icon"] = $service['icon'];
                $json['services'][$s]["name"] = $service['name'];
                $json['services'][$s]["link"] = $service['link'];
                $json['services'][$s]["status"] = $status;
                $s++;
            }
        }

        // Get subscribed APIs for this project
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

        // Get codespaces for this project
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

        // Projekte für den eingeloggten User (aus JWT)
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
