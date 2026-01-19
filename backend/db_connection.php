<?php 

$dbhost = "127.0.0.1";
$dbuser = "alex01d01";
$dbpass = "XL2fiPeVH3";
$dbname = "alex01d01";

if(!$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{

	die("failed to connect!");
}

// Set UTF-8 encoding for proper emoji support
mysqli_set_charset($con, "utf8mb4");

?>