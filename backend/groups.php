<?php
include 'head.php';

function generateToken($length = 32)
{
  // Zeichen für den Token
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

  // Token initialisieren
  $token = '';

  // Zufällige Zeichen auswählen, um den Token zu generieren
  for ($i = 0; $i < $length; $i++) {
    $token .= $characters[rand(0, strlen($characters) - 1)];
  }

  return $token;
}
// Empfange die Gruppendaten
/*
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

echo json_encode($response);*/


// Annahme: Der Formulareingabe-Name für die Datei ist 'image'
if (isset($_FILES['image']) && isset($_POST['name']) && isset($_POST['participants'])) {
  $file = $_FILES['image'];
  $name = escape_string($_POST['name']);
  $participants = escape_string($_POST['participants']);
  $chatID = generateToken();
  // Hier kannst du weitere Verarbeitungen für die hochgeladene Datei durchführen
  // Zum Beispiel: Datei auf dem Server speichern
  $targetDirectory = "images/groups/";
  $targetFile = $targetDirectory . $chatID . "." . pathinfo($file['name'])['extension'];
echo $targetFile;
  if (move_uploaded_file($file["tmp_name"], $targetFile)) {
    echo "Die Datei wurde erfolgreich hochgeladen.";
    query("INSERT INTO control_center_chats VALUES (0, '$participants', '2', '$chatID', NOW())");
    query("INSERT INTO control_center_groups VALUES (0, '$targetFile', '$name', '$chatID')");
    query("INSERT INTO control_center_pages VALUES (0, 'messages/$chatID', 'false', 'chatbubbles-outline', '$name', '-', '223')");

  } else {
    echo "Fehler beim Hochladen der Datei.";
  }
} else {
  echo "Es wurde keine Datei hochgeladen.";
}
?>