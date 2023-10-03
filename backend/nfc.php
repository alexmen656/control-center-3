<?php
include "head.php";
//print_r($_POST);
if(isset($_POST['hex']) && isset($_POST['entry']) && isset($_POST['form']) && isset($_POST['project'])){

    $hex = escape_string($_POST['hex']);
    $entry = escape_string($_POST['entry']);
    $form = escape_string($_POST['form']);
    $project = escape_string($_POST['project']);
    query("INSERT INTO control_center_nfc (`hex`,`entry_id`,`form`,`project`) VALUES ('$hex','$entry','$form','$project')");

}
?>