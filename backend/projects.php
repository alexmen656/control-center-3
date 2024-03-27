<?php
include "head.php";
if(isset($_POST['createProject']) && isset($_POST['projectName'])){
   $name = escape_string($_POST['projectName']);
   $icon = escape_string($_POST['projectIcon']);
   $href = str_replace("\\", "", createLink($name));
   echo $href;
    //$createFolder = createFolder("/www/paxar/projects/".$href, 0777);
    //$createFile = createFile("/www/paxar/projects/".$href."/index.php", "", 0777);
    $projectID = generateRandomString(20);
    $mysqli = query("INSERT INTO projects VALUES (0, '$icon', '$name', '$href', CURDATE(), '$projectID')");
    $url1 = "project/".$href;
    $url2 = "project/".$href."/new-tool";
    $url3 = "project/".$href."/manage/tools";
    $url4 = "project/".$href."/manage/components";
    $url5 = "project/".$href."/new/component";
    $url6 = "project/".$href."/info";
    $url7 = "project/".$href."/components/main";
    $url8 = "project/".$href."/module-store";
    $url9 = "project/".$href."/package-manager";


    mkdir("/www/".$href, 0777);
    echo $href;
    chmod("/www/".$href, 0777);
    file_put_contents("/www/".$href."/index.php", str_replace( array('[{[pLink]}]', '[{[pName]}]', '[{[pID]}]'), array($href, $name, $projectID),file_get_contents("templates/website/index.php")),0777);
    chmod("/www/".$href."/index.php", 0777);
    file_put_contents("/www/".$href."/main.php", "//Put here main content of your site", 0777);
    chmod("/www/".$href."/main.php", 0777);
    $mainComponent = query("INSERT INTO project_components VALUES (0, 'main.php', 'script', 'Main', 'main', NOW(), NOW(), 'System', '1234567890', '$projectID')");
    $pages = query("INSERT INTO control_center_pages VALUES (0, '$url1', 'true', '', 'Project Dashboard', '', 0), (0, '$url2', 'true', '', 'Create new tool', '', 0), (0, '$url3', 'true', '', 'Manage Tools', '', 0), (0, '$url4', 'true', '', 'Manage Components', '', 0), (0, '$url5', 'true', '', 'Create New Component', '', 0), (0, '$url6', 'true', '', 'Project Info', '', 0), (0, '$url7', 'true', '', 'Main', '', 0), (0, '$url8', 'false', '', 'Module Store', '', 0), (0, '$url9', 'true', '', 'Package Manager', '', 0)");
    query("INSERT INTO project_tools (`id`, `icon`, `name`, `link`, `hasConfig`, `order`, `projectID`) VALUES (0, 'storefront-outline', 'Module Store', 'module-store', 0, 0, '$projectID')");
    if($mysqli){//$createFolder && $createFile && 
        echo alert('succes', 'The project was created successfully. <a href="/paxar/projects/'.$href.'/">Go to the project</a>');
    }
   
}elseif(isset($_POST['deleteProject']) && isset($_POST['projectID'])){
    $name = escape_string($_POST['projectName']);
    $id = escape_string($_POST['projectID']);
    //$href = createLink($name);
     //$createFolder = createFolder("/www/paxar/projects/".$href, 0777);
     //$createFile = createFile("/www/paxar/projects/".$href."/index.php", "", 0777);
     $projectID = generateRandomString(20);
     $mysqli = query("DELETE FROM projects WHERE id='$id'");
     if($mysqli){//$createFolder && $createFile && 
         echo alert('succes', 'The project was created successfully. <a href="/paxar/projects/'.$href.'/">Go to the project</a>');
     }
    
 }elseif(isset($_POST['getProjectInfo']) && isset($_POST['project'])){
    $link = escape_string($_POST['project']);
    $query = query("SELECT * FROM projects WHERE link='$link'");
    if(mysqli_num_rows($query) == 1){
        $project = fetch_assoc($query);
        $json['icon'] = $project['icon'];
        $json['name'] = $project['name'];
        $json['createdOn'] = $project['createdOn'];
        echo echoJSON($json);
    }else{
        echo "No project found";
    }
 }else{
    $projects = query("SELECT * FROM projects"); 
    $i=0;
    foreach($projects as $p){
        $json[$i]["id"] = $p['id'];
        $json[$i]["icon"] = $p['icon'];
        $json[$i]["name"] = $p['name'];
        $json[$i]["link"] = $p['link'];
        $i++;
    }
    
    echo echoJson($json);
}
?>