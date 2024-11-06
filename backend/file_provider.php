<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
$path = $_GET['path'] ?? '';

if ($path) {
    $file = '/data/filesystem/' . $path;

    if (file_exists($file)) {
        $mimeType = mime_content_type($file);
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            http_response_code(400);
            echo "Invalid file type.";
            exit;
        }
        // echo $mimeType;
        header('Content-Type: ' . $mimeType);
        readfile($file);
        exit;
    } else {
        http_response_code(404);
        echo "File not found.";
        exit;
    }
} else {
    http_response_code(400);
    echo "No file specified.";
    exit;
}