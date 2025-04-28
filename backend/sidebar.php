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

        $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$projectName'");
        if (mysqli_num_rows($projectQuery) == 1) {
            $projectData = fetch_assoc($projectQuery);
            $projectId = $projectData['id'];
        $components = query("SELECT * FROM control_center_web_builder_pages WHERE project_id='$projectId' ORDER BY `control_center_web_builder_pages`.`id` ASC");//$projectID

        $z = 0;
        foreach ($components as $c) {
            $json['components'][$z]["id"] = $c['id'];
            // $json['tools'][$z]["icon"] = $c['icon'];
            $json['components'][$z]["name"] = $c['name'];
            $json['components'][$z]["slug"] = $c['slug'];
         //  $json['components'][$z]["type"] = $c['type'];
             $json['components'][$z]["type"] = 'script';

             //echo $c['id'];
             $comps = query("SELECT * FROM control_center_web_builder_components WHERE page_id='" . $c['id'] . "' ORDER BY `control_center_web_builder_components`.`position` ASC");
             $counter = 0;
             foreach ($comps as $comp) {
                if($comp['original_template_id'] !== NULL){
                    $comp2 = $comp;
                    $comp = fetch_assoc(query("SELECT * FROM control_center_web_builder_templates WHERE id='" . $comp['original_template_id'] . "'"));
                    $comp['id'] = $comp2['id'];
                    $comp['position'] = $comp2['position'];
                }else{
                    $comp['title'] = "Header";
                }

                $comp['icon'] = "home";
                $comp['type'] = "script";
                $componentId = $c['id'];
                $json['componentSubItems'][$componentId][$counter] = [
                    'id' => $comp['id'],
                    'name' => $comp['title'],
                    'type' => $comp['type'],
                    'icon' => $comp['icon'],
                    'position' => $comp['position']
                ];
                $counter++;
            }
            // Add 3 test subcomponents for each component
           /* $componentId = $c['id'];
            $json['componentSubItems'][$componentId] = [
                [
                    'id' => $componentId . '-sub1',
                    'name' => 'Sub 1 for ' . $c['name'],
                    'type' => 'script'
                ],
                [
                    'id' => $componentId . '-sub2',
                    'name' => 'Sub 2 for ' . $c['name'],
                    'type' => 'image'
                ],
                [
                    'id' => $componentId . '-sub3',
                    'name' => 'Sub 3 for ' . $c['name'],
                    'type' => 'menu'
                ]
            ];*/
            
            $z++;
        }
    }
        // Get services for this project
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