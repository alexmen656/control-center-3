<?php
// Link Tracker Redirect Handler
// This file should be placed in your project domain's web root
// and configured to handle all requests to short links

include "../backend/head.php";

// Get the requested path
$requestPath = $_SERVER['REQUEST_URI'] ?? '';
$slug = trim($requestPath, '/');

// Remove query parameters
if (strpos($slug, '?') !== false) {
    $slug = substr($slug, 0, strpos($slug, '?'));
}

if (empty($slug)) {
    // Redirect to main site or show 404
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
    exit;
}

// Cloudflare IP detection function
function getClientIpAddress() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    return $ip;
}

// Simple GeoIP function
function getCountryFromIp($ip) {
    // Simple IP to country detection
    // In production, use a proper GeoIP service
    
    // Check if IP is from Germany (example)
    if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return 'DE'; // Local network
    }
    
    // Use a simple API call for real IPs
    try {
        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
        if ($response) {
            $data = json_decode($response, true);
            return $data['countryCode'] ?? 'UN';
        }
    } catch (Exception $e) {
        // Fallback
    }
    
    // Fallback to unknown
    return 'UN';
}

// Get tracking data
function getTrackingData() {
    $ip = getClientIpAddress();
    return [
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referer' => $_SERVER['HTTP_REFERER'] ?? '',
        'country' => getCountryFromIp($ip),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Find the link
$slug = escape_string($slug);
$linkResult = query("
    SELECT * FROM link_tracker_links 
    WHERE slug = '$slug' AND status = 'active'
    AND (expires_at IS NULL OR expires_at > NOW())
");

if (mysqli_num_rows($linkResult) === 0) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Link nicht gefunden</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error { color: #ff4444; }
        </style>
    </head>
    <body>
        <h1 class='error'>Link nicht gefunden</h1>
        <p>Der angeforderte Link existiert nicht oder ist abgelaufen.</p>
    </body>
    </html>";
    exit;
}

$link = fetch_assoc($linkResult);

// Track the click
$trackingData = getTrackingData();
$linkId = $link['id'];
$ipAddress = escape_string($trackingData['ip_address']);
$userAgent = escape_string($trackingData['user_agent']);
$referer = escape_string($trackingData['referer']);
$country = escape_string($trackingData['country']);

query("
    INSERT INTO link_tracker_analytics 
    (link_id, ip_address, user_agent, referer, country) 
    VALUES 
    ('$linkId', '$ipAddress', '$userAgent', '$referer', '$country')
");

// Optional: Add click delay for analytics (uncomment if needed)
// usleep(100000); // 100ms delay

// Redirect to target URL
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $link['target_url']);
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
exit;
?>