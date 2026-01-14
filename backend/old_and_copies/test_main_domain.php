<?php
// test_main_domain.php
// Test für Haupt-Domain Verbindung

require_once 'config.php';
require_once 'head.php';

echo "<h2>Test: Haupt-Domain Verbindung</h2>";

// Test-Daten
$codespace_id = 16;  // Beispiel Codespace ID
$user_id = 146;      // Beispiel User ID

echo "<h3>Test 1: Haupt-Domain verbinden</h3>";

// POST Data für Haupt-Domain
$postData = [
    'action' => 'connect_domain',
    'codespace_id' => $codespace_id,
    'user_id' => $user_id,
    'is_main' => 'true'
    // Kein subdomain Parameter!
];

echo "<pre>";
echo "POST Data:\n";
print_r($postData);
echo "</pre>";

// Simuliere die Validierung
$subdomain = ''; // Leer bei Haupt-Domain
$is_main = true;

echo "<h3>Validierung:</h3>";
echo "Subdomain: '" . $subdomain . "'<br>";
echo "Is Main: " . ($is_main ? 'true' : 'false') . "<br>";

if (!$is_main && (!$subdomain || !preg_match('/^[a-z0-9-]+$/', $subdomain))) {
    echo "<span style='color: red;'>❌ Validierung fehlgeschlagen</span><br>";
} else {
    echo "<span style='color: green;'>✅ Validierung erfolgreich</span><br>";
}

echo "<h3>Test 2: Subdomain verbinden</h3>";

$subdomain2 = 'api';
$is_main2 = false;

echo "Subdomain: '" . $subdomain2 . "'<br>";
echo "Is Main: " . ($is_main2 ? 'true' : 'false') . "<br>";

if (!$is_main2 && (!$subdomain2 || !preg_match('/^[a-z0-9-]+$/', $subdomain2))) {
    echo "<span style='color: red;'>❌ Validierung fehlgeschlagen</span><br>";
} else {
    echo "<span style='color: green;'>✅ Validierung erfolgreich</span><br>";
}

echo "<h3>Test 3: Ungültige Subdomain</h3>";

$subdomain3 = 'API_INVALID';
$is_main3 = false;

echo "Subdomain: '" . $subdomain3 . "'<br>";
echo "Is Main: " . ($is_main3 ? 'true' : 'false') . "<br>";

if (!$is_main3 && (!$subdomain3 || !preg_match('/^[a-z0-9-]+$/', $subdomain3))) {
    echo "<span style='color: red;'>❌ Validierung fehlgeschlagen (erwartet)</span><br>";
} else {
    echo "<span style='color: green;'>✅ Validierung erfolgreich</span><br>";
}

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
