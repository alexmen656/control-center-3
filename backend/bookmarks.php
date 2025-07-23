<?php
include 'head.php';
$headers = getRequestHeaders();

if (isset($headers['Authorization'])) {
    $token = $headers['Authorization'];
    $payload = SimpleJWT::verify($token, $jwt_secret);
    if (!$payload || empty($payload['sub'])) {
        echo "Token not valid!";
        exit;
    }
    $userID = intval($payload['sub']);
    if (isset($_REQUEST['getBookmarks'])) {
        $bookmarks = query("SELECT * FROM control_center_bookmarks WHERE userID='$userID'");
        $i = 0;
        foreach ($bookmarks as $b) {
            $json[$i]['id'] = $b['id'];
            $json[$i]['icon'] = $b['icon'];
            $json[$i]['title'] = $b['title'];
            $json[$i]['location'] = $b['location'];
            $i++;
        }
        echo echoJson($json);
    } elseif (isset($_REQUEST['newBookmark']) && isset($_REQUEST['title']) && isset($_REQUEST['location'])) {
        $icon = escape_string($_REQUEST['icon']);
        $title = escape_string($_REQUEST['title']);
        $location = escape_string($_REQUEST['location']);
        query("INSERT INTO control_center_bookmarks VALUES (0, '$icon', '$title', '$location', '$userID')");
    } elseif (isset($_REQUEST['deleteBookmark']) && isset($_REQUEST['location'])) {
        $location = escape_string($_REQUEST['location']);
        query("DELETE FROM control_center_bookmarks WHERE location='$location' AND userID='$userID'");
    } elseif (isset($_REQUEST['checkBookmark']) && isset($_REQUEST['location'])) {
        $location = escape_string($_REQUEST['location']);
        $checkQuery = query("SELECT * FROM control_center_bookmarks WHERE location='$location' AND userID='$userID'");
        if (mysqli_num_rows($checkQuery) == 1) {
            echo 'true';
        } else {
            echo 'false';
        }
    }
} else {
    echo "No token provided";
}
