<?php
include 'head.php';
$headers = getRequestHeaders();

if (isset($headers['Authorization'])) {


    $token = escape_string($headers['Authorization']);
    $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
    if (mysqli_num_rows($data) == 1) {


        if (isset($_REQUEST['firstName'])) { // && $_POST['name'] && $_POST['email']
            $firstName = escape_string($_REQUEST['firstName']); //['value'];
            $lastName = escape_string($_REQUEST['lastName']); //['value'];
            $email = escape_string($_REQUEST['email']); //['value'];
            echo "I have to fucking Data!!!!";
            query("UPDATE control_center_users SET email='$email', firstname='$firstName', lastname='$lastName' WHERE loginToken='$token'");

        } elseif (isset($_REQUEST['updateProfileImage']) && isset($_REQUEST['data']) && isset($_REQUEST['name'])) {
            $baseData = escape_string($_REQUEST['data']);
            $fileName = escape_string($_REQUEST['name']);
            query("UPDATE control_center_users SET profileImg='$fileName' WHERE loginToken='$token'");
            if (createFile('images/profileImages/' . $fileName, $baseData, 0777)) {
                echo "file created";
            }

        } elseif (isset($_REQUEST['updateLoginWithGoogle']) && isset($_REQUEST['newValue'])) {
            $newValue = escape_string($_REQUEST['newValue']);
            if(query("UPDATE control_center_users SET login_with_google='$newValue' WHERE loginToken='$token'")){
                echo "Success!";
            }
        } else {
            $data = fetch_assoc($data);
            if ($data['profileImg'] != "avatar" && $data['profileImg'] != "google") {
                $data['profileImg'] = file_get_contents('images/profileImages/' . $data['profileImg']);
            }elseif($data['profileImg'] == "google"){
                $userID = $data['userID'];
                $select = query("SELECT * FROM control_center_google_profile_images WHERE userID=$userID");
                if(mysqli_num_rows($select) == 1){
                    $data['profileImg'] = fetch_assoc($select)['image'];
                }
            }

            $json['profileImg'] = $data['profileImg'];
            $json['firstName'] = $data['firstname'];
            $json['lastName'] = $data['lastname'];
            $json['email'] = $data['email'];
            $json['userID'] = $data['userID'];
            if($data['login_with_google'] == 'true'){
                $json['login_with_google'] = true;
            }elseif($data['login_with_google'] == 'false'){
                $json['login_with_google'] = false;
            }else{
                $json['login_with_google'] = $data['login_with_google'];
            }
            $json['accountStatus'] = $data['account_status'];
            echo preg_replace('/^\h*\v+/m', '', echoJson($json));
        }

    } else {
        echo "No valid token";
    }






} elseif (isset($_POST["checkEmailExists"]) && isset($_POST['email'])) {
    $email = escape_string($_POST['email']);
    $data = query("SELECT * FROM control_center_users WHERE email='$email'");
    if (mysqli_num_rows($data) > 0) {
        $value['value'] = true;
    } else {
        $value['value'] = false;
    }
    echo echoJson($value);
}


?>