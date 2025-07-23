<?php
$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
//if (! in_array($host_domain, $allowed_origins, false)) {
//  header('HTTP/1.0 403 Forbidden');
//  die('You are not allowed to access this.');     
//}
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';
include_once 'jwt_helper.php';
include_once 'config.php';
$headers = getRequestHeaders();

function randomNumber(){
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json){
    return json_encode($json, JSON_PRETTY_PRINT);
}

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = escape_string($_POST['email']);
    $password = escape_string($_POST['password']);
    $select = query("SELECT * FROM control_center_users WHERE email='$email'");

    if ($select) {
        //echo 1;
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
                $mailBody = "<html><body style='font-family: Arial, sans-serif; background: #f6f6f6; padding: 0; margin: 0;'>"
                    . "<div style='max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 32px 24px;'>"
                    . "<div style='text-align:center; margin-bottom: 24px;'><img src='https://alex.polan.sk/control-center/assets/logo_inline_large.png' alt='Control Center Logo' style='max-width: 120px;'></div>"
                    . "<h2 style='color: #222; text-align:center; margin-bottom: 12px;'>Dein Einmal-Code</h2>"
                    . "<p style='font-size: 16px; color: #444; text-align:center;'>Hallo <b>" . htmlspecialchars($data['firstname']) . "</b>,<br><br>"
                    . "um dich sicher einzuloggen, gib bitte folgenden Code ein:</p>"
                    . "<div style='font-size: 2.2em; letter-spacing: 0.2em; color: #0078d4; font-weight: bold; text-align:center; margin: 24px 0 16px 0;'>" . $code . "</div>"
                    . "<p style='font-size: 15px; color: #888; text-align:center;'>Dieser Code ist nur für dich bestimmt und <b>gilt nur für kurze Zeit</b>.<br>Teile ihn niemals mit anderen Personen.</p>"
                    . "<div style='margin-top: 32px; text-align:center;'><small style='color:#bbb;'>Wenn du diese E-Mail nicht angefordert hast, kannst du sie ignorieren.<br>Mit freundlichen Grüßen,<br>Dein Control Center Team</small></div>"
                    . "</div></body></html>";
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Dein Control Center Einmal-Code",
                    $mailBody,
                    $headers . "\r\nContent-type: text/html; charset=UTF-8"
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
                $mailBody = "<html><body style='font-family: Arial, sans-serif; background: #f6f6f6; padding: 0; margin: 0;'>"
                    . "<div style='max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 32px 24px;'>"
                    . "<div style='text-align:center; margin-bottom: 24px;'><img src='https://alex.polan.sk/control-center/assets/logo_inline_large.png' alt='Control Center Logo' style='max-width: 120px;'></div>"
                    . "<h2 style='color: #222; text-align:center; margin-bottom: 12px;'>Dein Einmal-Code</h2>"
                    . "<p style='font-size: 16px; color: #444; text-align:center;'>Hallo <b>" . htmlspecialchars($data['firstname']) . "</b>,<br><br>"
                    . "um dich sicher einzuloggen, gib bitte folgenden Code ein:</p>"
                    . "<div style='font-size: 2.2em; letter-spacing: 0.2em; color: #0078d4; font-weight: bold; text-align:center; margin: 24px 0 16px 0;'>" . $code . "</div>"
                    . "<p style='font-size: 15px; color: #888; text-align:center;'>Dieser Code ist nur für dich bestimmt und <b>gilt nur für kurze Zeit</b>.<br>Teile ihn niemals mit anderen Personen.</p>"
                    . "<div style='margin-top: 32px; text-align:center;'><small style='color:#bbb;'>Wenn du diese E-Mail nicht angefordert hast, kannst du sie ignorieren.<br>Mit freundlichen Grüßen,<br>Dein Control Center Team</small></div>"
                    . "</div></body></html>";
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Dein Control Center Einmal-Code",
                    $mailBody,
                    $headers . "\r\nContent-type: text/html; charset=UTF-8"
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
                $mailBody = "<html><body style='font-family: Arial, sans-serif; background: #f6f6f6; padding: 0; margin: 0;'>"
                    . "<div style='max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 32px 24px;'>"
                    . "<div style='text-align:center; margin-bottom: 24px;'><img src='https://alex.polan.sk/control-center/assets/logo_inline_large.png' alt='Control Center Logo' style='max-width: 120px;'></div>"
                    . "<h2 style='color: #222; text-align:center; margin-bottom: 12px;'>Dein Einmal-Code</h2>"
                    . "<p style='font-size: 16px; color: #444; text-align:center;'>Hallo <b>" . htmlspecialchars($data['firstname']) . "</b>,<br><br>"
                    . "um dich sicher einzuloggen, gib bitte folgenden Code ein:</p>"
                    . "<div style='font-size: 2.2em; letter-spacing: 0.2em; color: #0078d4; font-weight: bold; text-align:center; margin: 24px 0 16px 0;'>" . $code . "</div>"
                    . "<p style='font-size: 15px; color: #888; text-align:center;'>Dieser Code ist nur für dich bestimmt und <b>gilt nur für kurze Zeit</b>.<br>Teile ihn niemals mit anderen Personen.</p>"
                    . "<div style='margin-top: 32px; text-align:center;'><small style='color:#bbb;'>Wenn du diese E-Mail nicht angefordert hast, kannst du sie ignorieren.<br>Mit freundlichen Grüßen,<br>Dein Control Center Team</small></div>"
                    . "</div></body></html>";
                mail(
                    $data['firstname'] . " " . $data['lastname'] . "<" . $data['email'] . ">",
                    "Dein Control Center Einmal-Code",
                    $mailBody,
                    $headers . "\r\nContent-type: text/html; charset=UTF-8"
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