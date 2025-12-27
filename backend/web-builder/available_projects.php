<?php
require_once __DIR__ . '/api_base.php';
$userId = authenticateUser();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

getAvailableProjects($userId);

function getAvailableProjects($userId)
{
    $allProjects = getUserControlCenterProjects($userId);
    $usedResult = query("SELECT project_id FROM control_center_modul_web_builder_projects");

    $usedProjectIds = [];
    if ($usedResult) {
        while ($row = fetch_assoc($usedResult)) {
            $usedProjectIds[] = $row['project_id'];
        }
    }

    $availableProjects = array_filter($allProjects, function ($project) use ($usedProjectIds) {
        return !in_array($project['projectID'], $usedProjectIds);
    });

    $availableProjects = array_values($availableProjects);
    sendJsonResponse('success', 'Available projects retrieved successfully', 200, $availableProjects);
}
