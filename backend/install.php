<?php
include "head.php";

if (isset($_POST['install']) && isset($_POST['moduleID']) && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $moduleID = escape_string($_POST['moduleID']);
    $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
    if (mysqli_num_rows($module) == 1) {
        $module = fetch_assoc($module);
        query("INSERT INTO control_center_modules (`icon`, `name`, `project`) VALUES ('" . $module['tool_icon'] . "', '" . $module['display_name'] . "', '$project')");
    } else {
        echo "error 1";
    }
}

if (isset($_POST['deinstall']) && isset($_POST['moduleID']) && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $moduleID = escape_string($_POST['moduleID']);
    $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");
    if (mysqli_num_rows($module) == 1) {
        $module = fetch_assoc($module);
        $name = $module['name'];
        if (query("DELETE FROM control_center_modules WHERE name='$name' AND project='$project'")) {
            echo "Module '" . $module['name'] . "' deleted successfully";
        }
    } else {
        echo "error 1";
    }
}
?>