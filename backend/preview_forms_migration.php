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

$response = [];
$formsToMigrate = [];
$formsInTools = [];

try {
    // Step 1: Get all forms from form_settings with their project information
    $formsQuery = "SELECT fs.form_id, fs.form_name, fs.project, p.projectID, p.link as project_link, p.name as project_name 
                   FROM form_settings fs 
                   LEFT JOIN projects p ON fs.project = p.link 
                   ORDER BY fs.project, fs.form_name";
    
    $formsResult = query($formsQuery);
    
    if (!$formsResult) {
        throw new Exception("Failed to fetch forms: " . mysqli_error($connection));
    }
    
    while ($form = mysqli_fetch_assoc($formsResult)) {
        $formId = $form['form_id'];
        $formName = $form['form_name'];
        $projectLink = $form['project_link'];
        $projectID = $form['projectID'];
        $projectName = $form['project_name'];
        
        if (!$projectID || !$projectLink) {
            continue;
        }
        
        // Check if form exists in project_tools
        $toolCheckQuery = "SELECT id, name, link FROM project_tools 
                          WHERE projectID = '$projectID' 
                          AND (name LIKE '%$formName%' OR link LIKE '%$formName%')";
        
        $toolCheckResult = query($toolCheckQuery);
        $toolsFound = [];
        
        if ($toolCheckResult && mysqli_num_rows($toolCheckResult) > 0) {
            while ($tool = mysqli_fetch_assoc($toolCheckResult)) {
                $toolsFound[] = [
                    'tool_id' => $tool['id'],
                    'tool_name' => $tool['name'],
                    'tool_link' => $tool['link']
                ];
            }
        }
        
        // Check if page already exists
        $pageCheckQuery = "SELECT pageID FROM control_center_pages 
                          WHERE url = 'project/$projectLink/forms/$formName'";
        
        $pageExists = false;
        $pageCheckResult = query($pageCheckQuery);
        if ($pageCheckResult && mysqli_num_rows($pageCheckResult) > 0) {
            $pageExists = true;
        }
        
        $formsToMigrate[] = [
            'form_id' => $formId,
            'form_name' => $formName,
            'project_name' => $projectName,
            'project_link' => $projectLink,
            'project_id' => $projectID,
            'tools_found' => $toolsFound,
            'tools_count' => count($toolsFound),
            'page_exists' => $pageExists,
            'would_create_url' => "project/$projectLink/forms/$formName"
        ];
        
        if (count($toolsFound) > 0) {
            $formsInTools = array_merge($formsInTools, $toolsFound);
        }
    }
    
    $response['success'] = true;
    $response['total_forms'] = count($formsToMigrate);
    $response['total_tools_to_remove'] = count($formsInTools);
    $response['forms_with_existing_pages'] = count(array_filter($formsToMigrate, function($f) { return $f['page_exists']; }));
    $response['forms_to_migrate'] = $formsToMigrate;
    $response['summary'] = [
        'forms_found' => count($formsToMigrate),
        'tools_to_remove' => count($formsInTools),
        'new_pages_to_create' => count(array_filter($formsToMigrate, function($f) { return !$f['page_exists']; })),
        'existing_pages_found' => count(array_filter($formsToMigrate, function($f) { return $f['page_exists']; }))
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Preview failed with exception: " . $e->getMessage();
}

echo echoJson($response);
?>
