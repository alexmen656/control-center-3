<?php
/**
 * Öffentlicher Endpoint zum Abrufen der Custom Login Config
 * Keine Authentifizierung erforderlich
 */

ini_set('display_errors', false);
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db_connection.php";
include "functions.php";

// Nur GET requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$domain = isset($_GET['domain']) ? escape_string($_GET['domain']) : '';

if (empty($domain)) {
    echo json_encode(['success' => false, 'message' => 'Domain parameter required']);
    exit;
}

// Custom Login Config aus DB holen
$result = query("SELECT * FROM custom_login_domains WHERE domain='$domain' AND is_enabled=1");

if ($row = fetch_assoc($result)) {
    // Projekt-Info holen für zusätzliche Daten
    $projectResult = query("SELECT name FROM projects WHERE projectID='{$row['projectID']}'");
    $project = fetch_assoc($projectResult);
    
    echo json_encode([
        'success' => true,
        'config' => [
            'domain' => $row['domain'],
            'primary_color' => $row['primary_color'],
            'logo_url' => $row['logo_url'],
            'company_name' => $row['company_name'] ?: ($project['name'] ?? 'Control Center'),
            'project_name' => $project['name'] ?? 'Control Center'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Domain nicht gefunden oder nicht aktiviert']);
}
