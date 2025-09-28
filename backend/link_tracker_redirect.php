<?php
// Link Tracker Redirect Handler
include "config.php";
include "db_connection.php";

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

// Track the visit with Cloudflare IP
$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : ($_SERVER['REMOTE_ADDR'] ?? '');
$user_agent = escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
$referer = escape_string($_SERVER['HTTP_REFERER'] ?? '');

// Insert visit record
query("INSERT INTO link_tracker_visits (link_id, ip_address, user_agent, referer) VALUES ('{$link['id']}', '$ip', '$user_agent', '$referer')");

// Redirect to target URL
header("Location: " . $link['target_url'], true, 301);
exit;
