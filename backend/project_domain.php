<?php
// project_domain.php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $project = escape_string($_POST['project'] ?? '');
    $user_id = escape_string($_POST['user_id'] ?? '');
    $domain = strtolower(trim($_POST['domain'] ?? ''));
    if ($action === 'connect' && $project && $user_id && $domain) {
        // Domain-Format prüfen
        if (!preg_match('/^[a-z0-9-]+$/', $domain)) {
            echo json_encode(['error' => 'Ungültiges Domain-Format. Nur Kleinbuchstaben, Zahlen und Bindestriche erlaubt.']);
            exit;
        }
        $fullDomain = $domain . '.sites.control-center.eu';
        // Prüfen ob Domain schon vergeben
        $exists = query("SELECT id FROM control_center_project_domains WHERE domain='$fullDomain' LIMIT 1");
        if (mysqli_num_rows($exists) > 0) {
            echo json_encode(['error' => 'Domain bereits vergeben.']);
            exit;
        }
        $insert = query("INSERT INTO control_center_project_domains (project, domain, user_id) VALUES ('$project', '$fullDomain', '$user_id')");
        if ($insert) {
            echo json_encode(['success' => true, 'domain' => $fullDomain]);
        } else {
            echo json_encode(['error' => 'Insert failed']);
        }
        exit;
    }
    if ($action === 'get' && $project) {
        $res = query("SELECT * FROM control_center_project_domains WHERE project='$project' LIMIT 1");
        if ($row = fetch_assoc($res)) {
            echo json_encode(['domain' => $row['domain']]);
        } else {
            echo json_encode(['domain' => null]);
        }
        exit;
    }
}
echo json_encode(['error' => 'Invalid request']);
