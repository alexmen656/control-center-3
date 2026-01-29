<?php
namespace ControlCenter;

class PushService
{
    private string $serviceAccountPath;
    private string $projectId;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;

    public function __construct(?string $serviceAccountPath = null)
    {
        global $firebase_service_account_path;

        $this->serviceAccountPath = $serviceAccountPath
            ?? $firebase_service_account_path
            ?? __DIR__ . '/../../firebase-service-account.json';

        $this->projectId = 'control-center-2';
    }

    public function send(int $userID, string $title, string $body): array
    {
        $tokens = $this->getUserTokens($userID);

        if (empty($tokens)) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'errors' => ['No push tokens found for user']];
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($tokens as $tokenRow) {
            $result = $this->sendToToken($tokenRow['token'], $title, $body);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                $errors[] = $result['error'];
            }
        }

        return [
            'success' => $sent > 0,
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    public function sendToToken(string $token, string $title, string $body): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'badge' => 1,
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'response' => json_decode($response, true)];
        }

        $errorMsg = "FCM error (HTTP $httpCode)";
        $decoded = json_decode($response, true);
        if (isset($decoded['error']['message'])) {
            $errorMsg .= ': ' . $decoded['error']['message'];
        }

        error_log("PushService: $errorMsg");
        return ['success' => false, 'error' => $errorMsg];
    }

    private function getUserTokens(int $userID): array
    {
        $userID = (int) $userID;
        $result = query("SELECT token, platform FROM control_center_push_notifications_token WHERE userID = $userID");

        $tokens = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = fetch_assoc($result)) {
                $tokens[] = $row;
            }
        }

        return $tokens;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $jwt = $this->createJWT();
        if (!$jwt) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("PushService: Failed to get access token (HTTP $httpCode): $response");
            return null;
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'] ?? null;
        $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 60;

        return $this->accessToken;
    }

    private function createJWT(): ?string
    {
        if (!file_exists($this->serviceAccountPath)) {
            error_log("PushService: Service account file not found: {$this->serviceAccountPath}");
            return null;
        }

        $sa = json_decode(file_get_contents($this->serviceAccountPath), true);
        if (!$sa || !isset($sa['client_email'], $sa['private_key'])) {
            error_log("PushService: Invalid service account JSON");
            return null;
        }

        $now = time();
        $header = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::base64url(json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = "$header.$payload";
        $signature = '';
        $key = openssl_pkey_get_private($sa['private_key']);
        if (!$key || !openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            error_log("PushService: Failed to sign JWT");
            return null;
        }

        return $unsigned . '.' . self::base64url($signature);
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
