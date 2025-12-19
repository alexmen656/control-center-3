<?php
/**
 * Script to add Newsletter module to the module store
 */
include "config.php";
include '/www/paxar/components/php_head.php';

$module = [
    "name" => "Newsletter",
    "tool_icon" => "mail-outline",
    "url" => "newsletter",
    "display_name" => "Newsletter",
    "ref" => "newsletter",
    "icon" => "https://alex.polan.sk/control-center/images/appIcons/newsletter.png",
    "price" => "0"
];

if (function_exists('escape_string')) {
    $name = escape_string($module["name"]);
    $tool_icon = escape_string($module["tool_icon"]);
    $url = escape_string($module["url"]);
    $display_name = escape_string($module["display_name"]);
    $ref = escape_string($module["ref"]);
    $icon = escape_string($module["icon"]);
    $price = escape_string($module["price"]);
    
    // Check if module already exists
    $existing = query("SELECT id FROM module_store_modules WHERE ref = '$ref'");
    
    if (mysqli_num_rows($existing) > 0) {
        echo "Newsletter module already exists in the store.\n";
        echo "Module ID: " . mysqli_fetch_assoc($existing)['id'] . "\n";
    } else {
        $result = query("INSERT INTO module_store_modules (`name`, `tool_icon`, `url`, `display_name`, `ref`, `icon`, `price`) VALUES ('$name', '$tool_icon', '$url', '$display_name', '$ref', '$icon', '$price')");
        
        if ($result) {
            echo "✓ Newsletter module successfully added to the store!\n";
            $id = mysqli_insert_id($GLOBALS['conn']);
            echo "Module ID: $id\n";
        } else {
            echo "✗ Error adding Newsletter module to the store.\n";
        }
    }
} else {
    echo "✗ Database functions not available.\n";
}
?>
