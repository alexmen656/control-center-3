<?php
include "head.php";

if(isset($_POST['getPackages']) && isset($_POST['project'])){
    $projectName = escape_string($_POST['project']);
    $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
    if($projectID){
        $packages = query("SELECT * FROM project_packages WHERE projectID='$projectID'");
        $i=0;
        $json = [];
        foreach($packages as $p){
            $json[$i] = json_decode($p['package'], true);
            $i++;
        }
        echo echoJSON($json);
            }
}elseif(isset($_POST['packages']) && isset($_POST['project'])){
    $projectName = escape_string($_POST['project']);
    $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"))['projectID'];
    if($projectID){
$packages = $_POST['packages'];//escape_string
foreach($packages as $p){
    $p = json_encode($p);
    query("INSERT INTO project_packages VALUES (0, '$p', '$projectID')");
}
    }

}else{
$packages = query("SELECT * FROM packages");
$i = 0;
foreach($packages as $p){
    $json[$i]['name'] = $p['name'];
    $json[$i]['type'] = $p['type'];
    $json[$i]['attributes'] = json_decode($p['attributes'], true);
$i++;
}
echo echoJSON($json);

}



