<?php
include '/www/control-center/head.php';
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

echo "hahah";
print_r($_POST);
if (isset($_POST['newChat']) && isset($_POST['user'])) {
    echo "jesus";
    $user = escape_string($_POST['user']);
    $u_name = $user['name'];
    $u_profile_img = $user['profile_img'];
    echo $u_name." ".$u_profile_img;
    query("INSERT INTO control_center_chat_app_api_users VALUES (0,'$u_name','$u_profile_img')");

    // Get the ID of the registered user
    $result = query("SELECT id FROM control_center_chat_app_api_users WHERE name='$u_name' AND profile_img='$u_profile_img' ORDER BY id DESC LIMIT 1");
    $row = fetch_assoc($result);
    $user_id = $row['id'];

    // Insert a new entry into control_center_chat_app_chats
    $created_on = date('Y-m-d H:i:s');
    query("INSERT INTO control_center_chat_app_chats VALUES (0, '', '$user_id', '$created_on')");
    echo "success";
}
?>