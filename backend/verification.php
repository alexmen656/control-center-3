<?php
include 'head.php';

if($_POST['verificationToken']){
 $token = escape_string($_POST['verificationToken']);
 $userData = fetch_assoc(query("SELECT *, alexs_blog_login_log.token AS token2 FROM alexs_blog_login_log JOIN alexs_blog_users ON alexs_blog_login_log.userID=alexs_blog_users.userID WHERE alexs_blog_login_log.token='$token'"));   
//echo $userData[];


    if($_POST['verificationCode']){
        $query = query("SELECT * FROM alexs_blog_login_log WHERE token='$token'");
        if(mysqli_num_rows($query) == 1){

            $logData = fetch_assoc($query); 
          //         echo "I get this code: ".escape_string($_POST['verificationCode']).",";
        //echo $logData['verification_code'];
        //print_r($logData);
            if(str_replace(" ", "", escape_string($_POST['verificationCode'])) == $logData['verification_code']){
                    $loginToken = $userData['loginToken'];
             //  
           //  echo "Code is true";
             //verification_code
                $json['token'] = $loginToken;
                $updateLog= query("UPDATE alexs_blog_login_log SET action='successfull' WHERE token='$token'");
                echo json_encode($json, JSON_PRETTY_PRINT);

            }
        }
    }else{
//Headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Chater<noreply@kamala.aglo.eu>' . "\r\n";
//More Email Data
    $subject = "Chater verification code";
    $verificationCode = rand(100000, 999999);
query("UPDATE alexs_blog_login_log SET verification_code='$verificationCode' WHERE token='$token'");
$message = "Dear ".escape_string($userData['firstname'])." your verification code for Chater is:<br>".$verificationCode;
$mail = mail(escape_string($userData['email']),$subject,$message,$headers);
    }


}

?>