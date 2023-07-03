<?php
include 'head.php';
$headers = getRequestHeaders();

if ($_POST['email'] && $_POST['password']) {

    $email = escape_string($_POST['email']);
    $password = escape_string($_POST['password']);
    $select = query("SELECT * FROM control_center_users WHERE email='$email'");
    
    if ($select) {
        $data = fetch_assoc($select);

        if ($password === $data['password']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'] ? $_SERVER['HTTP_CLIENT_IP'] : ($_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
            $userID = $data['userID'];
            $check = query("SELECT * FROM control_center_login_log WHERE `ip`='$ip' AND `userID`='$userID' AND `action`='successfull'");
            if (mysqli_num_rows($check) > 0) {

                $token = $data['loginToken'];
                $json['token'] = $token;
                $json['firstname'] = $data['firstname'];
             
            } else {
                $verificationToken = bin2hex(random_bytes(48));
                $json["command"] = 'verify-ip';
                $json["verification_token"] = $verificationToken;
                $json["verification_email"] = $data['email'];
                $json["verification_name"] = $data['firstname'];
                $email = $data['email'];
                $userID = $data['userID'];
                $code = rand(100000, 999999);
                $insert = query("INSERT INTO control_center_login_log VALUES ('0','$ip','$email','$userID','processing','$verificationToken', $code ,NOW(),'')");
                $headers = 'From: Control Center<ems@kamala.aglo.eu>' . "\r\n" . 'Reply-To: ems@kamala.aglo.eu';

                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "OTP Code",
                    "Yout Otp code is " . $code,
                    $headers
                );
            }
        } else {
            $json["errorMessage"] = "Check email or password!";
        }
    } else {
        $json["errorMessage"] = "Check email or password!";
    }
} elseif (empty($email) || empty($password)) {
    $json["errorMessage"] = "Email or Password empty";
}

echo json_encode($json, JSON_PRETTY_PRINT);
?>