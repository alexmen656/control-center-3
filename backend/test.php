<?php
include 'head.php';
// Erhalten Sie den Request-Body als String
$request_body = $_POST['list'];

// Konvertieren Sie den JSON-String in ein PHP-Array oder Objekt
$data = json_decode($request_body, true);

// Überprüfen Sie das Array oder Objekt
if ($data !== null) {
  foreach($data as $d => $val){
   // echo $d." ".$val."<br><br>";
    if(query("UPDATE project_tools SET `order`='$val' WHERE id='$d'")){
        echo "UPDATE project_tools SET order=$val WHERE id=$d <br>";
    };
  }
} else {
  echo "Fehler beim Dekodieren des JSON-Strings.";
}
