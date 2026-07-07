<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');
include 'db_connection.php';
include 'functions.php';include 'helper.php';

if (isset($_POST['getDataById']) && isset($_POST['id']) && isset($_POST['project']) && isset($_POST['table'])) {
    $project = escape_string($_POST['project']);
    $table_name = escape_string($_POST['table']);
    $id = escape_string($_POST['id']);
    echo echoJson(getData($project, $table_name, $id));
} elseif ($_POST['getTableByName']) {
    $tbName = escape_string($_POST['getTableByName']);
    echo echoJson(getTableByName($tbName));
} elseif (isset($_POST['get_table_data']) && isset($_POST['project']) && isset($_POST['table'])) {
    $table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['table'])));
    $project_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['project'])));
    $table_name = $project_name . "_" . $table_name;
    echo echoJson(get_table_data($table_name, $project_name, $table_name));
} elseif (isset($_POST['delete_entry']) && isset($_POST['entry_id']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $id = escape_string($_POST['entry_id']);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));
    echo echoJson(delete_entry($id, $table_name, $project, $tableName));
} elseif (
    isset($_POST['update_entry']) &&
    isset($_POST['entry_id']) &&
    isset($_POST['table']) &&
    isset($_POST['table_name']) &&
    isset($_POST['project'])
) {
    $id = escape_string($_POST['entry_id']);
    $form = json_decode($_POST['table'], true);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    echo echoJson(update_entry($id, $form, $table_name, $project));
}
?>