<?php
include "head.php";

// Cloudflare API Integration für Link Tracker Domains
function createCloudflareSubdomain($subdomain, $domain, $target) {
    global $cloudflare_zone_id, $cloudflare_api_token;
    
    if (empty($cloudflare_zone_id) || empty($cloudflare_api_token)) {
        return ['success' => false, 'message' => 'Cloudflare API nicht konfiguriert'];
    }
    
    $full_domain = $subdomain . '.' . $domain;
    $api_url = "https://api.cloudflare.com/client/v4/zones/$cloudflare_zone_id/dns_records";
    
    $data = [
        'type' => 'CNAME',
        'name' => $full_domain,
        'content' => $target,
        'ttl' => 300,
        'proxied' => false
    ];
    
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer $cloudflare_api_token\r\nContent-Type: application/json\r\n",
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = @file_get_contents($api_url, false, $context);
    
    if (!$response) {
        return ['success' => false, 'message' => 'Cloudflare API Request fehlgeschlagen'];
    }
    
    $result = json_decode($response, true);
    
    if (!$result['success']) {
        $error = isset($result['errors'][0]['message']) ? $result['errors'][0]['message'] : 'Unbekannter Cloudflare Fehler';
        return ['success' => false, 'message' => $error];
    }
    
    return ['success' => true, 'data' => $result['result']];
}

if (isset($_POST['createCustomDomain'])) {
    $project_link = escape_string($_POST['project']);
    $link_id = escape_string($_POST['link_id']);
    $custom_subdomain = escape_string($_POST['custom_subdomain']);
    
    // Check project access
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    if (mysqli_num_rows($project) == 0) {
        echo echoJSON('Projekt nicht gefunden');
        exit;
    }
    
    $projectData = fetch_assoc($project);
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo echoJSON('Kein Zugriff auf das Projekt');
        exit;
    }
    
    // Verify link belongs to project
    $linkCheck = query("SELECT * FROM link_tracker_links WHERE id='$link_id' AND projectID='{$projectData['projectID']}'");
    if (mysqli_num_rows($linkCheck) == 0) {
        echo echoJSON('Link nicht gefunden');
        exit;
    }
    
    $linkData = fetch_assoc($linkCheck);
    
    // Get project domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) == 0) {
        echo echoJSON('Projekt hat keine Domain konfiguriert');
        exit;
    }
    
    $domain_data = fetch_assoc($domain_result);
    $base_domain = $domain_data['domain'];
    
    // Validate subdomain
    if (!preg_match('/^[a-z0-9-]+$/', $custom_subdomain)) {
        echo echoJSON('Ungültiges Subdomain-Format');
        exit;
    }
    
    // Check if subdomain already exists
    $existing = query("SELECT id FROM link_tracker_custom_domains WHERE subdomain='$custom_subdomain' AND base_domain='$base_domain'");
    if (mysqli_num_rows($existing) > 0) {
        echo echoJSON('Subdomain bereits vergeben');
        exit;
    }
    
    $full_domain = $custom_subdomain . '.' . $base_domain;
    $target = 'links.control-center.eu'; // Redirect target
    
    // Create Cloudflare DNS record
    $cloudflare_result = createCloudflareSubdomain($custom_subdomain, $base_domain, $target);
    
    if (!$cloudflare_result['success']) {
        echo echoJSON('Cloudflare Fehler: ' . $cloudflare_result['message']);
        exit;
    }
    
    // Save to database
    $insert = query("INSERT INTO link_tracker_custom_domains (link_id, subdomain, base_domain, full_domain, cloudflare_record_id, created_at) 
                     VALUES ('$link_id', '$custom_subdomain', '$base_domain', '$full_domain', '{$cloudflare_result['data']['id']}', NOW())");
    
    if ($insert) {
        echo echoJSON(['success' => true, 'domain' => $full_domain]);
    } else {
        echo echoJSON('Fehler beim Speichern der Domain');
    }
}

elseif (isset($_POST['getCustomDomains'])) {
    $project_link = escape_string($_POST['project']);
    $link_id = escape_string($_POST['link_id']);
    
    // Check project access
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    if (mysqli_num_rows($project) == 0) {
        echo echoJSON('Projekt nicht gefunden');
        exit;
    }
    
    $projectData = fetch_assoc($project);
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo echoJSON('Kein Zugriff auf das Projekt');
        exit;
    }
    
    // Get custom domains for this link
    $domains = [];
    $result = query("SELECT * FROM link_tracker_custom_domains WHERE link_id='$link_id' ORDER BY created_at DESC");
    
    while ($row = fetch_assoc($result)) {
        $domains[] = $row;
    }
    
    echo echoJSON(['success' => true, 'domains' => $domains]);
}

elseif (isset($_POST['deleteCustomDomain'])) {
    $domain_id = escape_string($_POST['domain_id']);
    $project_link = escape_string($_POST['project']);
    
    // Check project access
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    if (mysqli_num_rows($project) == 0) {
        echo echoJSON('Projekt nicht gefunden');
        exit;
    }
    
    $projectData = fetch_assoc($project);
    if (!checkUserProjectPermission($userID, $projectData['projectID'])) {
        echo echoJSON('Kein Zugriff auf das Projekt');
        exit;
    }
    
    // Get domain record
    $domainResult = query("SELECT cd.*, l.projectID FROM link_tracker_custom_domains cd
                          JOIN link_tracker_links l ON cd.link_id = l.id
                          WHERE cd.id='$domain_id' AND l.projectID='{$projectData['projectID']}'");
    
    if (mysqli_num_rows($domainResult) == 0) {
        echo echoJSON('Domain nicht gefunden');
        exit;
    }
    
    $domainData = fetch_assoc($domainResult);
    
    // Delete from Cloudflare if record ID exists
    if ($domainData['cloudflare_record_id']) {
        global $cloudflare_zone_id, $cloudflare_api_token;
        
        if (!empty($cloudflare_zone_id) && !empty($cloudflare_api_token)) {
            $delete_url = "https://api.cloudflare.com/client/v4/zones/$cloudflare_zone_id/dns_records/{$domainData['cloudflare_record_id']}";
            
            $opts = [
                'http' => [
                    'method' => 'DELETE',
                    'header' => "Authorization: Bearer $cloudflare_api_token\r\n"
                ]
            ];
            
            $context = stream_context_create($opts);
            @file_get_contents($delete_url, false, $context);
        }
    }
    
    // Delete from database
    $delete = query("DELETE FROM link_tracker_custom_domains WHERE id='$domain_id'");
    
    if ($delete) {
        echo echoJSON('Domain erfolgreich gelöscht');
    } else {
        echo echoJSON('Fehler beim Löschen der Domain');
    }
}

else {
    echo echoJSON('Ungültige Aktion');
}

// Create table if not exists
query("CREATE TABLE IF NOT EXISTS link_tracker_custom_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    subdomain VARCHAR(100) NOT NULL,
    base_domain VARCHAR(255) NOT NULL,
    full_domain VARCHAR(255) NOT NULL,
    cloudflare_record_id VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES link_tracker_links(id) ON DELETE CASCADE,
    UNIQUE KEY unique_domain (full_domain)
)");
?>