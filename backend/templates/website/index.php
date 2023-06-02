<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[{[pName]}]</title>
</head>
<body>
<?php
header("Content-type:text/html");
include '/www/paxar/components/php_head.php';;
$query = query("SELECT * FROM project_components WHERE projectID='[{[pID]}]'");
$html = file_get_contents('main.php');
for ($i = 1; $i <= 10; $i++) {
foreach($query as $q){
    //for ($ii = 1; $ii <= 10; $ii++) {
$html = str_replace("{{".$q['code']."}}", file_get_contents($q['file']), $html);
$html = str_replace(";".$q['code'].";", "/[{[pLink]}]/".$q['file'], $html);

    //}
}
}
echo $html;
?> 
</body>
</html>
