<?php
/**
 * Web Builder Projects API
 * 
 * Handles CRUD operations for web builder projects
 * Uses Control Center authentication
 */

require_once __DIR__ . '/api_base.php';

// Authenticate user
$userId = authenticateUser();

// HTTP method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getProjects($userId);
        break;

    case 'POST':
        createProject($userId);
        break;

    case 'PUT':
        updateProject($userId);
        break;

    case 'DELETE':
        deleteProject($userId);
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}

/**
 * Get all projects for a user
 */
function getProjects($userId)
{
    $projectId = $_GET['id'] ?? null;

    if ($projectId) {
        // Get specific project
        $projectId = intval($projectId);
        $result = query("SELECT id, user_id, project_id, name, description, created_at, updated_at 
                        FROM control_center_modul_web_builder_projects 
                        WHERE id = $projectId AND user_id = $userId");

        if (!$result || mysqli_num_rows($result) === 0) {
            sendError('Project not found or access denied', 404);
        }

        $project = fetch_assoc($result);

        // Get Control Center project info
        $ccProject = getControlCenterProject($project['project_id']);
        if ($ccProject) {
            $project['control_center_project'] = $ccProject;
        }

        // Get pages for this project
        $pagesResult = query("SELECT id, name, slug, title, meta_description, is_home 
                             FROM control_center_modul_web_builder_pages 
                             WHERE project_id = $projectId");

        $pages = [];
        while ($page = fetch_assoc($pagesResult)) {
            $pages[] = $page;
        }
        $project['pages'] = $pages;

        sendResponse($project);
    } else {
        // Get all projects - only those where user has access to the CC project
        $result = query("SELECT wb.id, wb.user_id, wb.project_id, wb.name, wb.description, wb.created_at, wb.updated_at,
                               p.name as cc_project_name, p.link as cc_project_link
                        FROM control_center_modul_web_builder_projects wb
                        INNER JOIN projects p ON wb.project_id = p.projectID
                        INNER JOIN control_center_user_projects up ON p.projectID = up.projectID
                        WHERE wb.user_id = $userId AND up.userID = $userId
                        ORDER BY wb.updated_at DESC");

        $projects = [];
        while ($row = fetch_assoc($result)) {
            // Get pages for each project
            $projectId = $row['id'];
            $pagesResult = query("SELECT id, name, slug, title, meta_description, is_home 
                                 FROM control_center_modul_web_builder_pages 
                                 WHERE project_id = $projectId");

            $pages = [];
            while ($page = fetch_assoc($pagesResult)) {
                $pages[] = $page;
            }

            $row['pages'] = $pages;
            $row['control_center_project'] = [
                'projectID' => $row['project_id'],
                'name' => $row['cc_project_name'],
                'link' => $row['cc_project_link']
            ];
            unset($row['cc_project_name']);
            unset($row['cc_project_link']);

            $projects[] = $row;
        }

        sendJsonResponse('success', 'Projects retrieved successfully', 200, $projects);
    }
}

/**
 * Create a new project
 */
function createProject($userId)
{
    $data = getJsonData();
    validateRequiredFields($data, ['name', 'project_id']);

    //print_r($data);
    $name = escape_string($data['name']);
    $description = isset($data['description']) ? escape_string($data['description']) : '';
    $ccProjectId = escape_string($data['project_id']);

    if (!userHasProjectAccess($userId, $ccProjectId)) {
        sendError('Access denied: You do not have access to this Control Center project', 403);
    }

    $insertResult = query("INSERT INTO control_center_modul_web_builder_projects (user_id, project_id, name, description) 
                          VALUES ($userId, '$ccProjectId', '$name', '$description')");

    if (!$insertResult) {
        sendError('Failed to create project', 500);
    }

    $projectId = mysqli_insert_id($GLOBALS['con']);
    $title = escape_string("Willkommen auf " . $data['name']);
    $metaDescription = escape_string("Homepage von " . $data['name']);

    query("INSERT INTO control_center_modul_web_builder_pages (project_id, name, slug, title, meta_description, is_home) 
          VALUES ($projectId, 'Homepage', 'home', '$title', '$metaDescription', 1)");

    $pageId = mysqli_insert_id($GLOBALS['con']);
    $ccProject = getControlCenterProject($ccProjectId);

    $newProject = [
        'id' => $projectId,
        'user_id' => $userId,
        'project_id' => $ccProjectId,
        'name' => $data['name'],
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'control_center_project' => $ccProject,
        'pages' => [
            [
                'id' => $pageId,
                'name' => 'Homepage',
                'slug' => 'home',
                'title' => $title,
                'meta_description' => $metaDescription,
                'is_home' => 1
            ]
        ]
    ];

    sendJsonResponse('success', 'Project created successfully', 201, $newProject);
}

function updateProject($userId)
{
    $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($projectId <= 0) {
        sendError('Invalid project ID', 400);
    }

    $checkResult = query("SELECT id, project_id FROM control_center_modul_web_builder_projects 
                         WHERE id = $projectId AND user_id = $userId");

    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        sendError('Project not found or access denied', 403);
    }

    $projectData = fetch_assoc($checkResult);

    if (!userHasProjectAccess($userId, $projectData['project_id'])) {
        sendError('Access denied: You no longer have access to the linked Control Center project', 403);
    }

    $data = getJsonData();

    // Build update query
    $updates = [];

    if (isset($data['name'])) {
        $updates[] = "name = '" . escape_string($data['name']) . "'";
    }

    if (isset($data['description'])) {
        $updates[] = "description = '" . escape_string($data['description']) . "'";
    }

    // Don't allow changing project_id - it's immutable
    if (isset($data['project_id'])) {
        sendError('Cannot change the linked Control Center project', 400);
    }

    if (empty($updates)) {
        sendError('No data to update', 400);
    }

    $updates[] = "updated_at = NOW()";
    $updateSql = "UPDATE control_center_modul_web_builder_projects SET " . implode(", ", $updates) . " WHERE id = $projectId";

    $result = query($updateSql);

    if ($result) {
        // Get updated project
        $projectResult = query("SELECT id, user_id, project_id, name, description, created_at, updated_at 
                               FROM control_center_modul_web_builder_projects 
                               WHERE id = $projectId");
        $project = fetch_assoc($projectResult);

        // Add CC project info
        $ccProject = getControlCenterProject($project['project_id']);
        if ($ccProject) {
            $project['control_center_project'] = $ccProject;
        }

        sendJsonResponse('success', 'Project updated successfully', 200, $project);
    } else {
        sendError('Failed to update project', 500);
    }
}

/**
 * Delete a project
 */
function deleteProject($userId)
{
    $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($projectId <= 0) {
        sendError('Invalid project ID', 400);
    }

    // Check ownership and get project data
    $checkResult = query("SELECT id, project_id FROM control_center_modul_web_builder_projects 
                         WHERE id = $projectId AND user_id = $userId");

    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        sendError('Project not found or access denied', 403);
    }

    $projectData = fetch_assoc($checkResult);

    // Verify user still has access to the linked CC project
    if (!userHasProjectAccess($userId, $projectData['project_id'])) {
        sendError('Access denied: You no longer have access to the linked Control Center project', 403);
    }

    // Delete components first (foreign key constraint)
    query("DELETE c FROM control_center_modul_web_builder_components c 
          INNER JOIN control_center_modul_web_builder_pages p ON c.page_id = p.id 
          WHERE p.project_id = $projectId");

    // Delete pages
    query("DELETE FROM control_center_modul_web_builder_pages WHERE project_id = $projectId");

    // Delete project
    $result = query("DELETE FROM control_center_modul_web_builder_projects WHERE id = $projectId AND user_id = $userId");

    if ($result) {
        sendJsonResponse('success', 'Project deleted successfully', 200);
    } else {
        sendError('Failed to delete project', 500);
    }
}
