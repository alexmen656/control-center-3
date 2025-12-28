<?php
/**
 * Web Builder Webhook Handler
 * Ruft Shell-Scripts für Setup/Cleanup auf
 */

define('WEBHOOK_SECRET', 'cc_web_builder_webhook_secret_2025');

// Logging
function logWebhook($message)
{
    $logFile = '/var/log/web_builder_webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Request Data einlesen
$json = file_get_contents('php://input');
$data = json_decode($json, true);

logWebhook("Webhook received: " . json_encode($data));

// Validierung
if (!$data || !isset($data['secret']) || $data['secret'] !== WEBHOOK_SECRET) {
    http_response_code(403);
    logWebhook("Invalid secret");
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($data['domain']) || !isset($data['project']) || !isset($data['type'])) {
    http_response_code(400);
    logWebhook("Missing required fields");
    die(json_encode(['error' => 'Missing required fields']));
}

$domain = escapeshellarg($data['domain']);
$project = escapeshellarg($data['project']);
$type = $data['type'];
$action = $data['action'] ?? 'setup';

// Nur Web Builder Domains verarbeiten
if ($type !== 'web_builder') {
    http_response_code(400);
    logWebhook("Invalid type: $type");
    die(json_encode(['error' => 'Invalid type']));
}

logWebhook("Processing domain: $domain for project: $project, action: $action");

// Shell-Script ausführen
if ($action === 'setup') {
    // Setup-Script aufrufen
    $command = "sudo /home/ftpuser/webhook/setup_web_builder_project.sh $project $domain 2>&1";
    logWebhook("Executing: $command");
    
    exec($command, $output, $returnCode);
    $outputStr = implode("\n", $output);
    
    logWebhook("Script output: $outputStr");
    logWebhook("Return code: $returnCode");
    
    if ($returnCode === 0) {
        logWebhook("Setup completed successfully for $domain");
        
        // Backend benachrichtigen
        notifyBackend(trim($domain, "'\""), trim($project, "'\""), 'active');
        
        echo json_encode([
            'success' => true,
            'message' => 'Domain successfully configured',
            'domain' => trim($domain, "'\""),
            'output' => $outputStr
        ]);
    } else {
        logWebhook("Setup failed for $domain");
        notifyBackend(trim($domain, "'\""), trim($project, "'\""), 'failed');
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Setup failed',
            'error' => $outputStr
        ]);
    }
} elseif ($action === 'cleanup') {
    // Cleanup-Script aufrufen
    $command = "sudo /home/ftpuser/webhook/cleanup_web_builder_project.sh $project 2>&1";
    logWebhook("Executing: $command");
    
    exec($command, $output, $returnCode);
    $outputStr = implode("\n", $output);
    
    logWebhook("Script output: $outputStr");
    logWebhook("Return code: $returnCode");
    
    if ($returnCode === 0) {
        logWebhook("Cleanup completed successfully for $project");
        
        echo json_encode([
            'success' => true,
            'message' => 'Domain successfully removed',
            'project' => trim($project, "'\""),
            'output' => $outputStr
        ]);
    } else {
        logWebhook("Cleanup failed for $project");
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Cleanup failed',
            'error' => $outputStr
        ]);
    }
} else {
    http_response_code(400);
    logWebhook("Invalid action: $action");
    die(json_encode(['error' => 'Invalid action']));
}

/**
 * Benachrichtigt das Backend über SSL-Status
 */
function notifyBackend($domain, $project, $status)
{
    $backendUrl = 'https://alex.polan.sk/control-center/web_builder_domains.php';

    $postData = http_build_query([
        'action' => 'update_ssl_status',
        'project' => $project,
        'domain' => $domain,
        'ssl_status' => $status
    ]);

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 5
        ]
    ];

    $context = stream_context_create($options);
    @file_get_contents($backendUrl, false, $context);
}

