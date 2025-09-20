<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

include '/www/paxar/components/php_head.php';
include_once 'config.php';

function echoJson($json)
{
    return json_encode($json, JSON_PRETTY_PRINT);
}

// Safety check - only allow execution if specifically requested
if (!isset($_GET['execute']) || $_GET['execute'] !== 'yes') {
    echo echoJson([
        'success' => false,
        'message' => 'Migration not executed. Add ?execute=yes to the URL to run the migration.',
        'warning' => 'This will remove all forms from project_tools. Make sure you have a backup!'
    ]);
    exit;
}

$response = [];
$removedTools = [];
$errors = [];

try {
    // Get all forms from form_settings
    $formsQuery = "SELECT fs.form_id, fs.form_name, fs.project, p.projectID, p.link as project_link, p.name as project_name 
                   FROM form_settings fs 
                   LEFT JOIN projects p ON fs.project = p.link 
                   ORDER BY fs.project, fs.form_name";
    
    $formsResult = query($formsQuery);
    
    if (!$formsResult) {
        throw new Exception("Failed to fetch forms: " . mysqli_error($connection));
    }
    
    $removedFromToolsCount = 0;
    
    while ($form = mysqli_fetch_assoc($formsResult)) {
        $formName = $form['form_name'];
        $projectID = $form['projectID'];
        $projectName = $form['project_name'];
        
        if (!$projectID) {
            $errors[] = "Skipping form '$formName' - project not found for project link: " . $form['project'];
            continue;
        }
        
        // Remove form from project_tools if it exists
        $toolCheckQuery = "SELECT id, name, link FROM project_tools 
                          WHERE projectID = '$projectID' 
                          AND (name LIKE '%$formName%' OR link LIKE '%$formName%')";
        
        $toolCheckResult = query($toolCheckQuery);
        
        if ($toolCheckResult && mysqli_num_rows($toolCheckResult) > 0) {
            while ($tool = mysqli_fetch_assoc($toolCheckResult)) {
                $toolId = $tool['id'];
                $toolName = $tool['name'];
                $toolLink = $tool['link'];
                
                // Remove from project_tools
                $deleteToolQuery = "DELETE FROM project_tools WHERE id = '$toolId'";
                if (query($deleteToolQuery)) {
                    $removedFromToolsCount++;
                    
                    // Also remove any tool config
                    $deleteToolConfigQuery = "DELETE FROM project_tools_config WHERE tool_id = '$toolId'";
                    query($deleteToolConfigQuery);
                    
                    $removedTools[] = [
                        'form_name' => $formName,
                        'project_name' => $projectName,
                        'tool_id' => $toolId,
                        'tool_name' => $toolName,
                        'tool_link' => $toolLink
                    ];
                } else {
                    $errors[] = "Failed to remove tool for form '$formName' from project_tools";
                }
            }
        }
    }
    
    $response['success'] = true;
    $response['message'] = "Migration completed successfully. Forms removed from project_tools and are now available in the sidebar via form_settings.";
    $response['removed_from_tools'] = $removedFromToolsCount;
    $response['removed_tools'] = $removedTools;
    $response['errors'] = $errors;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Migration failed with exception: " . $e->getMessage();
    $response['errors'] = $errors;
}

echo echoJson($response);
?>
