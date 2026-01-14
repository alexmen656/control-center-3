<?php
require_once "head.php";

$result = query("DESCRIBE projects");

while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
    echo "<br>";
}
