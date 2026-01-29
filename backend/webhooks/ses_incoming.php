<?php
/**
 * AWS SES Incoming Email Webhook
 * 
 * Dieser Endpoint empfängt SNS-Notifications von AWS SES für eingehende E-Mails.
 * 
 * Setup:
 * 1. In AWS SES eine Receipt Rule erstellen:
 *    - Aktion: S3 (E-Mail in S3 speichern) und/oder SNS (Notification senden)
 *    - SNS Topic erstellen und diesen Endpoint als HTTPS Subscriber hinzufügen
 * 
 * 2. SNS Subscription bestätigen:
 *    - Beim ersten Aufruf sendet AWS eine SubscriptionConfirmation
 *    - Dieser Endpoint bestätigt diese automatisch
 * 
 * URL: https://yourdomain.com/backend/webhooks/ses_incoming.php
 */

// CORS und Header
header('Content-Type: application/json');

// Nur POST-Requests akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Raw Body lesen
$rawPayload = file_get_contents('php://input');

if (empty($rawPayload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

// Logging für Debugging
$logFile = __DIR__ . '/../logs/ses_incoming_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = [
    'timestamp' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'payload_preview' => substr($rawPayload, 0, 1000),
];
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

// AWS SNS Message Signature verifizieren (optional aber empfohlen)
$payload = json_decode($rawPayload, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// SNS Message Type Header prüfen
$messageType = $_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE'] ?? $payload['Type'] ?? null;

// Optional: Signature verifizieren
if (!verifySnsSignature($payload)) {
    // Für Entwicklung: Warnung loggen aber trotzdem verarbeiten
    error_log("SNS Signature verification failed - continuing anyway for development");
}

// DB-Verbindung und Receiver laden
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../services/AwsSesReceiver.php';

try {
    $receiver = new \ControlCenter\AwsSesReceiver($con);
    $result = $receiver->processSnsNotification($rawPayload);

    // Log result
    file_put_contents($logFile, json_encode([
        'timestamp' => date('c'),
        'result' => $result,
    ]) . "\n", FILE_APPEND);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("SES Webhook Error: " . $e->getMessage());
    
    file_put_contents($logFile, json_encode([
        'timestamp' => date('c'),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]) . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
    ]);
}

/**
 * Verifiziere AWS SNS Signature (optional)
 * Für Produktiv-Umgebung empfohlen
 */
function verifySnsSignature(array $payload): bool
{
    // Für SubscriptionConfirmation und Notification
    if (!isset($payload['SigningCertURL']) || !isset($payload['Signature'])) {
        return true; // Keine Signatur vorhanden - für direkte Aufrufe
    }

    // Prüfe ob SigningCertURL von AWS kommt
    $certUrl = $payload['SigningCertURL'];
    $parsedUrl = parse_url($certUrl);
    
    if (!$parsedUrl || 
        !isset($parsedUrl['host']) || 
        !preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $parsedUrl['host'])) {
        error_log("Invalid SNS certificate URL: $certUrl");
        return false;
    }

    // Zertifikat abrufen
    $certificate = @file_get_contents($certUrl);
    if (!$certificate) {
        error_log("Failed to fetch SNS certificate");
        return false;
    }

    // String to sign erstellen
    $stringToSign = '';
    $type = $payload['Type'] ?? '';
    
    if ($type === 'Notification') {
        $stringToSign = "Message\n{$payload['Message']}\n"
            . "MessageId\n{$payload['MessageId']}\n";
        
        if (isset($payload['Subject'])) {
            $stringToSign .= "Subject\n{$payload['Subject']}\n";
        }
        
        $stringToSign .= "Timestamp\n{$payload['Timestamp']}\n"
            . "TopicArn\n{$payload['TopicArn']}\n"
            . "Type\n{$payload['Type']}\n";
    } else {
        // SubscriptionConfirmation oder UnsubscribeConfirmation
        $stringToSign = "Message\n{$payload['Message']}\n"
            . "MessageId\n{$payload['MessageId']}\n"
            . "SubscribeURL\n{$payload['SubscribeURL']}\n"
            . "Timestamp\n{$payload['Timestamp']}\n"
            . "Token\n{$payload['Token']}\n"
            . "TopicArn\n{$payload['TopicArn']}\n"
            . "Type\n{$payload['Type']}\n";
    }

    // Signatur verifizieren
    $signature = base64_decode($payload['Signature']);
    $pubKey = openssl_pkey_get_public($certificate);
    
    if (!$pubKey) {
        error_log("Failed to extract public key from certificate");
        return false;
    }

    $verified = openssl_verify($stringToSign, $signature, $pubKey, OPENSSL_ALGO_SHA1);
    
    return $verified === 1;
}
