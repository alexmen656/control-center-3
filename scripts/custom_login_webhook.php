<?php
/**
 * Webhook Endpoint für Custom Login Domain Setup
 * Dieser Endpoint wird vom Backend aufgerufen wenn eine neue Domain erstellt wird
 * und triggert das Nginx Setup Script auf dem Frontend Server
 * 
 * WICHTIG: Dieses Script sollte auf dem FRONTEND SERVER (92.5.112.145) liegen,
 * nicht auf dem Backend Server!
 */

// Security Token für Webhook-Authentifizierung
define('WEBHOOK_SECRET', 'cc_custom_login_webhook_secret_2025');

// Log File
$logFile = '/var/log/custom-login-webhook.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Webhook-Secret');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Webhook Secret prüfen
$headers = getallheaders();
$providedSecret = $headers['X-Webhook-Secret'] ?? $_POST['webhook_secret'] ?? '';

if ($providedSecret !== WEBHOOK_SECRET) {
    logMessage("Unauthorized webhook attempt");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Input lesen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';
$domain = $input['domain'] ?? '';

if (empty($action) || empty($domain)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action or domain']);
    exit;
}

// Domain validieren
if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]*[a-zA-Z0-9]$/', $domain)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid domain format']);
    exit;
}

$scriptPath = '/home/ftpuser/webhook/setup_custom_login_domain.sh';

// Prüfen ob Script existiert
if (!file_exists($scriptPath)) {
    logMessage("Setup script not found: $scriptPath");
    http_response_code(500);
    echo json_encode(['error' => 'Setup script not found']);
    exit;
}

logMessage("Processing webhook: action=$action, domain=$domain");

switch ($action) {
    case 'add':
        $command = "sudo $scriptPath add " . escapeshellarg($domain) . " 2>&1";
        break;
    case 'remove':
        $command = "sudo $scriptPath remove " . escapeshellarg($domain) . " 2>&1";
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

// Script im Hintergrund ausführen
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

$result = implode("\n", $output);
logMessage("Script result (code $returnCode): $result");

if ($returnCode === 0) {
    echo json_encode([
        'success' => true,
        'message' => "Domain $domain " . ($action === 'add' ? 'added' : 'removed') . " successfully",
        'output' => $result
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Script execution failed',
        'output' => $result
    ]);
}
