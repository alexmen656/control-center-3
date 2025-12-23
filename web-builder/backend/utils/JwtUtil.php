<?php
/**
 * JWT Utility Class
 * 
 * Provides functions for creating and validating JSON Web Tokens (JWT)
 */
class JwtUtil {
    private static $secret = 'your-secret-key-change-this-in-production'; // In Produktion in eine .env Datei auslagern!
    private static $algorithm = 'HS256';
    private static $tokenLifetime = 86400; // 24 Stunden in Sekunden

    /**
     * Generiert einen JWT-Token für den angegebenen Benutzer
     * 
     * @param array $user Benutzerdaten (mindestens user_id)
     * @return string Der generierte JWT
     */
    public static function generateToken($user) {
        // Header
        $header = json_encode([
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        ]);
        $header = self::base64UrlEncode($header);

        // Payload
        $issuedAt = time();
        $expiresAt = $issuedAt + self::$tokenLifetime;

        $payload = json_encode([
            'iss' => 'web-builder', // Issuer
            'iat' => $issuedAt,     // Issued At
            'exp' => $expiresAt,    // Expire At
            'user_id' => $user['id'],
            'username' => $user['username']
        ]);
        $payload = self::base64UrlEncode($payload);

        // Signature
        $signature = hash_hmac('sha256', "$header.$payload", self::$secret, true);
        $signature = self::base64UrlEncode($signature);

        // Token
        return "$header.$payload.$signature";
    }

    /**
     * Validiert einen JWT-Token und gibt die darin enthaltenen Nutzerdaten zurück
     * 
     * @param string $token Der zu validierende JWT
     * @return array|false Benutzerdaten oder false bei ungültigem Token
     */
    public static function validateToken($token) {
        if (empty($token)) {
            return false;
        }

        // Token Teile extrahieren
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        // Signature validieren
        $verifySignature = hash_hmac('sha256', "$header.$payload", self::$secret, true);
        $verifySignature = self::base64UrlEncode($verifySignature);

        if ($signature !== $verifySignature) {
            return false;
        }

        // Payload decodieren
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        if (!$decodedPayload) {
            return false;
        }

        // Überprüfung des Ablaufdatums
        if (isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
            return false;
        }

        return $decodedPayload;
    }

    /**
     * Base64 URL-sichere Kodierung
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-sichere Dekodierung
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Hilfsfunktion um den JWT Token aus dem Authorization Header zu extrahieren
     * 
     * @return string|null Der Token oder null wenn keiner gefunden wurde
     */
    public static function getBearerToken() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        if (!$headers) {
            return null;
        }
        
        // Header Format: "Bearer token"
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}