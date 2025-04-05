<?php
include 'head.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Standardmäßig Seite 1, falls kein Parameter übergeben wird
    $page  = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 30;
    $offset = ($page - 1) * $limit;
    
    // Gesamtanzahl der Seiten ermitteln
    $count_sql = "SELECT COUNT(*) AS total FROM control_center_pages";
    $count_result = query($count_sql);
    $count_data = mysqli_fetch_assoc($count_result);
    $total = $count_data['total'];
    $total_pages = ceil($total / $limit);
    
    // Seiten-Datensätze abrufen
    $sql = "SELECT * FROM control_center_pages LIMIT $limit OFFSET $offset";
    $result = query($sql);
    $pages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pages[] = $row;
    }
    
    echo json_encode([
        "status"       => "success", 
        "data"         => $pages, 
        "total_pages"  => $total_pages, 
        "current_page" => $page
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addPage']) && $_POST['addPage'] === 'addPage') {
    // Felder aus POST auslesen und mit escape_string absichern
    $title = isset($_POST['title']) ? escape_string($_POST['title']) : '';
    $url   = isset($_POST['url'])   ? escape_string($_POST['url'])   : '';
    $icon  = isset($_POST['icon'])  ? escape_string($_POST['icon'])  : '';
    // html wird immer als leerer String übergeben
    $html  = "";

    if (empty($title) || empty($url)) {
        echo json_encode(["status" => "error", "message" => "Title and URL are required."]);
        exit;
    }

    // SQL-Abfrage zum Einfügen einer neuen Seite
    $sql = "INSERT INTO control_center_pages (title, url, icon, html) VALUES ('$title', '$url', '$icon', '$html')";
    $result = query($sql);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Page added successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding page."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>