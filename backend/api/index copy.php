<?php
/*$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
//echo $host_domain;
if (!in_array($host_domain, $allowed_origins, false)) {
    header('HTTP/1.0 403 Forbidden');
    die('You are not allowed to access this.');
}
*/


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

    if ($form) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));
        $updates = array();

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = escape_string($fieldName);
            $fieldValue = escape_string($fieldValue);
            $updates[] = "$fieldName = '$fieldValue'";
        }

        $updatesStr = implode(', ', $updates);

        $sql = "UPDATE $tableName SET $updatesStr WHERE id='$id'";

        if (query($sql)) {
            echo "Entry updated successfully!";
        } else {
            echo "Error updating entry!";
        }
    }
}
//echo echoJson($json);
?>