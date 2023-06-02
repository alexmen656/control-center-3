<?php
include "head.php";

// Get data from POST request
if(isset($_POST['name']) && isset($_POST['description'])){
    $name = escape_string($_POST['name']);
    echo $name;
    $description = escape_string($_POST['description']);
    $examples = escape_string($_POST['examples']);
    $function = str_replace(" ","-",strtolower($name));
    $sql = "INSERT INTO control_center_info_pages (function, name, description, examples)
    VALUES ('$function', '$name', '$description', '$examples')";
    
    if (query($sql) === TRUE) {
        $url = 'info/'.$function;
        $name = "Docs - ".$name;
        query("INSERT INTO control_center_pages VALUES (0,'$url','document-text-outline','$name','',0)");
        echo json_encode(array('message' => 'Function created'));
    } else {
        echo json_encode(array('message' => 'Error: ' . $sql . '<br>' . $conn->error));
    }
}

?>
