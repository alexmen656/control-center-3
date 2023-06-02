<?php
include 'head.php';

if(isset($_POST['newConfig']) && isset($_POST['token']) && isset($_POST['chatID']) && isset($_POST['project'])){
    $token = escape_string($_POST['token']);
    $chatID = escape_string($_POST['chatID']);
    $project = escape_string($_POST['project']);
    $query = query("INSERT INTO control_center_telegram_bot_configurations VALUES (0, '$token', $chatID, '$project')");
    if($query){
        echo "success";
    }else{
        echo "error 1";
    }
}elseif(isset($_POST['changeConfig']) && isset($_POST['token']) && isset($_POST['chatID']) && isset($_POST['project'])){
    $token = escape_string($_POST['token']);
    $chatID = escape_string($_POST['chatID']);
    echo $chatID;
    $project = escape_string($_POST['project']);
    $query = query("UPDATE control_center_telegram_bot_configurations SET token='$token', chatID=$chatID WHERE project='$project'");
    if($query){
        echo "success";
    }else{
        echo "error 1";
    }
}elseif(isset($_POST['getConfig']) && isset($_POST['project'])){
    $project = escape_string($_POST['project']);
    $query = query("SELECT * FROM control_center_telegram_bot_configurations WHERE project='$project'");
    if(mysqli_num_rows($query) == 1){
        $config = fetch_assoc($query);
        $json['token'] = $config['token'];
        $json['chatID'] = $config['chatID'];
        echo echoJson($json);
    }else{
        echo "Error 1";
    }
}
?>