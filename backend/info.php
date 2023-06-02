<?php
include "head.php";
/*
if(isset($_REQUEST['function'])){
    $function = escape_string($_REQUEST['function']);
// SQL-Abfrage zum Abrufen aller Datensätze aus der Tabelle
$sql = "SELECT * FROM control_center_info_pages WHERE function='$function'";
$result = query($sql);


// Datensätze in ein assoziatives Array einfügen
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $data = $row;
  }
} 

}else{
// SQL-Abfrage zum Abrufen aller Datensätze aus der Tabelle
$sql = "SELECT * FROM control_center_info_pages";
$result = query($sql);
$data = array();

// Datensätze in ein assoziatives Array einfügen
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    array_push($data, $row);
  }
} 
}

// JSON-Formatierung des assoziativen Arrays
echo json_encode($data);*/
?>







<?php



// Überprüfen, ob eine Funktion angefordert wurde
if(isset($_GET['function'])) {
  $function = $_GET['function'];
} else {
  $response = array("error" => "No function requested");
  echo json_encode($response);
  exit();
}

// SQL-Abfrage vorbereiten
$sql = "SELECT * FROM control_center_info_pages WHERE function = '".$function."'";
$result = query($sql);

// Prüfen, ob eine Zeile zurückgegeben wurde
if ($result->num_rows > 0) {
  // Ausgabe der Daten als JSON
  $row = $result->fetch_assoc();

  $response = array(
    "name" => $row["name"],
    "description" => $row["description"],
    "examples" => json_decode($row["examples"], true)
  );
  echo json_encode($response);

} else {
  $response = array("error" => "Function not found");
  echo json_encode($response);
}

?>
