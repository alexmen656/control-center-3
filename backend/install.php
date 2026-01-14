<?php
require_once "head.php";

function generateApiKey()
{
    return 'API_' . bin2hex(random_bytes(32));
}

// Add module directly to project
if (isset($_POST['install']) && isset($_POST['moduleID']) && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $moduleID = escape_string($_POST['moduleID']);
    $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");

    if (mysqli_num_rows($module) == 1) {
        $module = fetch_assoc($module);
        $toolName = $module['display_name'];
        $toolIcon = $module['tool_icon'];

        // Get project ID
        $projectID = query("SELECT * FROM projects WHERE link='$project'");
        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];

            // Check if module is already added
            $link = strtolower(str_replace(['Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', ' '], ['a', 'a', 'u', 'u', 'o', 'o', '-'], $toolName));
            $existing = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'");

            if (mysqli_num_rows($existing) > 0) {
                echo "error: module already added";
            } else {
                $order = mysqli_num_rows(query("SELECT * FROM project_tools WHERE projectID='$projectID'")) + 1;

                // Insert into project_tools
                $query = query("INSERT INTO project_tools (id, icon, name, link, hasConfig, `order`, projectID, section_id) VALUES (0,'$toolIcon','$toolName', '$link',0,'$order','$projectID', NULL)");

                if ($query) {
                    // Handle special cases like Chat App
                    if ($toolName == "Chat App") {
                        $toolID = fetch_assoc(query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'"))['id'];
                        $config = '{"api_key":"' . generateApiKey() . '"}';
                        query("INSERT INTO control_center_chat_app_config (config, toolID) VALUES ('$config','$toolID')");
                    }

                    // Add pages
                    $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($toolName));
                    $config_url = $url . "/config";
                    $config_name = $toolName . " Config";
                    $true = "true";
                    if($toolName == "Mail"){
                        $true = "false";
                    }
                    query("INSERT INTO control_center_pages VALUES (0,'$url', '$true','$toolIcon','$toolName', '', 0)");
                    query("INSERT INTO control_center_pages VALUES (0,'$config_url', 'true','cog-outline','$config_name', '', 0)");

                    if($toolName == "Mail"){
                        $mail_url = $url . "/email";
                        query("INSERT INTO control_center_pages VALUES (0,'$mail_url', 'false','$toolIcon','$toolName', '', 0)");
                    }

                    echo "success";
                } else {
                    echo "error: failed to add module";
                }
            }
        } else {
            echo "error: project not found";
        }
    } else {
        echo "error: module not found";
    }
}

// Remove module from project
if (isset($_POST['deinstall']) && isset($_POST['moduleID']) && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    $moduleID = escape_string($_POST['moduleID']);
    $module = query("SELECT * FROM module_store_modules WHERE id='$moduleID'");

    if (mysqli_num_rows($module) == 1) {
        $module = fetch_assoc($module);
        $toolName = $module['display_name'];
        $link = strtolower(str_replace(['Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', ' '], ['a', 'a', 'u', 'u', 'o', 'o', '-'], $toolName));

        // Get project ID
        $projectID = query("SELECT * FROM projects WHERE link='$project'");
        if (mysqli_num_rows($projectID) == 1) {
            $projectID = fetch_assoc($projectID)['projectID'];

            // Get tool ID
            $tool = query("SELECT * FROM project_tools WHERE projectID='$projectID' AND link='$link'");
            if (mysqli_num_rows($tool) == 1) {
                $toolID = fetch_assoc($tool)['id'];

                // Delete from project_tools
                if (query("DELETE FROM project_tools WHERE id='$toolID'")) {
                    // Clean up pages
                    $url = "project/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "/" . str_replace([" ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["-", "a", "a", "u", "u", "o", "o"], strtolower($toolName));
                    query("DELETE FROM control_center_pages WHERE url LIKE '$url%'");

                    echo "success";
                } else {
                    echo "error: failed to remove module";
                }
            } else {
                echo "error: module not found in project";
            }
        } else {
            echo "error: project not found";
        }
    } else {
        echo "error: module not found";
    }
}

// Get available modules from store
if (isset($_POST['getAvailableModules'])) {
    $modules = query("SELECT * FROM module_store_modules");
    $json = [];
    foreach ($modules as $module) {
        $json[] = [
            'id' => $module['id'],
            'name' => $module['name'],
            'display_name' => $module['display_name'],
            'description' => "test",//$module['description'],
            'icon' => $module['tool_icon'],// tool_
            'ref' => $module['ref']
        ];
    }
    echo echoJSON($json);
}