<?php
include 'head.php';
if(isset($_POST['newToken']) && isset($_POST['token']) && isset($_POST['userID'])){
    $token = escape_string($_POST['token']);
    $platform = escape_string($_POST['platform']);
    $userID = escape_string($_POST['userID']);
    $search = query("SELECT * FROM control_center_push_notifications_token WHERE token='$token' AND userID='$userID'");
    if(mysqli_num_rows($search) == 0){
        if(query("INSERT INTO control_center_push_notifications_token VALUES (0, '$token', '$platform', '$userID')")){
            query("INSERT INTO control_center_push_notifications (date, time, token, body, title)
            VALUES (CURDATE(), CURTIME(), '$token', 'Now, you will get push messages from Control Center!', 'Welcome!')");
        }
    }else{
        echo 'Device already registred';
    }
}
?>