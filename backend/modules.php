<?php
include 'head.php';

if (isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $modules = query("SELECT * FROM control_center_modules WHERE project='$project'");
    $i = 0;
    foreach ($modules as $m) {
        $json[$i]['icon'] = $m['icon'];
        $json[$i]['name'] = $m['name'];
        $i++;
    }
    echo echoJSON($json);
}