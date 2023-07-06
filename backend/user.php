<?php
include 'head.php';
$headers = getRequestHeaders();

if ($headers['Authorization']) {


    $token = escape_string($headers['Authorization']);
    $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
    if (mysqli_num_rows($data) == 1) {


        if ($_REQUEST['firstName']) { // && $_POST['name'] && $_POST['email']
            $firstName = escape_string($_REQUEST['firstName']); //['value'];
            $lastName = escape_string($_REQUEST['lastName']); //['value'];
            $email = escape_string($_REQUEST['email']); //['value'];
            echo "I have to fucking Data!!!!";
            query("UPDATE control_center_users SET email='$email', firstname='$firstName', lastname='$lastName' WHERE loginToken='$token'");

        } elseif (isset($_REQUEST['updateProfileImage']) && isset($_REQUEST['data']) && isset($_REQUEST['name'])) {
            $baseData = escape_string($_REQUEST['data']);
            $fileName = escape_string($_REQUEST['name']);
            echo 222;
            query("UPDATE control_center_users SET profileImg='$fileName' WHERE loginToken='$token'");
            if (createFile('images/profileImages/' . $fileName, $baseData, 0777)) {
                echo "file created";
            }

        } else {
            $data = fetch_assoc($data);
            if ($data['profileImg'] != "avatar") {
                $data['profileImg'] = file_get_contents('images/profileImages/' . $data['profileImg']);
            }
            $json['profileImg'] = $data['profileImg'];
            $json['firstName'] = $data['firstname'];
            $json['lastName'] = $data['lastname'];
            $json['email'] = $data['email'];
            $json['userID'] = $data['userID'];
            echo preg_replace('/^\h*\v+/m', '', echoJson($json));
        }

    } else {
        echo "No valid token";
    }






} elseif (isset($_POST["checkEmailExists"]) && isset($_POST['email'])) {
    $email = escape_string($_POST['email']);
    $data = query("SELECT * FROM control_center_users WHERE email='$email'");
    if (mysqli_num_rows($data) > 0) {

        // $data = fetch_assoc($data);
        //print_r($data);
        //$json['token'] = $data['loginToken'];
        $value['value'] = true;
    } else {
        $value['value'] = false;
    }
    echo echoJson($value);
}


?>