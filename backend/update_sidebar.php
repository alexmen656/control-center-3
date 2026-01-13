<?php
include 'head.php';

if (isset($_POST['project']) && isset($_POST['tools'])) {
    $projectName = $_POST['project'];
    $projectData = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"));

    if (!$projectData) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }

    $projectID = $projectData['projectID'];
    $tools = json_decode($_POST['tools'], true);

    if (is_array($tools)) {
        foreach ($tools as $tool) {
            if (!isset($tool['id']))
                continue;

            $id = intval($tool['id']);
            $name = addslashes($tool['name']);
            $icon = addslashes($tool['icon']);

            query("UPDATE project_tools SET name='$name', icon='$icon' WHERE id='$id' AND projectID='$projectID'");
        }
    }
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
?>