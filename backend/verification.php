<?php
include 'head.php';

if ($_POST['verificationToken']) {
    $token = escape_string($_POST['verificationToken']);
    $userData = fetch_assoc(query("SELECT *, control_center_login_log.token AS token2 FROM control_center_login_log JOIN control_center_users ON control_center_login_log.userID=control_center_users.userID WHERE control_center_login_log.token='$token'"));
    //echo $userData[];


    if ($_POST['verificationCode']) {
        $query = query("SELECT * FROM control_center_login_log WHERE token='$token'");
        if (mysqli_num_rows($query) == 1) {

            $logData = fetch_assoc($query);
            //         echo "I get this code: ".escape_string($_POST['verificationCode']).",";
            //echo $logData['verification_code'];
            //print_r($logData);
            if (str_replace(" ", "", escape_string($_POST['verificationCode'])) == $logData['verification_code']) {
                $loginToken = $userData['loginToken'];
                //  
                //  echo "Code is true";
                //verification_code
                $json['token'] = $loginToken;
                $updateLog = query("UPDATE control_center_login_log SET action='successfull' WHERE token='$token'");
                echo json_encode($json, JSON_PRETTY_PRINT);

            }
        }
    }


}

?>