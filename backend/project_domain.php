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
        $setHomepageResult = null;
        if ($insert) {
            // Versuche, falls Repo verbunden, das homepage-Feld zu setzen
            $repoRes = query("SELECT * FROM control_center_project_repos WHERE project='$project' LIMIT 1");
            if ($repoRow = fetch_assoc($repoRes)) {
                $tokenRes = query("SELECT github_token FROM control_center_github_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
                if ($tokenRow = fetch_assoc($tokenRes)) {
                    $token = $tokenRow['github_token'];
                    $ownerRepo = $repoRow['repo_full_name'];
                    $apiUrl = 'https://api.github.com/repos/' . $ownerRepo;
                    $data = [ 'homepage' => 'https://' . $fullDomain ];
                    $opts = [
                        'http' => [
                            'method' => 'PATCH',
                            'header' => "Authorization: token $token\r\nUser-Agent: ControlCenter\r\nAccept: application/vnd.github.v3+json\r\nContent-Type: application/json\r\n",
                            'content' => json_encode($data)
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $result = @file_get_contents($apiUrl, false, $context);
                    $setHomepageResult = $result ? json_decode($result, true) : null;
                }
            }
            echo json_encode(['success' => true, 'domain' => $fullDomain, 'github_homepage_set' => $setHomepageResult]);
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
