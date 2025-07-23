<?php
$jwt_secret = 'dein_geheimer_schluessel_123'; // Setze hier einen sicheren Key!
include 'head.php';
include_once 'jwt_helper.php';
$headers = getRequestHeaders();

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = escape_string($_POST['email']);
    $password = escape_string($_POST['password']);
    $select = query("SELECT * FROM control_center_users WHERE email='$email'");

    if ($select) {
        $data = fetch_assoc($select);

        if (password_verify($password, $data['password'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $userID = $data['userID'];
            $check = query("SELECT * FROM control_center_login_log WHERE `ip`='$ip' AND `userID`='$userID' AND `action`='successfull'");
            if (mysqli_num_rows($check) > 0) {


                // JWT generieren
                $payload = [
                    'sub' => $data['userID'],
                    'email' => $data['email'],
                    'firstname' => $data['firstname'],
                    'iat' => time(),
                    'exp' => time() + 60*60*24*7 // 7 Tage gültig
                ];
                $jwt = SimpleJWT::encode($payload, $jwt_secret);
                $json['token'] = $jwt;
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
                $headers = 'From: Control Center<ems@alex.polan.sk>' . "\r\n" . 'Reply-To: Control Center<ems@alex.polan.sk>';
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Your OTP Code",
                    "Your Otp code is " . $code . "\n Do not share this code with anyone!",
                    $headers
                );
            }
        } else {
            $json["errorMessage"] = "Check email or password!";
        }
    } else {
        $json["errorMessage"] = "Check email or password!";
    }
} elseif (isset($_POST['email']) && isset($_POST['loginWithGoogle'])) {

    $email = escape_string($_POST['email']);
    $select = query("SELECT * FROM control_center_users WHERE email='$email'");

    if ($select) {
        $data = fetch_assoc($select);

        if ($data["login_with_google"] == "true") {
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $userID = $data['userID'];
            $check = query("SELECT * FROM control_center_login_log WHERE `ip`='$ip' AND `userID`='$userID' AND `action`='successfull'");
            if (mysqli_num_rows($check) > 0) {


                $payload = [
                    'sub' => $data['userID'],
                    'email' => $data['email'],
                    'firstname' => $data['firstname'],
                    'iat' => time(),
                    'exp' => time() + 60*60*24*7
                ];
                $jwt = SimpleJWT::encode($payload, $jwt_secret);
                $json['token'] = $jwt;
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
                $headers = 'From: Control Center<ems@alex.polan.sk>' . "\r\n" . 'Reply-To: Control Center<ems@alex.polan.sk>';
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Your OTP Code",
                    "Your Otp code is " . $code . "\n Do not share this code with anyone!",
                    $headers
                );
            }
        } else {
            $json["errorMessage"] = "Log In with Google is not activated!";
        }
    } else {
        $json["errorMessage"] = "Check email or password!";
    }
} elseif (isset($_POST['email']) && isset($_POST['loginWithMicrosoft'])) {

    $email = escape_string($_POST['email']);
    $select = query("SELECT * FROM control_center_users WHERE email='$email'");

    if ($select) {
        $data = fetch_assoc($select);

        if (strtolower($data["login_with_google"]) == "microsoft") {
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $userID = $data['userID'];
            $check = query("SELECT * FROM control_center_login_log WHERE `ip`='$ip' AND `userID`='$userID' AND `action`='successfull'");
            if (mysqli_num_rows($check) > 0) {


                $payload = [
                    'sub' => $data['userID'],
                    'email' => $data['email'],
                    'firstname' => $data['firstname'],
                    'iat' => time(),
                    'exp' => time() + 60*60*24*7
                ];
                $jwt = SimpleJWT::encode($payload, $jwt_secret);
                $json['token'] = $jwt;
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
                $headers = 'From: Control Center<ems@alex.polan.sk>' . "\r\n" . 'Reply-To: Control Center<ems@alex.polan.sk>';
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Your OTP Code",
                    "Your Otp code is " . $code . "\n Do not share this code with anyone!",
                    $headers
                );
            }
        } else {
            $json["errorMessage"] = "Log In with Microsoft is not activated!";
        }
    } else {
        $json["errorMessage"] = "Check email or password!";
    }
} elseif (empty($email) || empty($password)) {
    $json["errorMessage"] = "Email or Password empty";
}

echo json_encode($json, JSON_PRETTY_PRINT);
?>