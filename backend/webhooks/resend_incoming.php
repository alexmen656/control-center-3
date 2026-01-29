<?php
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

try {
    $autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php');
    if ($autoloadPath && file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
} catch (\Throwable $e) {
    error_log("Autoloader error in resend_incoming webhook: " . $e->getMessage());
}

require_once __DIR__ . '/../db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawPayload = file_get_contents('php://input');

if (empty($rawPayload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

$logFile = __DIR__ . '/../logs/resend_incoming_' . date('Y-m-d') . '.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
        $headers[$headerName] = $value;
    }
}

$logEntry = [
    'timestamp' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => $headers,
    'payload_preview' => substr($rawPayload, 0, 2000),
];
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

$payload = json_decode($rawPayload, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

require_once __DIR__ . '/../services/ResendReceiver.php';

try {
    $receiver = new \ControlCenter\ResendReceiver($con);
    $result = $receiver->processWebhook($rawPayload, $headers);

    file_put_contents($logFile, json_encode([
        'timestamp' => date('c'),
        'event_type' => $payload['type'] ?? 'unknown',
        'result' => $result,
    ]) . "\n", FILE_APPEND);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(200);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("Resend Webhook Error: " . $e->getMessage());

    file_put_contents($logFile, json_encode([
        'timestamp' => date('c'),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]) . "\n", FILE_APPEND);

    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
    ]);
}
