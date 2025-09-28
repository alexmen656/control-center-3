<?php
include "head.php";

// Cloudflare IP detection (wie in login.php integriert)  
function getClientIP() {
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Generate unique slug
function generateSlug($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $slug = '';
    for ($i = 0; $i < $length; $i++) {
        $slug .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $slug;
}

// Create database tables
$linksTable = "CREATE TABLE IF NOT EXISTS link_tracker_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectID VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    target_url TEXT NOT NULL,
    slug VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_slug (projectID, slug)
)";

$visitsTable = "CREATE TABLE IF NOT EXISTS link_tracker_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referer TEXT,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES link_tracker_links(id) ON DELETE CASCADE
)";

query($linksTable);
query($visitsTable);

if (isset($_POST['createLink'])) {
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

    $title = escape_string($_POST['title']);
    $target_url = escape_string($_POST['target_url']);
    $custom_slug = escape_string($_POST['custom_slug'] ?? '');
    $projectID = $projectData['projectID'];
    
    // Get project domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) > 0) {
        $domain_data = fetch_assoc($domain_result);
        $domain = $domain_data['domain'];
    } else {
        $domain = $project_link . '.links.control-center.eu';
    }
    
    // Generate or validate slug
    if ($custom_slug) {
        $slug_check = query("SELECT id FROM link_tracker_links WHERE slug='$custom_slug' AND projectID='$projectID'");
        if (mysqli_num_rows($slug_check) > 0) {
            echo echoJSON('Slug bereits vergeben');
            exit;
        }
        $slug = $custom_slug;
    } else {
        do {
            $slug = generateSlug();
            $slug_check = query("SELECT id FROM link_tracker_links WHERE slug='$slug' AND projectID='$projectID'");
        } while(mysqli_num_rows($slug_check) > 0);
    }
    
    $result = query("INSERT INTO link_tracker_links (projectID, title, target_url, slug) VALUES ('$projectID', '$title', '$target_url', '$slug')");
    
    if ($result) {
        $short_url = "https://$slug.$domain/";
        echo echoJSON(['success' => true, 'short_url' => $short_url, 'slug' => $slug]);
    } else {
        echo echoJSON('Fehler beim Erstellen des Links');
    }
}

elseif (isset($_POST['getLinks'])) {
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
    
    $projectID = $projectData['projectID'];
    
    // Get domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) > 0) {
        $domain_data = fetch_assoc($domain_result);
        $domain = $domain_data['domain'];
    } else {
        $domain = $project_link . '.links.control-center.eu';
    }
    
    $links = [];
    $result = query("SELECT l.*, COUNT(v.id) as visits, MAX(v.visited_at) as last_visit FROM link_tracker_links l 
                     LEFT JOIN link_tracker_visits v ON l.id = v.link_id 
                     WHERE l.projectID='$projectID' 
                     GROUP BY l.id 
                     ORDER BY l.created_at DESC");
    
    while ($row = fetch_assoc($result)) {
        $row['short_url'] = "https://{$row['slug']}.$domain/";
        $links[] = $row;
    }
    
    echo echoJSON(['success' => true, 'links' => $links]);
}

elseif (isset($_POST['deleteLink'])) {
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
    
    $projectID = $projectData['projectID'];
    
    $result = query("DELETE FROM link_tracker_links WHERE id='$link_id' AND projectID='$projectID'");
    
    if ($result) {
        echo echoJSON('Link erfolgreich gelöscht');
    } else {
        echo echoJSON('Fehler beim Löschen des Links');
    }
}

elseif (isset($_POST['getAnalytics'])) {
    $project_link = escape_string($_POST['project']);
    $period = escape_string($_POST['period'] ?? '30');
    
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
    
    $projectID = $projectData['projectID'];
    
    // Get analytics data
    $analytics = [];
    $result = query("SELECT v.*, l.title, l.slug, l.target_url 
                     FROM link_tracker_visits v 
                     JOIN link_tracker_links l ON v.link_id = l.id 
                     WHERE l.projectID='$projectID' 
                     AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY) 
                     ORDER BY v.visited_at DESC");
    
    while ($row = fetch_assoc($result)) {
        $analytics[] = $row;
    }
    
    echo echoJSON(['success' => true, 'analytics' => $analytics]);
}

else {
    echo echoJSON('Ungültige Aktion');
}
?>