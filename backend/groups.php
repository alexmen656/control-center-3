<?php
include 'head.php';
// Empfange die Gruppendaten
$groupName = $_POST['name'];
$participants = $_POST['participants'];
$imagePath = $_POST['image'];

// Speichere die Gruppendaten in einer Datenbank oder führe andere gewünschte Aktionen aus

// Beispiel: Daten in einer JSON-Datei speichern
$data = [
  'name' => $groupName,
  'participants' => $participants,
  'image' => $imagePath,
];

// Passe den Speicherpfad entsprechend deiner Anforderungen an
$storagePath = '/pfad/zur/json/datei/groups.json';

$currentData = file_get_contents($storagePath);
$currentGroups = json_decode($currentData, true);
$currentGroups[] = $data;

$newData = json_encode($currentGroups);
file_put_contents($storagePath, $newData);

// Sende eine Erfolgsnachricht zurück
$response = [
  'success' => true,
  'message' => 'Group created successfully.',
];

echo json_encode($response);
?>
