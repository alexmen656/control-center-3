<?php

if (!defined('FILE_SIGNATURE_SECRET')) {
    define('FILE_SIGNATURE_SECRET', 'cc_secure_file_sign_2026_secret_key');
}
if (!defined('DEFAULT_VALIDITY')) {
    define('DEFAULT_VALIDITY', 3600);
}

class SignedUrlGenerator
{
    public function generateSignedUrl($path, $validitySeconds = DEFAULT_VALIDITY, $projectID = null)
    {
        $expires = time() + $validitySeconds;
        $data = $path . '|' . $expires;

        if ($projectID !== null) {
            $data .= '|' . $projectID;
        }

        $signature = hash_hmac('sha256', $data, FILE_SIGNATURE_SECRET);

        $params = [
            'path' => $path,
            'expires' => $expires,
            'signature' => $signature
        ];

        if ($projectID !== null) {
            $params['project'] = $projectID;
        }

        $baseUrl = $this->getBaseUrl() . '/v2/secure-file';
        $signedUrl = $baseUrl . '?' . http_build_query($params);

        return [
            'url' => $signedUrl,
            'expires' => $expires,
            'expiresIn' => $validitySeconds
        ];
    }

    public function generateBulkSignedUrls($files, $validitySeconds = DEFAULT_VALIDITY)
    {
        $result = [];

        foreach ($files as $file) {
            $path = $file['path'] ?? $file['location'] ?? '';
            $projectID = $file['projectID'] ?? $file['project'] ?? null;

            if (empty($path)) {
                continue;
            }

            $signed = $this->generateSignedUrl($path, $validitySeconds, $projectID);
            $result[] = [
                'originalPath' => $path,
                'signedUrl' => $signed['url'],
                'expires' => $signed['expires'],
                'projectID' => $projectID
            ];
        }

        return $result;
    }

    private function getBaseUrl()
    {
        return 'https://api.fringelo.com';
    }
}
