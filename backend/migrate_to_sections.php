<?php
/**
 * Migration Script: Migrate existing projects to the new Sections system
 * 
 * This script:
 * 1. Creates a default "Tools" section for each project
 * 2. Assigns all existing tools to this section
 * 3. Optionally creates other default sections
 * 
 * Run this AFTER running the SQL migration in create_sidebar_sections_table.sql
 */

include 'head.php';
include_once 'jwt_helper.php';
include_once 'config.php';

header('Content-Type: application/json');

// Only allow authenticated requests
$headers = getRequestHeaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(['error' => 'Authorization required']);
    exit;
}

$token = $headers['Authorization'];
$payload = SimpleJWT::verify($token, $jwt_secret);
if (!$payload || empty($payload['sub'])) {
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$userID = intval($payload['sub']);

// Check if this is a dry run
$dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] == '1';

$results = [
    'dry_run' => $dryRun,
    'projects_processed' => 0,
    'sections_created' => 0,
    'tools_migrated' => 0,
    'errors' => [],
    'details' => []
];

// Get all projects for this user
$userProjects = query("SELECT p.* FROM projects p 
                       INNER JOIN control_center_user_projects cup ON p.projectID = cup.projectID 
                       WHERE cup.userID = '$userID'");

if (mysqli_num_rows($userProjects) == 0) {
    echo json_encode(['message' => 'No projects found for this user']);
    exit;
}

foreach ($userProjects as $project) {
    $projectID = $project['projectID'];
    $projectName = $project['name'];
    $projectLink = $project['link'];
    
    $projectResult = [
        'project_id' => $projectID,
        'project_name' => $projectName,
        'actions' => []
    ];
    
    // Check if project already has sections
    $existingSections = query("SELECT COUNT(*) as count FROM project_sidebar_sections WHERE projectID = '$projectID'");
    $sectionCount = fetch_assoc($existingSections)['count'];
    
    if ($sectionCount > 0) {
        $projectResult['actions'][] = "Project already has $sectionCount sections - skipping";
        $results['details'][] = $projectResult;
        continue;
    }
    
    // Get tools without sections
    $toolsWithoutSection = query("SELECT * FROM project_tools WHERE projectID = '$projectID' AND (section_id IS NULL OR section_id = 0)");
    $toolCount = mysqli_num_rows($toolsWithoutSection);
    
    if (!$dryRun) {
        // Create default "Tools" section
        $addRoute = "/project/$projectLink/new/tool/";
        $createSection = query("INSERT INTO project_sidebar_sections 
            (projectID, name, slug, icon, order_index, is_default, is_collapsible, show_add_button, add_button_route) 
            VALUES ('$projectID', 'Tools', 'tools', 'construct-outline', 1, 1, 1, 1, '$addRoute')");
        
        if ($createSection) {
            $toolsSectionId = mysqli_insert_id($GLOBALS['con']);
            $results['sections_created']++;
            $projectResult['actions'][] = "Created 'Tools' section (ID: $toolsSectionId)";
            
            // Assign existing tools to this section
            if ($toolCount > 0) {
                $assignTools = query("UPDATE project_tools SET section_id = '$toolsSectionId' WHERE projectID = '$projectID' AND (section_id IS NULL OR section_id = 0)");
                if ($assignTools) {
                    $results['tools_migrated'] += $toolCount;
                    $projectResult['actions'][] = "Assigned $toolCount tools to 'Tools' section";
                } else {
                    $results['errors'][] = "Failed to assign tools for project $projectName";
                }
            }
        } else {
            $results['errors'][] = "Failed to create section for project $projectName";
        }
    } else {
        $projectResult['actions'][] = "[DRY RUN] Would create 'Tools' section";
        $projectResult['actions'][] = "[DRY RUN] Would assign $toolCount tools to section";
        $results['sections_created']++;
        $results['tools_migrated'] += $toolCount;
    }
    
    $results['projects_processed']++;
    $results['details'][] = $projectResult;
}

$results['message'] = $dryRun 
    ? "Dry run complete. Run without dry_run=1 to apply changes."
    : "Migration complete!";

echo json_encode($results, JSON_PRETTY_PRINT);
