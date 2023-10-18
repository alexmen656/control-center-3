<?php
include "head.php";

function getName($n)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randomString = '';

    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

if (isset($_POST['new_dashboard']) && isset($_POST['project'])) { //&& isset($_POST['json'])
    //$json = json_encode($_POST['json']);
    $projectName = escape_string($_POST['project']);
    $projectID = query("SELECT * FROM projects WHERE link='$projectName'");
    $name = "Dashboard-" . getName(7);
    if (mysqli_num_rows($projectID) == 1) {
        $projectID = fetch_assoc($projectID)['projectID'];
        $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;
        $dashboardName = strtolower($name);
        $insert = query("INSERT INTO control_center_dashboards VALUES (0, '$dashboardName', '[]', '$projectName', NOW(), NOW())"); //$json
        if ($insert) {
            $link = str_replace(" ", "-", $name).strtolower();
            $query = query("INSERT INTO project_tools VALUES (0,'bar-chart-outline','$name', '$link',0,'','$projectID')");
            if ($query) {
                $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($projectName)) . "/dashboard/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($name));
                query("INSERT INTO control_center_pages VALUES (0,'$url', 'true','bar-chart-outline','$name', '', 0)");
                echo "success";
            } else {
                echo "error 2";
            }
        } else {
            echo "error 3";
        }
    } else {
        echo "error 1";
    }
} elseif (isset($_POST['get_dashboard']) && isset($_POST['dashboard']) && isset($_POST['project'])) {

    $dashboardName = escape_string($_POST['dashboard']);
    $projectName = escape_string($_POST['project']);
    $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
    $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
    $existingDataArray = json_decode($existingJson, true);
    $json = $existingDataArray;//["dashboard_json"]
    echo echoJson($json);

} elseif (isset($_POST['new_chart']) && isset($_POST['dashboard']) && isset($_POST['project'])) {

    $dashboardName = escape_string($_POST['dashboard']);
    $projectName = escape_string($_POST['project']);
    $chartData = $_POST['json'];

    $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
    $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
    $existingDataArray = json_decode($existingJson, true);

    $newDataArray = json_decode($chartData, true);
    $mergedDataArray = array_merge($existingDataArray, $newDataArray);
    $updatedJson = json_encode($mergedDataArray);

    $updateJsonQuery = "UPDATE control_center_dashboards SET dashboard_json = '$updatedJson' WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
    $updateResult = query($updateJsonQuery);

    if ($updateResult) {
        echo "success";
    } else {
        echo "error updating JSON data";
    }
} elseif (isset($_POST['delete_chart']) && isset($_POST['dashboard']) && isset($_POST['project']) && isset($_POST['chart_index'])) {
    $dashboardName = escape_string($_POST['dashboard']);
    $projectName = escape_string($_POST['project']);
    $chartIndex = (int)$_POST['chart_index']; // Convert to integer for safety

    $fetchJsonQuery = "SELECT dashboard_json FROM control_center_dashboards WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
    $existingJson = fetch_assoc(query($fetchJsonQuery))['dashboard_json'];
    $existingDataArray = json_decode($existingJson, true);

    // Check if the chart index exists in the array
    if (array_key_exists($chartIndex, $existingDataArray)) {
        // Remove the chart at the specified index
        unset($existingDataArray[$chartIndex]);

        // Re-index the array to avoid gaps
        $existingDataArray = array_values($existingDataArray);

        // Encode the updated JSON
        $updatedJson = json_encode($existingDataArray);

        $updateJsonQuery = "UPDATE control_center_dashboards SET dashboard_json = '$updatedJson' WHERE dashboard_name = '$dashboardName' AND project = '$projectName'";
        $updateResult = query($updateJsonQuery);

        if ($updateResult) {
            echo "success";
        } else {
            echo "error updating JSON data";
        }
    } else {
        echo "Chart index not found in the JSON data";
    }
}
