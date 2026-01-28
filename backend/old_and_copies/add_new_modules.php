
<?php
// mini script: add 3 new modules to module_store_modules
require_once "config.php";
include 'db_connection.php';
include 'functions.php';
$modules = [
    [
        "name" => "App Store Connect Dashboard",
        "tool_icon" => "logo-apple-appstore",
        "url" => "appstore-connect",
        "display_name" => "App Store Connect Dashboard",
        "ref" => "appstoreconnect",
        "icon" => "https://api.fringelo.com/images/appIcons/appstoreconnect.png",
        "price" => "0"
    ],
    [
        "name" => "App User Management",
        "tool_icon" => "people-outline",
        "url" => "app-user-management",
        "display_name" => "App User Management",
        "ref" => "appusermanagement",
        "icon" => "https://api.fringelo.com/images/appIcons/appusermanagement.png",
        "price" => "0"
    ],
    [
        "name" => "Monaco Editor",
        "tool_icon" => "laptop-outline",
        "url" => "https://monaco-editor.example.com",
        "display_name" => "Monaco Editor",
        "ref" => "monacoeditor",
        "icon" => "https://api.fringelo.com/images/appIcons/monacoeditor.png",
        "price" => "0"
    ]
];

foreach ($modules as $m) {
    if (function_exists('escape_string')) {
        $name = escape_string($m["name"]);
        $tool_icon = escape_string($m["tool_icon"]);
        $url = escape_string($m["url"]);
        $display_name = escape_string($m["display_name"]);
        $ref = escape_string($m["ref"]);
        $icon = escape_string($m["icon"]);
        $price = escape_string($m["price"]);
        query("INSERT INTO module_store_modules (`name`, `tool_icon`, `url`, `display_name`, `ref`, `icon`, `price`) VALUES ('$name', '$tool_icon', '$url', '$display_name', '$ref', '$icon', '$price')");
    }
}

echo "3 modules added.";
