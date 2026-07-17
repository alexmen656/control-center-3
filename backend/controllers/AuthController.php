<?php

class AuthController
{
    private function loginOtpMailBody(string $firstname, int $code): string
    {
        return "<html><body style='font-family: Arial, sans-serif; background: #f6f6f6; padding: 0; margin: 0;'>"
            . "<div style='max-width: 480px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 32px 24px;'>"
            . "<div style='text-align:center; margin-bottom: 24px;'><img src='https://fringelo.com/fringelo_logo.png' alt='Fringelo Logo' style='max-width: 120px;'></div>"
            . "<h2 style='color: #222; text-align:center; margin-bottom: 12px;'>Dein Einmal-Code</h2>"
            . "<p style='font-size: 16px; color: #444; text-align:center;'>Hallo <b>" . htmlspecialchars($firstname) . "</b>,<br><br>"
            . "um dich sicher einzuloggen, gib bitte folgenden Code ein:</p>"
            . "<div style='font-size: 2.2em; letter-spacing: 0.2em; color: #0078d4; font-weight: bold; text-align:center; margin: 24px 0 16px 0;'>" . $code . "</div>"
            . "<p style='font-size: 15px; color: #888; text-align:center;'>Dieser Code ist nur für dich bestimmt und <b>gilt nur für kurze Zeit</b>.<br>Teile ihn niemals mit anderen Personen.</p>"
            . "<div style='margin-top: 32px; text-align:center;'><small style='color:#bbb;'>Wenn du diese E-Mail nicht angefordert hast, kannst du sie ignorieren.<br>Mit freundlichen Grüßen,<br>Dein Fringelo Team</small></div>"
            . "</div></body></html>";
    }

    private function loginSuccessResponse(array $data, string $jwt_secret): array
    {
        $payload = [
            'sub' => $data['userID'],
            'email' => $data['email'],
            'firstname' => $data['firstname'],
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 7
        ];
        $json = [
            'token' => SimpleJWT::encode($payload, $jwt_secret),
            'firstname' => $data['firstname']
        ];
        $assignmentQuery = query("SELECT project_link FROM user_project_assignments WHERE user_id='{$data['userID']}'");
        if ($assignmentQuery && mysqli_num_rows($assignmentQuery) > 0) {
            $json['assigned_project'] = fetch_assoc($assignmentQuery)['project_link'];
        }
        return $json;
    }

    private function handleAuthenticatedLogin(array $data, string $jwt_secret): array
    {
        $json = [];
        $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $userID = $data['userID'];
        $check = query("SELECT * FROM control_center_login_log WHERE `ip`='$ip' AND `userID`='$userID' AND `action`='successfull'");
        if ($check && mysqli_num_rows($check) > 0) {
            $payload = [
                'sub' => $data['userID'],
                'email' => $data['email'],
                'firstname' => $data['firstname'],
                'iat' => time(),
                'exp' => time() + 60 * 60 * 24 * 7
            ];
            $json['token'] = SimpleJWT::encode($payload, $jwt_secret);
            $json['firstname'] = $data['firstname'];

            $assignmentQuery = query("SELECT project_link FROM user_project_assignments WHERE user_id='{$data['userID']}'");
            if ($assignmentQuery && mysqli_num_rows($assignmentQuery) > 0) {
                $assignment = fetch_assoc($assignmentQuery);
                $json['assigned_project'] = $assignment['project_link'];
            }
        } else if ($data['email_2fa_enabled'] == '0') {
            $json = $this->loginSuccessResponse($data, $jwt_secret);
        } else {
            $verificationToken = bin2hex(random_bytes(48));
            $json["command"] = 'verify-ip';
            $json["verification_token"] = $verificationToken;
            $json["verification_email"] = $data['email'];
            $json["verification_name"] = $data['firstname'];
            $email = $data['email'];
            $userID = $data['userID'];
            $code = rand(100000, 999999);
            query("INSERT INTO control_center_login_log VALUES ('0','$ip','$email','$userID','processing','$verificationToken', $code ,NOW(),'')");
            sendMail(
                $data['firstname'] . " " . $data['lastname'] . " <" . $data['email'] . ">",
                "Dein Fringelo Einmal-Code",
                $this->loginOtpMailBody($data['firstname'], $code)
            );
        }
        return $json;
    }

    public function login(Request $request, Response $response): void
    {
        global $jwt_secret;

        $email = $request->input('email');
        $password = $request->input('password');
        $loginWithGoogle = $request->input('loginWithGoogle');
        $loginWithMicrosoft = $request->input('loginWithMicrosoft');

        $json = [];

        if ($email !== null && $password !== null) {
            $emailEsc = escape_string($email);
            $passwordEsc = escape_string($password);
            $select = query("SELECT * FROM control_center_users WHERE email='$emailEsc'");

            if ($select && mysqli_num_rows($select) > 0) {
                $data = fetch_assoc($select);
                if (password_verify($passwordEsc, $data['password'])) {
                    $json = $this->handleAuthenticatedLogin($data, $jwt_secret);
                } else {
                    $json["errorMessage"] = "Check email or password!";
                }
            } else {
                $json["errorMessage"] = "Check email or password!";
            }
        } elseif ($email !== null && $loginWithGoogle !== null) {
            $emailEsc = escape_string($email);
            $select = query("SELECT * FROM control_center_users WHERE email='$emailEsc'");

            if ($select && mysqli_num_rows($select) > 0) {
                $data = fetch_assoc($select);
                if ($data["login_with_google"] == "true") {
                    $json = $this->handleAuthenticatedLogin($data, $jwt_secret);
                } else {
                    $json["errorMessage"] = "Log In with Google is not activated!";
                }
            } else {
                $json["errorMessage"] = "Check email or password!";
            }
        } elseif ($email !== null && $loginWithMicrosoft !== null) {
            $emailEsc = escape_string($email);
            $select = query("SELECT * FROM control_center_users WHERE email='$emailEsc'");

            if ($select && mysqli_num_rows($select) > 0) {
                $data = fetch_assoc($select);
                if (strtolower($data["login_with_google"]) == "microsoft") {
                    $json = $this->handleAuthenticatedLogin($data, $jwt_secret);
                } else {
                    $json["errorMessage"] = "Log In with Microsoft is not activated!";
                }
            } else {
                $json["errorMessage"] = "Check email or password!";
            }
        } elseif (empty($email) || empty($password)) {
            $json["errorMessage"] = "Email or Password empty";
        }

        $response->json($json);
    }

    public function verifyToken(Request $request, Response $response): void
    {
        global $jwt_secret;

        $auth = $request->headers['Authorization'] ?? null;

        $valid = false;
        if ($auth) {
            $payload = SimpleJWT::verify($auth, $jwt_secret);
            $valid = $payload !== false;

            if ($payload && !empty($payload['sub'])) {
                $userId = intval($payload['sub']);
                $userResult = query("SELECT userID, email, firstname, lastname, profileImg FROM control_center_users WHERE userID = $userId");
                if ($userResult && mysqli_num_rows($userResult) > 0) {
                    $userData = fetch_assoc($userResult);
                    $response->json([
                        "valid" => true,
                        "user" => [
                            "id" => $userData['userID'],
                            "userID" => $userData['userID'],
                            "email" => $userData['email'],
                            "firstName" => $userData['firstname'],
                            "lastName" => $userData['lastname'],
                            "profileImg" => $userData['profileImg']
                        ]
                    ]);
                    return;
                }
            }
        }

        $response->json(["valid" => $valid]);
    }

    public function signUp(Request $request, Response $response): void
    {
        $firstNameIn = $request->input('first_name');
        $emailIn = $request->input('email_adress');
        $passwordIn = $request->input('password');
        $loginWithGoogleIn = $request->input('login_with_google');

        if ($firstNameIn !== null && $emailIn !== null && $passwordIn !== null && $loginWithGoogleIn !== null) {
            $first_name = escape_string($firstNameIn);
            $last_name = escape_string($request->input('last_name', ''));
            $email_adress = escape_string($emailIn);
            $password = password_hash(escape_string($passwordIn), PASSWORD_DEFAULT);
            $login_with_google = escape_string($loginWithGoogleIn);
            $token = bin2hex(random_bytes(72));
            $img = 'avatar';
            if ($login_with_google == 'true') {
                $img = 'google';
            } else if ($login_with_google == 'microsoft') {
                $img = 'avatar';
            }

            if (query("INSERT INTO control_center_users (profileImg, firstname, lastname, email, password, login_with_google, loginToken, account_status) VALUES ('$img', '$first_name', '$last_name', '$email_adress', '$password', '$login_with_google', '$token', 'pending_verification')")) {
                $userID_query = query("SELECT * FROM control_center_users WHERE email = '$email_adress'");
                if ($userID_query && mysqli_num_rows($userID_query) == 1) {
                    $userID = fetch_assoc($userID_query)['userID'];
                    if ($login_with_google == 'true') {
                        $profile_img = escape_string($request->input('profile_img', ''));
                        query("INSERT INTO control_center_google_profile_images VALUES(0, '$profile_img', $userID)");
                    }
                }
                $response->json(['token' => $token]);
                return;
            }
        }

        $response->json([]);
    }

    public function verifyEmail(Request $request, Response $response): void
    {
        $verificationTokenIn = $request->input('verificationToken');
        $verificationCodeIn = $request->input('verificationCode');

        $json = [];

        if (!empty($verificationTokenIn)) {
            $token = escape_string($verificationTokenIn);
            $userData = fetch_assoc(query("SELECT *, control_center_login_log.token AS token2 FROM control_center_login_log JOIN control_center_users ON control_center_login_log.userID=control_center_users.userID WHERE control_center_login_log.token='$token'"));

            if (!empty($verificationCodeIn)) {
                $codeQuery = query("SELECT * FROM control_center_login_log WHERE token='$token'");
                if ($codeQuery && mysqli_num_rows($codeQuery) == 1) {
                    $logData = fetch_assoc($codeQuery);
                    if (str_replace(" ", "", escape_string($verificationCodeIn)) == $logData['verification_code']) {
                        $json['token'] = $userData['loginToken'];
                        query("UPDATE control_center_login_log SET action='successfull' WHERE token='$token'");
                    }
                }
            }
        }

        $response->json($json);
    }

    public function mcpLogin(Request $request, Response $response): void
    {
        global $jwt_secret;

        $emailIn = $request->input('email');
        $passwordIn = $request->input('password');

        $email = $emailIn !== null ? escape_string($emailIn) : '';
        $password = $passwordIn !== null ? $passwordIn : '';

        if ($email === '' || $password === '') {
            $response->json(['error' => 'Email and password are required.'], 400);
            return;
        }

        $select = query("SELECT * FROM control_center_users WHERE email='$email'");

        if (!$select || mysqli_num_rows($select) === 0) {
            $response->json(['error' => 'Invalid email or password.'], 401);
            return;
        }

        $data = fetch_assoc($select);

        if (!password_verify($password, $data['password'])) {
            $response->json(['error' => 'Invalid email or password.'], 401);
            return;
        }

        $payload = [
            'sub' => $data['userID'],
            'email' => $data['email'],
            'firstname' => $data['firstname'],
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 7,
        ];

        $jwt = SimpleJWT::encode($payload, $jwt_secret);

        $response->json([
            'token' => $jwt,
            'expires_in' => 60 * 60 * 24 * 7,
            'user' => [
                'id' => $data['userID'],
                'userID' => $data['userID'],
                'email' => $data['email'],
                'firstName' => $data['firstname'],
                'lastName' => $data['lastname'],
                'profileImg' => $data['profileImg'] ?? null,
            ],
        ]);
    }
}
