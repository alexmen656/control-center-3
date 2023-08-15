<?php
include "head.php";

if (isset($_POST['create_form']) && isset($_POST['form']) && isset($_POST['name']) && isset($_POST['project'])) {
    $formJSON = escape_string($_POST['form']);
    $formName = escape_string($_POST['name']);
    $project = escape_string($_POST['project']);
    if (query("INSERT INTO form_settings (form_name, form_json, project) VALUES ('$formName', '$formJSON', '$project')")) {
        echo $formName . " Created Successfully!!!";
    }
} elseif (isset($_POST['get_form']) && isset($_POST['project']) && isset($_POST['form'])) {
    $form_name = escape_string($_POST['form']);
    $project = escape_string($_POST['project']);
    $query = query("SELECT * FROM form_settings WHERE form_name='$form_name' AND project='$project'");
    if (mysqli_num_rows($query) > 0) {
        $form = fetch_assoc($query);
        $json['id'] = $form['form_id'];
        $json['form'] = json_decode($form['form_json'], true);
        $json['createdOn'] = $form['created_at'];
        echo echoJson(($json));
    }
}
?>
