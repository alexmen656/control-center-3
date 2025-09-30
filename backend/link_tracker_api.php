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

// Get country from IP (using simple IP-API service)
function getCountryFromIP($ip) {
    if ($ip == 'unknown' || empty($ip)) return 'XX';
    
    // Use ip-api.com for free geolocation
    $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
    if ($response) {
        $data = json_decode($response, true);
        return $data['countryCode'] ?? 'XX';
    }
    return 'XX';
}

// Detect device type from user agent
function getDeviceType($userAgent) {
    if (preg_match('/tablet|ipad/i', $userAgent)) {
        return 'Tablet';
    } elseif (preg_match('/mobile|android|iphone|ipod/i', $userAgent)) {
        return 'Mobile';
    } else {
        return 'Desktop';
    }
}

// Detect browser from user agent
function getBrowser($userAgent) {
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    return 'Other';
}

// Detect platform from user agent
function getPlatform($userAgent) {
    if (strpos($userAgent, 'Windows') !== false) return 'Windows';
    if (strpos($userAgent, 'Mac') !== false) return 'macOS';
    if (strpos($userAgent, 'Linux') !== false) return 'Linux';
    if (strpos($userAgent, 'Android') !== false) return 'Android';
    if (strpos($userAgent, 'iOS') !== false) return 'iOS';
    return 'Other';
}

// Detect if request is from a bot
function isBotRequest($userAgent, $requestedPath) {
    // Check user agent for bot patterns
    $botPatterns = [
        'bot', 'crawler', 'spider', 'crawl', 'facebook', 'whatsapp', 'telegram',
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot',
        'twitterbot', 'linkedinbot', 'discordbot', 'slackbot', 'curl', 'wget',
        'python-requests', 'scrapy', 'http_request', 'php/', 'go-http-client'
    ];
    
    $userAgentLower = strtolower($userAgent);
    foreach ($botPatterns as $pattern) {
        if (strpos($userAgentLower, $pattern) !== false) {
            return true;
        }
    }
    
    // Check if requesting suspicious paths (anything other than root)
    if ($requestedPath && $requestedPath !== '' && $requestedPath !== '/') {
        $suspiciousPaths = [
            'robots.txt', 'sitemap.xml', 'favicon.ico', '.well-known',
            'admin', 'wp-admin', 'wp-login', 'login', 'phpmyadmin',
            'config', 'backup', 'sql', 'database', 'secrets',
            '.env', '.git', 'node_modules', 'vendor'
        ];
        
        $pathLower = strtolower($requestedPath);
        foreach ($suspiciousPaths as $suspicious) {
            if (strpos($pathLower, $suspicious) !== false) {
                return true;
            }
        }
    }
    
    return false;
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
    country VARCHAR(2),
    device_type VARCHAR(20),
    browser VARCHAR(50),
    platform VARCHAR(50),
    requested_url TEXT,
    is_bot BOOLEAN DEFAULT FALSE,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES link_tracker_links(id) ON DELETE CASCADE
)";

query($linksTable);
query($visitsTable);

// Enable CORS for Node.js requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Log visit from Node.js redirect (no authentication required)
if (isset($_POST['logVisit'])) {
    $link_id = escape_string($_POST['link_id']);
    $visitor_ip = escape_string($_POST['visitor_ip']);
    $user_agent = escape_string($_POST['user_agent']);
    $referer = escape_string($_POST['referer']);
    $domain = escape_string($_POST['domain']);
    $path = escape_string($_POST['path']);
    $requested_url = escape_string($_POST['requested_url'] ?? '');
    
    // Get analytics data
    $country = getCountryFromIP($visitor_ip);
    $device_type = getDeviceType($user_agent);
    $browser = getBrowser($user_agent);
    $platform = getPlatform($user_agent);
    $is_bot = isBotRequest($user_agent, $path) ? 1 : 0;
    
    // Insert visit record with full analytics
    $insert_visit = query("INSERT INTO link_tracker_visits (link_id, ip_address, user_agent, referer, country, device_type, browser, platform, requested_url, is_bot) 
                          VALUES ('$link_id', '$visitor_ip', '$user_agent', '$referer', '$country', '$device_type', '$browser', '$platform', '$requested_url', '$is_bot')");
    
    if ($insert_visit) {
        echo echoJSON(['success' => true, 'message' => 'Visit logged successfully']);
    } else {
        echo echoJSON('Error logging visit');
    }
    exit;
}

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
    $exclude_bots = isset($_POST['exclude_bots']) && $_POST['exclude_bots'] == 'true';
    
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
    
    // Add bot filter to visits count
    $botFilter = $exclude_bots ? 'AND v.is_bot = 0' : '';
    
    $links = [];
    $result = query("SELECT l.*, 
                            COUNT(v.id) as visits, 
                            COUNT(DISTINCT CASE WHEN v.is_bot = 0 OR v.is_bot IS NULL THEN v.ip_address END) as unique_visitors,
                            COUNT(CASE WHEN v.is_bot = 0 OR v.is_bot IS NULL THEN 1 END) as human_visits,
                            COUNT(CASE WHEN v.is_bot = 1 THEN 1 END) as bot_visits,
                            MAX(v.visited_at) as last_visit FROM link_tracker_links l 
                     LEFT JOIN link_tracker_visits v ON l.id = v.link_id $botFilter
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
    $exclude_bots = isset($_POST['exclude_bots']) && $_POST['exclude_bots'] == 'true';
    
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
    
    // Add bot filter to query
    $botFilter = $exclude_bots ? 'AND v.is_bot = 0' : '';
    
    // Get analytics data
    $analytics = [];
    $result = query("SELECT v.*, l.title, l.slug, l.target_url 
                     FROM link_tracker_visits v 
                     JOIN link_tracker_links l ON v.link_id = l.id 
                     WHERE l.projectID='$projectID' 
                     AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY) 
                     $botFilter
                     ORDER BY v.visited_at DESC");
    
    while ($row = fetch_assoc($result)) {
        $analytics[] = $row;
    }
    
    echo echoJSON(['success' => true, 'analytics' => $analytics]);
}

elseif (isset($_POST['getDetailedAnalytics'])) {
    $project_link = escape_string($_POST['project']);
    $period = escape_string($_POST['period'] ?? '30');
    $exclude_bots = isset($_POST['exclude_bots']) && $_POST['exclude_bots'] == 'true';
    
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
    
    // Add bot filter to all queries
    $botFilter = $exclude_bots ? 'AND v.is_bot = 0' : '';
    
    // Country Stats
    $countries = [];
    $country_result = query("SELECT country, COUNT(*) as count 
                            FROM link_tracker_visits v 
                            JOIN link_tracker_links l ON v.link_id = l.id 
                            WHERE l.projectID='$projectID' 
                            AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                            AND country IS NOT NULL 
                            $botFilter
                            GROUP BY country 
                            ORDER BY count DESC 
                            LIMIT 10");
    
    while ($row = fetch_assoc($country_result)) {
        $countries[] = $row;
    }
    
    // Device Stats
    $devices = [];
    $device_result = query("SELECT device_type, COUNT(*) as count 
                           FROM link_tracker_visits v 
                           JOIN link_tracker_links l ON v.link_id = l.id 
                           WHERE l.projectID='$projectID' 
                           AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                           $botFilter
                           GROUP BY device_type 
                           ORDER BY count DESC");
    
    while ($row = fetch_assoc($device_result)) {
        $devices[] = $row;
    }
    
    // Browser Stats
    $browsers = [];
    $browser_result = query("SELECT browser, COUNT(*) as count 
                            FROM link_tracker_visits v 
                            JOIN link_tracker_links l ON v.link_id = l.id 
                            WHERE l.projectID='$projectID' 
                            AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                            $botFilter
                            GROUP BY browser 
                            ORDER BY count DESC 
                            LIMIT 10");
    
    while ($row = fetch_assoc($browser_result)) {
        $browsers[] = $row;
    }
    
    // Platform Stats
    $platforms = [];
    $platform_result = query("SELECT platform, COUNT(*) as count 
                             FROM link_tracker_visits v 
                             JOIN link_tracker_links l ON v.link_id = l.id 
                             WHERE l.projectID='$projectID' 
                             AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                             $botFilter
                             GROUP BY platform 
                             ORDER BY count DESC");
    
    while ($row = fetch_assoc($platform_result)) {
        $platforms[] = $row;
    }
    
    // Daily clicks for timeline
    $timeline = [];
    $timeline_result = query("SELECT DATE(v.visited_at) as date, COUNT(*) as clicks 
                             FROM link_tracker_visits v 
                             JOIN link_tracker_links l ON v.link_id = l.id 
                             WHERE l.projectID='$projectID' 
                             AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                             $botFilter
                             GROUP BY DATE(v.visited_at) 
                             ORDER BY date ASC");
    
    while ($row = fetch_assoc($timeline_result)) {
        $timeline[] = $row;
    }
    
    // Bot statistics
    $bot_stats = [];
    $bot_result = query("SELECT 
                            COUNT(CASE WHEN v.is_bot = 1 THEN 1 END) as bot_visits,
                            COUNT(CASE WHEN v.is_bot = 0 THEN 1 END) as human_visits,
                            COUNT(*) as total_visits
                        FROM link_tracker_visits v 
                        JOIN link_tracker_links l ON v.link_id = l.id 
                        WHERE l.projectID='$projectID' 
                        AND v.visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    
    $bot_stats = fetch_assoc($bot_result);
    
    echo echoJSON([
        'success' => true, 
        'countries' => $countries,
        'devices' => $devices,
        'browsers' => $browsers,
        'platforms' => $platforms,
        'timeline' => $timeline,
        'bot_stats' => $bot_stats
    ]);
}

elseif (isset($_POST['getLinkDetails'])) {
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
    
    // Get link details with stats
    $result = query("SELECT l.*, 
                            COUNT(v.id) as visits, 
                            COUNT(DISTINCT v.ip_address) as unique_visitors,
                            COUNT(CASE WHEN DATE(v.visited_at) = CURDATE() THEN 1 END) as clicks_today,
                            COUNT(CASE WHEN YEARWEEK(v.visited_at) = YEARWEEK(NOW()) THEN 1 END) as clicks_this_week
                     FROM link_tracker_links l 
                     LEFT JOIN link_tracker_visits v ON l.id = v.link_id 
                     WHERE l.id='$link_id' AND l.projectID='$projectID'
                     GROUP BY l.id");
    
    if (mysqli_num_rows($result) == 0) {
        echo echoJSON('Link nicht gefunden');
        exit;
    }
    
    $linkData = fetch_assoc($result);
    
    // Get domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) > 0) {
        $domain_data = fetch_assoc($domain_result);
        $domain = $domain_data['domain'];
    } else {
        $domain = $project_link . '.links.control-center.eu';
    }
    
    $linkData['short_url'] = "https://{$linkData['slug']}.$domain/";
    
    echo echoJSON(['success' => true, 'link' => $linkData]);
}

elseif (isset($_POST['getLinkAnalytics'])) {
    $project_link = escape_string($_POST['project']);
    $link_id = escape_string($_POST['link_id']);
    $period = escape_string($_POST['period'] ?? '30');
    $exclude_bots = isset($_POST['exclude_bots']) && $_POST['exclude_bots'] == 'true';
    
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
    
    // Verify link belongs to project
    $linkCheck = query("SELECT id FROM link_tracker_links WHERE id='$link_id' AND projectID='$projectID'");
    if (mysqli_num_rows($linkCheck) == 0) {
        echo echoJSON('Link nicht gefunden');
        exit;
    }
    
    // Add bot filter to all queries
    $botFilter = $exclude_bots ? 'AND is_bot = 0' : '';
    
    // Country Stats for this link
    $countries = [];
    $country_result = query("SELECT country, COUNT(*) as count 
                            FROM link_tracker_visits 
                            WHERE link_id='$link_id' 
                            AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                            AND country IS NOT NULL 
                            $botFilter
                            GROUP BY country 
                            ORDER BY count DESC 
                            LIMIT 10");
    
    while ($row = fetch_assoc($country_result)) {
        $countries[] = $row;
    }
    
    // Device Stats for this link
    $devices = [];
    $device_result = query("SELECT device_type, COUNT(*) as count 
                           FROM link_tracker_visits 
                           WHERE link_id='$link_id' 
                           AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                           $botFilter
                           GROUP BY device_type 
                           ORDER BY count DESC");
    
    while ($row = fetch_assoc($device_result)) {
        $devices[] = $row;
    }
    
    // Browser Stats for this link
    $browsers = [];
    $browser_result = query("SELECT browser, COUNT(*) as count 
                            FROM link_tracker_visits 
                            WHERE link_id='$link_id' 
                            AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                            $botFilter
                            GROUP BY browser 
                            ORDER BY count DESC 
                            LIMIT 10");
    
    while ($row = fetch_assoc($browser_result)) {
        $browsers[] = $row;
    }
    
    // Platform Stats for this link
    $platforms = [];
    $platform_result = query("SELECT platform, COUNT(*) as count 
                             FROM link_tracker_visits 
                             WHERE link_id='$link_id' 
                             AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                             $botFilter
                             GROUP BY platform 
                             ORDER BY count DESC");
    
    while ($row = fetch_assoc($platform_result)) {
        $platforms[] = $row;
    }
    
    // Daily clicks timeline for this link
    $timeline = [];
    $timeline_result = query("SELECT DATE(visited_at) as date, COUNT(*) as clicks 
                             FROM link_tracker_visits 
                             WHERE link_id='$link_id' 
                             AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
                             $botFilter
                             GROUP BY DATE(visited_at) 
                             ORDER BY date ASC");
    
    while ($row = fetch_assoc($timeline_result)) {
        $timeline[] = $row;
    }
    
    // Recent visits for this link
    $recent_visits = [];
    $recent_result = query("SELECT * FROM link_tracker_visits 
                           WHERE link_id='$link_id' 
                           $botFilter
                           ORDER BY visited_at DESC 
                           LIMIT 50");
    
    while ($row = fetch_assoc($recent_result)) {
        $recent_visits[] = $row;
    }
    
    // Bot statistics for this link
    $bot_stats = [];
    $bot_result = query("SELECT 
                            COUNT(CASE WHEN is_bot = 1 THEN 1 END) as bot_visits,
                            COUNT(CASE WHEN is_bot = 0 THEN 1 END) as human_visits,
                            COUNT(*) as total_visits
                        FROM link_tracker_visits 
                        WHERE link_id='$link_id' 
                        AND visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    
    $bot_stats = fetch_assoc($bot_result);
    
    echo echoJSON([
        'success' => true, 
        'countries' => $countries,
        'devices' => $devices,
        'browsers' => $browsers,
        'platforms' => $platforms,
        'timeline' => $timeline,
        'recent_visits' => $recent_visits,
        'bot_stats' => $bot_stats
    ]);
}

else {
    echo echoJSON('Ungültige Aktion');
}
?>