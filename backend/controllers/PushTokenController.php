<?php

class PushTokenController
{
    public function register(Request $request, Response $response): void
    {
        $token = escape_string($request->input('token', ''));
        $platform = escape_string($request->input('platform', ''));
        $userID = escape_string((string) $request->userID);

        if ($token === '' || $userID === '') {
            $response->error('token and userID are required', 400);
            return;
        }

        $search = query("SELECT * FROM control_center_push_notifications_token WHERE token='$token' AND userID='$userID'");
        if (mysqli_num_rows($search) == 0) {
            if (query("INSERT INTO control_center_push_notifications_token VALUES (0, '$token', '$platform', '$userID')")) {
                query("INSERT INTO control_center_push_notifications (date, time, token, body, title)
            VALUES (CURDATE(), CURTIME(), '$token', 'Now, you will get push messages from Fringelo!', 'Welcome!')");
            }
            $response->json(['success' => true]);
        } else {
            $response->json(['success' => true, 'message' => 'Device already registred']);
        }
    }
}
