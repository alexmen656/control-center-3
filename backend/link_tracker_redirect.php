<?php
// Link Tracker Redirect Handler
include "config.php";
include "db_connection.php";

// Analytics functions
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

// Get subdomain from host
$host = $_SERVER['HTTP_HOST'] ?? '';
$parts = explode('.', $host);
$slug = $parts[0] ?? '';

if (!$slug) {
    http_response_code(404);
    echo "Invalid link";
    exit;
}

// Find the link in database
$result = query("SELECT * FROM link_tracker_links WHERE slug='$slug' LIMIT 1");

if (!$result || mysqli_num_rows($result) == 0) {
    http_response_code(404);
    echo "Link not found";
    exit;
}

$link = fetch_assoc($result);

// Track the visit with Cloudflare IP and full analytics
$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : ($_SERVER['REMOTE_ADDR'] ?? '');
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Get analytics data
$country = getCountryFromIP($ip);
$device_type = getDeviceType($user_agent);
$browser = getBrowser($user_agent);  
$platform = getPlatform($user_agent);

// Escape strings for DB
$user_agent = escape_string($user_agent);
$referer = escape_string($referer);
$country = escape_string($country);
$device_type = escape_string($device_type);
$browser = escape_string($browser);
$platform = escape_string($platform);

// Insert visit record with full analytics
query("INSERT INTO link_tracker_visits (link_id, ip_address, user_agent, referer, country, device_type, browser, platform) 
       VALUES ('{$link['id']}', '$ip', '$user_agent', '$referer', '$country', '$device_type', '$browser', '$platform')");

// Redirect to target URL
header("Location: " . $link['target_url'], true, 301);
exit;
