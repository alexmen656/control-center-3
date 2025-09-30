<?php
// Link Tracker Redirect Handler - Updated for Vercel Node.js Integration
include "config.php";
include "db_connection.php";
include "functions.php";

// Enable CORS for Node.js requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Analytics functions
function getCountryFromIP($ip) {
    if ($ip == 'unknown' || empty($ip) || $ip == '127.0.0.1') return 'XX';
    
    // Use ip-api.com for free geolocation
    $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
    if ($response) {
        $data = json_decode($response, true);
        return $data['countryCode'] ?? 'XX';
    }
    return 'XX';
}

function getDeviceType($userAgent) {
    if (preg_match('/tablet|ipad/i', $userAgent)) {
        return 'Tablet';
    } elseif (preg_match('/mobile|android|iphone|ipod/i', $userAgent)) {
        return 'Mobile';
    } else {
        return 'Desktop';
    }
}

function getBrowser($userAgent) {
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    return 'Other';
}

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
    error_log("User Agent: " . $userAgent);
    error_log("Requested Path: " . $requestedPath);
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

// Handle API requests from Vercel Node.js
if (isset($_POST['processRedirect'])) {
    $domain = escape_string($_POST['domain']);
    $path = escape_string($_POST['path']);
    $visitor_ip = escape_string($_POST['visitor_ip']);
    $user_agent = escape_string($_POST['user_agent']);
    $referer = escape_string($_POST['referer']);
    $requested_url = escape_string($_POST['requested_url'] ?? '');
    
    // Find link by domain (either main slug or custom domain)
    $link = null;
    
    // Check if it's a custom domain first
    $custom_domain_result = query("
        SELECT ltl.*, ltcd.full_domain 
        FROM link_tracker_custom_domains ltcd
        JOIN link_tracker_links ltl ON ltcd.link_id = ltl.id
        WHERE ltcd.full_domain = '$domain'
        LIMIT 1
    ");
    
    if (mysqli_num_rows($custom_domain_result) > 0) {
        $link = fetch_assoc($custom_domain_result);
    } else {
        // Check for main project domain
        $parts = explode('.', $domain);
        $slug = $parts[0] ?? '';
        
        if ($slug && $path) {
            // Use path as slug for main domain
            $result = query("SELECT * FROM link_tracker_links WHERE slug='$path' LIMIT 1");
        } else {
            // Use subdomain as slug  
            $result = query("SELECT * FROM link_tracker_links WHERE slug='$slug' LIMIT 1");
        }
        
        if (mysqli_num_rows($result) > 0) {
            $link = fetch_assoc($result);
        }
    }
    
    if (!$link) {
        echo json_encode(['success' => false, 'message' => 'Link not found']);
        exit;
    }
    
    // Get analytics data
    $country = getCountryFromIP($visitor_ip);
    $device_type = getDeviceType($user_agent);
    $browser = getBrowser($user_agent);  
    $platform = getPlatform($user_agent);
    $is_bot = isBotRequest($user_agent, $requested_url) ? 1 : 0;
    
    // Insert visit record with full analytics including bot detection
    query("INSERT INTO link_tracker_visits (link_id, ip_address, user_agent, referer, country, device_type, browser, platform, requested_url, is_bot) 
           VALUES ('{$link['id']}', '$visitor_ip', '$user_agent', '$referer', '$country', '$device_type', '$browser', '$platform', '$requested_url', $is_bot)");
    
    echo json_encode([
        'success' => true, 
        'redirect_url' => $link['target_url'],
        'link_id' => $link['id'],
        'is_bot' => $is_bot
    ]);

    error_log("Redirecting to: " . $link['target_url']);
    error_log('is_bot: ' . $is_bot);
    exit;
}

// Legacy direct redirect (fallback for direct PHP access)
$host = $_SERVER['HTTP_HOST'] ?? '';
$path = $_SERVER['REQUEST_URI'] ?? '';
$path = ltrim($path, '/');

// Find link by domain or path
$link = null;

// Check custom domain first
$custom_domain_result = query("
    SELECT ltl.*, ltcd.full_domain 
    FROM link_tracker_custom_domains ltcd
    JOIN link_tracker_links ltl ON ltcd.link_id = ltl.id
    WHERE ltcd.full_domain = '$host'
    LIMIT 1
");

if (mysqli_num_rows($custom_domain_result) > 0) {
    $link = fetch_assoc($custom_domain_result);
} else {
    // Check main domain with path as slug
    $parts = explode('.', $host);
    $slug = $path ?: ($parts[0] ?? '');
    
    if ($slug) {
        $result = query("SELECT * FROM link_tracker_links WHERE slug='$slug' LIMIT 1");
        if (mysqli_num_rows($result) > 0) {
            $link = fetch_assoc($result);
        }
    }
}

if (!$link) {
    http_response_code(404);
    echo "Link not found";
    exit;
}

// Track the visit
$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : ($_SERVER['REMOTE_ADDR'] ?? '');
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$requested_url = $_SERVER['REQUEST_URI'] ?? '';

// Get analytics data
$country = getCountryFromIP($ip);
$device_type = getDeviceType($user_agent);
$browser = getBrowser($user_agent);  
$platform = getPlatform($user_agent);
$is_bot = isBotRequest($user_agent, $path) ? 1 : 0;

// Escape strings for DB
$user_agent_escaped = escape_string($user_agent);
$referer_escaped = escape_string($referer);
$requested_url_escaped = escape_string($requested_url);

// Insert visit record with bot detection
query("INSERT INTO link_tracker_visits (link_id, ip_address, user_agent, referer, country, device_type, browser, platform, requested_url, is_bot) 
       VALUES ('{$link['id']}', '$ip', '$user_agent_escaped', '$referer_escaped', '$country', '$device_type', '$browser', '$platform', '$requested_url_escaped', '$is_bot')");

// Redirect to target URL
header("Location: " . $link['target_url'], true, 301);
exit;
