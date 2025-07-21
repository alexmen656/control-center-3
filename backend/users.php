<?php
include 'head.php';

if (isset($_POST['new_user']) && isset($_POST['first_name']) && isset($_POST['email_adress']) && isset($_POST['password'])) {
    $first_name = escape_string($_POST['first_name']);
    $last_name = escape_string($_POST['last_name']);
    $email_adress = escape_string($_POST['email_adress']);
    $password = escape_string($_POST['password']);
    $password = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(72));
    if (query("INSERT INTO control_center_users VALUES(0, '', '$first_name', '$last_name', '$email_adress', '$password', 'false', '$token', 'active')")) {
        echo "User created successful";
    }
} elseif (isset($_REQUEST['updateAccountStatus']) && isset($_REQUEST['userID']) && isset($_REQUEST['newStatus'])) {
    $userID = escape_string($_REQUEST['userID']);
    $new_status = escape_string($_REQUEST['newStatus']);

    if (query("UPDATE control_center_users SET account_status='$new_status' WHERE userID='$userID'")) {
        echo "Account status updated";
    }

} elseif (isset($_REQUEST['deactivateUser']) && isset($_REQUEST['userID'])) {
    $userID = escape_string($_REQUEST['userID']);
    if (query("UPDATE control_center_users SET account_status='inactive' WHERE userID='$userID'")) {
        echo "User deaktiviert";
    } else {
        echo "Fehler beim Deaktivieren";
    }
} elseif (isset($_REQUEST['getAllUsers'])) {
    $json = [];
    $labels = [
        "userID",
        "profileImg",
        "firstname",
        "lastname",
        "email",
        "password",
        "login_with_google",
       // "loginToken",
        "account_status"
    ];
    $json['labels'] = $labels;
    $json['data'] = [];
    $users = query("SELECT userID, profileImg, firstname, lastname, email, password, login_with_google, account_status FROM control_center_users");// loginToken,
    foreach ($users as $u) {
        $tr = [];
        foreach ($labels as $col) {
            $tr[] = $u[$col];
        }
        $json['data'][] = $tr;
    }
    echo echoJson($json);
}

?>