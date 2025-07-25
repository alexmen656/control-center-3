
<?php
// mini script: add 3 new modules to module_store_modules
include "config.php";
include '/www/paxar/components/php_head.php';

$modules = [
    [
        "name" => "Telegram Bot",
        "tool_icon" => "paper-plane-outline",
        "url" => "telegram-bot",
        "display_name" => "Telegram Bot",
        "ref" => "telegrambot",
        "icon" => "https://alex.polan.sk/control-center/images/appIcons/telegrambot.png",
        "price" => "0"
    ],
    [
        "name" => "My Tasks",
        "tool_icon" => "list-outline",
        "url" => "my-tasks",
        "display_name" => "My Tasks",
        "ref" => "mytasks",
        "icon" => "https://alex.polan.sk/control-center/images/appIcons/mytasks.png",
        "price" => "0"
    ],
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

echo "2 modules added.";
