<?php
include "config.php";
include "db_connection.php";
include_once "functions.php";

// Get slug from subdomain
$host = $_SERVER['HTTP_HOST'] ?? '';
$parts = explode('.', $host);
$slug = $parts[0] ?? '';

// Find link

$result = query("SELECT * FROM link_tracker_links WHERE slug=? LIMIT 1", [$slug]);
if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo "Link nicht gefunden.";
    exit;
}
$link = fetch_assoc($result);

// Track visit
$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : ($_SERVER['REMOTE_ADDR'] ?? '');
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$device = $user_agent;
query("INSERT INTO link_tracker_visits (link_id, ip, device, referer, visited_at) VALUES (?, ?, ?, ?, NOW())", [$link['id'], $ip, $device, $referer]);

// Redirect
header("Location: " . $link['target_url']);
exit;
