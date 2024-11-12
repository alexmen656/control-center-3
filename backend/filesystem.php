<?php
include 'head.php';

function getProjectDirectoryStructure($projectName, $parentId = 0)
{
    $projectData = fetch_assoc(query("SELECT * FROM projects WHERE link='$projectName'"));
    if ($projectData && isset($projectData['projectID'])) {
        $projectID = $projectData['projectID'];
    } else {
        return [];
    }

    if ($parentId == 0) {
        $parentId = fetch_assoc(query("SELECT * FROM project_filesystem WHERE name = '' AND projectID = '$projectID'"))['id'];
        if (!$parentId) {
            return [];
        }
    }

    $result = [];
    $query = query("SELECT * FROM project_filesystem WHERE parent = $parentId AND projectID = '$projectID'");
    while ($row = $query->fetch_assoc()) {
        if ($row['type'] == 0) { // Folder
            $result[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'folder',
                'children' => getProjectDirectoryStructure($row['id'], $projectName)
            ];
        } else { // File
            $result[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'file',
                'location' => $row['location']
            ];
        }
    }
    return $result;
}

function getDirectoryStructure($parentId = 0)
{
    $result = [];
    $query = query("SELECT * FROM control_center_filesystem WHERE parent = $parentId");
    while ($row = $query->fetch_assoc()) {
        if ($row['type'] == 0) { // Folder
            $result[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'folder',
                'children' => getDirectoryStructure($row['id'])
            ];
        } else { // File
            $result[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'file',
                'location' => $row['location']
            ];
        }
    }
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['directory']) && isset($_POST['name'])) {
        if (isset($_POST['project'])) {
            $project = escape_string($_POST['project']);
            $projectID = fetch_assoc(query("SELECT * FROM projects WHERE link='$project'"))['projectID'];
            if ($projectID == null) {
                return [];
            }








            $dir = '/data/project_filesystems/' . $projectID; // Verzeichnis festlegen
            $name = escape_string($_POST['name']);
            $parentName = escape_string($_POST['directory']);

            // Parent ID aus der Datenbank abfragen
            $parentQuery = query("SELECT id FROM project_filesystem WHERE name = '$parentName' AND projectID = '$projectID'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;
            // Überprüfen, ob es sich um eine Datei oder einen Ordner handelt
            if (isset($_FILES["files"])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    //$name2 = $_FILES['files']['name'][$key];
                    //$file_info = pathinfo($name2, PATHINFO_EXTENSION);
                    $fileName = $name; //. "." . $file_info;
                    $file_destination = $dir . '/' . $parentName . '/' . $fileName;
                    move_uploaded_file($tmp_name, $file_destination);
                    $file_destination = $parentName . '/' . $fileName;
                    $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$fileName', '$file_destination', $parentId, 1, '$projectID')");
                    if (!$insert) {
                        echo "error 1";
                        exit;
                    }
                }
            } else {
                // Ordner erstellen
                $folderPath = $dir . '/' . $parentName . '/' . $name;
                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                    $folderLocation = $parentName . '/' . $name;
                    $insert = query("INSERT INTO project_filesystem (name, location, parent, type, projectID) VALUES ('$name', '$folderLocation', $parentId, 0, '$projectID')");
                    if (!$insert) {
                        echo "error 2";
                        exit;
                    }
                    echo "SUCCESS";
                } else {
                    echo "error 3"; // Ordner existiert bereits
                }
            }
























        } else {
            $dir = '/data/filesystem'; // Verzeichnis festlegen
            $name = escape_string($_POST['name']);
            $parentName = escape_string($_POST['directory']);

            // Parent ID aus der Datenbank abfragen
            $parentQuery = query("SELECT id FROM control_center_filesystem WHERE name = '$parentName'");
            $parentId = $parentQuery ? $parentQuery->fetch_assoc()['id'] : 0;

            // Überprüfen, ob es sich um eine Datei oder einen Ordner handelt
            if (isset($_FILES["files"])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    //$name2 = $_FILES['files']['name'][$key];
                    //$file_info = pathinfo($name2, PATHINFO_EXTENSION);
                    $fileName = $name; //. "." . $file_info;
                    $file_destination = $dir . '/' . $parentName . '/' . $fileName;
                    move_uploaded_file($tmp_name, $file_destination);
                    $file_destination = $parentName . '/' . $fileName;
                    $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$fileName', '$file_destination', $parentId, 1)");
                    if (!$insert) {
                        echo "error 1";
                        exit;
                    }
                }
            } else {
                // Ordner erstellen
                $folderPath = $dir . '/' . $parentName . '/' . $name;
                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                    $folderLocation = $parentName . '/' . $name;
                    $insert = query("INSERT INTO control_center_filesystem (name, location, parent, type) VALUES ('$name', '$folderLocation', $parentId, 0)");
                    if (!$insert) {
                        echo "error 2";
                        exit;
                    }
                    echo "SUCCESS";
                } else {
                    echo "error 3"; // Ordner existiert bereits
                }
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['project'])) {
        $project = escape_string($_GET['project']);
        $structure = getProjectDirectoryStructure($project);
        echo json_encode($structure);
    } else {
        $structure = getDirectoryStructure();
        header('Content-Type: application/json');
        echo json_encode($structure);
    }
}
