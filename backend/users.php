<?php
include 'head.php';

if(isset($_POST['new_user']) && isset($_POST['first_name']) && isset($_POST['email_adress']) && isset($_POST['password'])){
    $first_name = escape_string($_POST['first_name']);
    $last_name = escape_string($_POST['last_name']);
    $email_adress = escape_string($_POST['email_adress']);
    $password = escape_string($_POST['password']);
    $token = bin2hex(random_bytes(72));
    if(query("INSERT INTO control_center_users VALUES(0, '', '$first_name', '$last_name', '$email_adress', '$password', '$token', 'active')")){
        echo "User created successful";
    }
} elseif (isset($_REQUEST['updateAccountStatus']) && isset($_REQUEST['userID']) && isset($_REQUEST['newStatus'])) {
    $userID = escape_string($_REQUEST['userID']);
    $new_status = escape_string($_REQUEST['newStatus']);

    if(query("UPDATE control_center_users SET account_status='$new_status' WHERE userID='$userID'")){
        echo "Account status updated";
    }
    
}

?>