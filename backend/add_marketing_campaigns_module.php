<?php
// Script to add Marketing Campaigns module to the module store
include "config.php";
include '/www/paxar/components/php_head.php';

$module = [
    "name" => "Marketing Campaigns",
    "tool_icon" => "megaphone-outline",
    "url" => "marketing-campaigns",
    "display_name" => "Marketing Campaigns",
    "ref" => "marketingcampaigns",
    "icon" => "https://alex.polan.sk/control-center/images/appIcons/marketingcampaigns.png",
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
        echo "Marketing Campaigns module already exists in the store.";
    } else {
        $result = query("INSERT INTO module_store_modules (`name`, `tool_icon`, `url`, `display_name`, `ref`, `icon`, `price`) VALUES ('$name', '$tool_icon', '$url', '$display_name', '$ref', '$icon', '$price')");
        
        if ($result) {
            echo "Marketing Campaigns module successfully added to the store!";
        } else {
            echo "Error adding Marketing Campaigns module to the store.";
        }
    }
} else {
    echo "Database functions not available.";
}
?>
