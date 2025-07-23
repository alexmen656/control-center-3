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
        $vercelResult = null;
        if ($insert) {
            // === Vercel Domain Connect ===
            $vercelRes = query("SELECT * FROM control_center_project_vercel_projects WHERE project='$project' LIMIT 1");
            if ($vercelRow = fetch_assoc($vercelRes)) {
                $vercel_project_id = $vercelRow['vercel_project_id'];
                $vercelTokenRes = query("SELECT vercel_token FROM control_center_vercel_tokens WHERE userID='" . escape_string($user_id) . "' LIMIT 1");
                if ($vercelTokenRow = fetch_assoc($vercelTokenRes)) {
                    $vercel_token = $vercelTokenRow['vercel_token'];
                    $vercelApiUrl = "https://api.vercel.com/v10/projects/{$vercel_project_id}/domains";
                    $vercelData = [ 'name' => $fullDomain ];
                    $vercelOpts = [
                        'http' => [
                            'method' => 'POST',
                            'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
                            'content' => json_encode($vercelData)
                        ]
                    ];
                    $vercelContext = stream_context_create($vercelOpts);
                    $vercelResponse = @file_get_contents($vercelApiUrl, false, $vercelContext);
                    $vercelResult = $vercelResponse ? json_decode($vercelResponse, true) : null;
                    // Fehlerbehandlung: Falls Fehler, Insert rückgängig machen
                    if (!$vercelResponse || (isset($vercelResult['error']) && $vercelResult['error'])) {
                        // Rollback Domain-Insert
                        query("DELETE FROM control_center_project_domains WHERE project='$project' AND domain='$fullDomain'");
                        $errMsg = isset($vercelResult['error']['message']) ? $vercelResult['error']['message'] : 'Vercel API Fehler';
                        echo json_encode(['error' => 'Vercel: ' . $errMsg]);
                        exit;
                    }
                } else {
                    // Kein Vercel-Token gefunden
                    query("DELETE FROM control_center_project_domains WHERE project='$project' AND domain='$fullDomain'");
                    echo json_encode(['error' => 'Kein Vercel-Token gefunden.']);
                    exit;
                }
            } else {
                // Kein Vercel-Projekt verknüpft
                query("DELETE FROM control_center_project_domains WHERE project='$project' AND domain='$fullDomain'");
                echo json_encode(['error' => 'Kein Vercel-Projekt verknüpft.']);
                exit;

            }
                // Hole CNAME-Target von Vercel (zweiter API-Call)
                $cnameTarget = null;
                $vercelDomainConfigUrl = "https://api.vercel.com/v6/domains/$fullDomain/config";
                $vercelDomainConfigOpts = [
                    'http' => [
                        'method' => 'GET',
                        'header' => "Authorization: Bearer $vercel_token\r\nUser-Agent: ControlCenter\r\nAccept: application/json\r\n"
                    ]
                ];
                $vercelDomainConfigContext = stream_context_create($vercelDomainConfigOpts);
                $vercelDomainConfigResponse = @file_get_contents($vercelDomainConfigUrl, false, $vercelDomainConfigContext);
                error_log('[cloudns] Vercel config response: ' . $vercelDomainConfigResponse);
                if ($vercelDomainConfigResponse) {
                    $vercelDomainConfig = json_decode($vercelDomainConfigResponse, true);
                    if (isset($vercelDomainConfig['recommendedCNAME'][0]['value'])) {
                        $cnameTarget = $vercelDomainConfig['recommendedCNAME'][0]['value'];
                    } elseif (isset($vercelDomainConfig['cnameTarget'])) {
                        $cnameTarget = $vercelDomainConfig['cnameTarget'];
                    } elseif (isset($vercelDomainConfig['domain']['cnameTarget'])) {
                        $cnameTarget = $vercelDomainConfig['domain']['cnameTarget'];
                    }
                }
                error_log('[cloudns] CNAME-Target: ' . $cnameTarget);
                if (!$cnameTarget) {
                    // Kein CNAME-Target gefunden, Insert rückgängig machen
                    query("DELETE FROM control_center_project_domains WHERE project='$project' AND domain='$fullDomain'");
                    error_log('[cloudns] ERROR: Kein CNAME-Target von Vercel erhalten!');
                    echo json_encode(['error' => 'cloudns: Kein CNAME-Target von Vercel erhalten!']);
                    exit;
                }

                // === GitHub Homepage setzen (wie bisher) ===
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

                // === cloudns.net CNAME setzen ===
                $cloudnsResult = null;
                $cloudns_auth_id = isset($cloudns_auth_id) ? $cloudns_auth_id : '';
                $cloudns_auth_password = isset($cloudns_auth_password) ? $cloudns_auth_password : '';
                $cloudns_zone = isset($cloudns_zone) ? $cloudns_zone : 'sites.control-center.eu';
                $cloudns_api_url = 'https://api.cloudns.net/dns/add-record.json';
                $cloudns_post = http_build_query([
                    'auth-id' => $cloudns_auth_id,
                    'auth-password' => $cloudns_auth_password,
                    'domain-name' => $cloudns_zone,
                    'record-type' => 'CNAME',
                    'host' => $domain, // nur subdomain
                    'record' => $cnameTarget,
                    'ttl' => 300
                ]);
                error_log('[cloudns] POST: ' . $cloudns_post);
                $opts = [
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'content' => $cloudns_post
                    ]
                ];
                $context = stream_context_create($opts);
                $cloudns_response = @file_get_contents($cloudns_api_url, false, $context);
                error_log('[cloudns] RESPONSE: ' . $cloudns_response);
                $cloudnsResult = $cloudns_response ? json_decode($cloudns_response, true) : null;
                // Fehlerbehandlung: Falls Fehler, Insert rückgängig machen
                if (!$cloudns_response || (isset($cloudnsResult['status']) && $cloudnsResult['status'] == 'Failed')) {
                    query("DELETE FROM control_center_project_domains WHERE project='$project' AND domain='$fullDomain'");
                    $errMsg = isset($cloudnsResult['statusDescription']) ? $cloudnsResult['statusDescription'] : 'cloudns API Fehler';
                    error_log('[cloudns] ERROR: ' . $errMsg);
                    echo json_encode(['error' => 'cloudns: ' . $errMsg]);
                    exit;
                }
                echo json_encode(['success' => true, 'domain' => $fullDomain, 'vercel' => $vercelResult, 'cloudns' => $cloudnsResult, 'github_homepage_set' => $setHomepageResult]);
           
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
