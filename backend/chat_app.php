<?php
include 'head.php';
if (isset($_POST['getAPIKey']) && isset($_POST['project']) && isset($_POST['tool'])) {
    $projectName = escape_string($_POST['project']);
    $tool = escape_string($_POST['tool']);
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");

    if (mysqli_num_rows($projectID) == 1) {
        $projectID = fetch_assoc($projectID)['projectID'];
        $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$tool'"))['id'];
        //echo "toolID: " . $toolID;
        $query = query("SELECT * FROM control_center_chat_app_config WHERE toolID='$toolID'");
        if (mysqli_num_rows($query) == 1) {
            //print_r($query);
            $config = fetch_assoc($query)['config'];
            //print($config);
            echo json_encode(json_decode($config, true), JSON_PRETTY_PRINT);
        } else {
            echo echoJson([]);
        }
    }
}
?>