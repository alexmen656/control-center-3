<?php
include 'head.php';

if(isset($_POST['newTool']) && isset($_POST['projectName']) && isset($_POST['toolName'])){
    $projectName = escape_string($_POST['projectName']);
    $toolName = escape_string($_POST['toolName']);
    $toolIcon = escape_string($_POST['toolIcon']);
    echo $tool;
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");
    if(mysqli_num_rows($projectID) == 1){
        $projectID = fetch_assoc($projectID)['projectID'];
        $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'"))+1;
        $query = query("INSERT INTO project_tools VALUES (0,'$toolIcon','$toolName',0,'','$projectID')");
        if($query){
            $url = "project/".str_replace(" ", "-", strtolower($projectName))."/".str_replace(" ", "-", strtolower($toolName));
            query("INSERT INTO control_center_pages VALUES (0,'$url','$toolIcon','$toolName', '', 0)");
            echo "success";
        }else{
            echo "error 2";
        }
    }else{
     echo "error 1";
    }
}elseif(isset($_POST['deleteTool']) && isset($_POST['toolID'])){
    $toolID = escape_string($_POST['toolID']);
    echo $toolID;
    $query = query("DELETE FROM project_tools WHERE id=$toolID");
    if($query){
        echo "success";
    }else{
        echo "error 2";
    }
}

