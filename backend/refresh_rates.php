<?php
include '/www/control-center/head.php'
// refresh_rates.php

// Definiere die Refresh-Intervalle
$refreshIntervals = ['hourly', 'daily', 'weekly', 'monthly', 'yearly'];

// Wähle zufällig ein Intervall aus
$randomInterval = $refreshIntervals[array_rand($refreshIntervals)];

// Gib das ausgewählte Intervall als JSON zurück
header('Content-Type: application/json');
echo json_encode($randomInterval);
?>
<?php
// refresh_rates.php

// Definiere die Refresh-Intervalle
$refreshIntervals = ['hourly', 'daily', 'weekly'];

// Wähle zufällig ein Intervall aus
$randomInterval = $refreshIntervals[array_rand($refreshIntervals)];

// Gib das ausgewählte Intervall als JSON zurück
echo json_encode($randomInterval);
?>
