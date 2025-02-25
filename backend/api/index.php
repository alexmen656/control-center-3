<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
include '/www/paxar/components/php_head.php';
include 'helper.php';

if (isset($_POST['getDataById']) && isset($_POST['id']) && isset($_POST['project']) && isset($_POST['form'])) {
    $project = escape_string($_POST['project']);
    $form_name = escape_string($_POST['form']);
    $id = escape_string($_POST['id']);
    echo echoJson(getData($project, $form_name, $id));
} elseif ($_POST['getTableByName']) {
    $tbName = escape_string($_POST['getTableByName']);
    echo echoJson(getTableByName($tbName));
} elseif (isset($_POST['get_form_data']) && isset($_POST['project']) && isset($_POST['form'])) {
    $form_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['form'])));
    $project_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['project'])));
    $table_name = $project_name . "_" . $form_name;
    echo echoJson(get_form_data($form_name, $project_name, $table_name));
} elseif (isset($_POST['delete_entry']) && isset($_POST['entry_id']) && isset($_POST['form_name']) && isset($_POST['project'])) {
    $id = escape_string($_POST['entry_id']);
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));
    echo echoJson(delete_entry($id, $form_name, $project, $tableName));
} elseif (
    isset($_POST['update_entry']) &&
    isset($_POST['entry_id']) &&
    isset($_POST['form']) &&
    isset($_POST['form_name']) &&
    isset($_POST['project'])
) {
    $id = escape_string($_POST['entry_id']);
    $form = json_decode($_POST['form'], true);
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    echo echoJson(update_entry($id, $form, $form_name, $project));
}
?>