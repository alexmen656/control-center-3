<?php
include 'head.php';

$modules = query("SELECT * FROM control_center_modules");
$i=0;
foreach($modules as $m ){
    $json[$i]['icon'] = $m['icon'];
    $json[$i]['name'] = $m['name'];
    $i++;
}
echo echoJSON($json);