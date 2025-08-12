<?php 

$dbhost = "127.0.0.1";
$dbuser = "alex01d01";
$dbpass = "XL2fiPeVH3";
$dbname = "alex01d01";

if(!$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{

	die("failed to connect!");
}

?>