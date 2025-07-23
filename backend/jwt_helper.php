<?php
// Minimalistisches JWT-Helper-File (nur für HS256, keine externen Abhängigkeiten)
// Quelle: https://github.com/firebase/php-jwt (stark gekürzt)

class SimpleJWT {
    public static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload, $key, $alg = 'HS256') {
        $header = ['typ' => 'JWT', 'alg' => $alg];
        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public static function sign($msg, $key, $alg) {
        switch ($alg) {
            case 'HS256':
                return hash_hmac('sha256', $msg, $key, true);
            default:
                throw new Exception('Unsupported algorithm');
        }
    }

    public static function verify($jwt, $key) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;
        list($headb64, $payloadb64, $sigb64) = $parts;
        $header = json_decode(self::base64UrlDecode($headb64), true);
        $payload = json_decode(self::base64UrlDecode($payloadb64), true);
        $sig = self::base64UrlDecode($sigb64);
        if (!$header || !$payload || !$sig) return false;
        if (empty($payload['exp']) || time() > $payload['exp']) return false;
        $alg = isset($header['alg']) ? $header['alg'] : 'HS256';
        $valid_sig = self::sign($headb64 . '.' . $payloadb64, $key, $alg);
        if (!hash_equals($valid_sig, $sig)) return false;
        return $payload;
    }
}
