<?php
include 'head.php';

if (isset($_POST['newTool']) && isset($_POST['projectName']) && isset($_POST['toolName'])) {
    $projectName = escape_string($_POST['projectName']);
    $toolName = escape_string($_POST['toolName']);
    $toolIcon = escape_string($_POST['toolIcon']);
    $link = strtolower(str_replace(['Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', ' '], ['a', 'a', 'u', 'u', 'o', 'o', '-'], $toolName));
    echo $tool;
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");
    if (mysqli_num_rows($projectID) == 1) {
        $projectID = fetch_assoc($projectID)['projectID'];
        $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;
        $query = query("INSERT INTO project_tools VALUES (0,'$toolIcon','$toolName', '$link',0,'','$projectID')");
        if ($query) {
            $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($projectName)) . "/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($toolName));
            $config_url = $url."/config";
            $config_name = $toolName." Config";
            query("INSERT INTO control_center_pages VALUES (0,'$url', 'true','$toolIcon','$toolName', '', 0)");
            query("INSERT INTO control_center_pages VALUES (0,'$config_url', 'true','cog-outline','$config_name', '', 0)");
            echo "success";
        } else {
            echo "error 2";
        }
    } else {
        echo "error 1";
    }
} elseif (isset($_POST['newToolConfig']) && isset($_POST['config']) && isset($_POST['project']) && isset($_POST['tool'])) {
    $json = $_POST['config']; //json_decode
    $projectName = escape_string($_POST['project']);
    $tool = escape_string($_POST['tool']);
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

    if (mysqli_num_rows($projectID) == 1) {
        $projectID = fetch_assoc($projectID)['projectID'];
        $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$tool'"))['id'];
        if (mysqli_num_rows(query("SELECT * FROM project_tools_config WHERE tool_id='$toolID'")) == 0) {
            $query = query("INSERT INTO project_tools_config (config_json, tool_id) VALUES ('$json','$toolID')");
        } else {
            $query = query("UPDATE project_tools_config SET config_json='$json' WHERE tool_id='$toolID'");
        }

        if ($query) {
            echo "success";
        } else {
            echo "error 2";
        }
    } else {
        echo "error 1";
    }
} elseif (isset($_POST['getToolConfig']) && isset($_POST['tool']) && isset($_POST['project'])) {
    $projectName = escape_string($_POST['project']);
    $tool = escape_string($_POST['tool']);
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

    if (mysqli_num_rows($projectID) == 1) {
        $projectID = fetch_assoc($projectID)['projectID'];
        $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$tool'"))['id'];
        $config = fetch_assoc(query("SELECT * FROM project_tools_config WHERE tool_id='$toolID'"))['config_json'];
        echo echoJson(json_decode($config));
    }
} elseif (isset($_POST['deleteTool']) && isset($_POST['toolID'])) {
    $toolID = escape_string($_POST['toolID']);
    echo $toolID;
    $query = query("DELETE FROM project_tools WHERE id=$toolID");
    if ($query) {
        echo "success";
    } else {
        echo "error 2";
    }
}