<?php
include "head.php";

if(isset($_POST['getProductByCode']) && isset($_POST['code'])){
    $code = escape_string($_POST['code']);
    $product = query("SELECT * FROM control_center_products WHERE `code`=$code");
    if(mysqli_num_rows($product) != 1){
        echo "error 1 - To many results";
    }else{
        $product = fetch_assoc($product);
        $json['title'] = $product['title'];
        $json['price'] = $product['price'];
        echo echoJSON($json);
    }
}