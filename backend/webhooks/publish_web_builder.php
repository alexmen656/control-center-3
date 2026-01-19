<?php
/**
 * Web Builder Publish Webhook Handler
 * 
 * Empfängt die generierten HTML-Dateien und deployed sie
 * auf dem Publish-Server ins richtige Verzeichnis
 * 
 * Erwartet POST mit:
 * - secret: Webhook Secret
 * - project_slug: CC Project Slug (z.B. "alex-fan-club")
 * - files: Array von Dateien [{filename: "index.html", content: "..."}]
 */

define('WEBHOOK_SECRET', 'cc_web_builder_publish_secret_2025');

// Logging
function logPublish($message)
{
    $logFile = '/var/log/web_builder_publish.log';
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    error_log("[WebBuilderPublish] $message");
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE));
}

// Request Data einlesen
$json = file_get_contents('php://input');
$data = json_decode($json, true);

logPublish("Publish webhook received: " . substr($json, 0, 500) . "...");

// Validierung
if (!$data || !isset($data['secret']) || $data['secret'] !== WEBHOOK_SECRET) {
    http_response_code(403);
    logPublish("Invalid secret");
    die(json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE));
}

if (!isset($data['project_slug']) || !isset($data['files']) || !is_array($data['files'])) {
    http_response_code(400);
    logPublish("Missing required fields");
    die(json_encode(['error' => 'Missing required fields: project_slug, files'], JSON_UNESCAPED_UNICODE));
}

$projectSlug = $data['project_slug'];
$files = $data['files'];

// Validiere Project Slug (Sicherheit)
if (!preg_match('/^[a-z0-9-]+$/', $projectSlug)) {
    http_response_code(400);
    logPublish("Invalid project slug: $projectSlug");
    die(json_encode(['error' => 'Invalid project slug'], JSON_UNESCAPED_UNICODE));
}

// Zielverzeichnis
$webRoot = "/home/ftpuser/$projectSlug/wb";

logPublish("Publishing to: $webRoot");

// Prüfe ob Verzeichnis existiert
if (!is_dir($webRoot)) {
    http_response_code(404);
    logPublish("Web root does not exist: $webRoot");
    die(json_encode(['error' => 'Web root does not exist. Configure domain first.'], JSON_UNESCAPED_UNICODE));
}

$publishedFiles = [];
$errors = [];

// Dateien schreiben
foreach ($files as $file) {
    if (!isset($file['filename']) || !isset($file['content'])) {
        $errors[] = "Invalid file entry";
        continue;
    }
    
    $filename = basename($file['filename']); // Sicherheit: nur Dateiname, kein Pfad
    $content = $file['content'];
    
    // Nur erlaubte Dateiendungen (kein PHP - API laeuft auf CC Server)
    $allowedExtensions = ['html', 'htm', 'css', 'js', 'json', 'txt', 'xml', 'svg'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions)) {
        $errors[] = "Disallowed file type: $filename";
        logPublish("Skipped disallowed file type: $filename");
        continue;
    }
    
    $filePath = "$webRoot/$filename";
    
    // Wenn Datei existiert und nicht schreibbar ist, versuche Permissions zu ändern
    if (file_exists($filePath) && !is_writable($filePath)) {
        @chmod($filePath, 0664);
    }
    
    // Sicherstelle dass content UTF-8 ist
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8');
    }
    
    if (file_put_contents($filePath, $content, FILE_TEXT) !== false) {
        // Permissions setzen (group-writable für zukünftige Updates)
        @chown($filePath, 'ftpuser');
        @chgrp($filePath, 'ftpuser');
        @chmod($filePath, 0664);
        
        $publishedFiles[] = $filename;
        logPublish("Published: $filename (" . strlen($content) . " bytes)");
    } else {
        $errors[] = "Failed to write: $filename";
        logPublish("ERROR: Failed to write $filename - " . (is_writable($webRoot) ? "dir writable" : "dir not writable") . ", file exists: " . (file_exists($filePath) ? "yes" : "no"));
    }
}

// Assets-Verzeichnis erstellen falls nötig
$assetsDir = "$webRoot/assets";
if (!is_dir($assetsDir)) {
    mkdir($assetsDir, 0755, true);
    chown($assetsDir, 'ftpuser');
    chgrp($assetsDir, 'ftpuser');
}

// Response
$response = [
    'success' => count($errors) === 0,
    'published' => $publishedFiles,
    'errors' => $errors,
    'webRoot' => $webRoot,
    'filesCount' => count($publishedFiles)
];

logPublish("Publish complete: " . count($publishedFiles) . " files, " . count($errors) . " errors");

http_response_code(count($errors) === 0 ? 200 : 207);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
