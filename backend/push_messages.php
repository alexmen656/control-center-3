<?php
include 'head.php';
$notifications = query("SELECT * FROM control_center_push_notifications
WHERE date = CURDATE() AND time >= DATE_SUB(CURTIME(), INTERVAL 5 MINUTE);

");
if(mysqli_num_rows($notifications) > 0){
    $i=0;
    foreach($notifications as $n){
        $json['notifications'][$i]['id'] = $n['id'];
        $json['notifications'][$i]['token'] = $n['token'];
        $json['notifications'][$i]['title'] = $n['title'];
        $json['notifications'][$i]['text'] = $n['body'];
        $i++;
    }
}else{
    $json['notifications'] = [];  
}
echo echoJSON($json);
?>