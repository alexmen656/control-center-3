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
function getProjects($userId) {
    $projectId = $_GET['id'] ?? null;
    
    if ($projectId) {
        // Get specific project
        $projectId = intval($projectId);
        $result = query("SELECT id, user_id, name, description, created_at, updated_at 
                        FROM control_center_modul_web_builder_projects 
                        WHERE id = $projectId AND user_id = $userId");
        
        if (!$result || mysqli_num_rows($result) === 0) {
            sendError('Project not found or access denied', 404);
        }
        
        $project = fetch_assoc($result);
        
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
        // Get all projects
        $result = query("SELECT id, user_id, name, description, created_at, updated_at 
                        FROM control_center_modul_web_builder_projects 
                        WHERE user_id = $userId 
                        ORDER BY updated_at DESC");
        
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
            $projects[] = $row;
        }
        
        sendJsonResponse('success', 'Projects retrieved successfully', 200, $projects);
    }
}

/**
 * Create a new project
 */
function createProject($userId) {
    $data = getJsonData();
    validateRequiredFields($data, ['name']);
    
    $name = escape_string($data['name']);
    $description = isset($data['description']) ? escape_string($data['description']) : '';
    
    // Insert project
    $insertResult = query("INSERT INTO control_center_modul_web_builder_projects (user_id, name, description) 
                          VALUES ($userId, '$name', '$description')");
    
    if (!$insertResult) {
        sendError('Failed to create project', 500);
    }
    
    $projectId = mysqli_insert_id($GLOBALS['con']);
    
    // Create default homepage
    $title = escape_string("Willkommen auf " . $data['name']);
    $metaDescription = escape_string("Homepage von " . $data['name']);
    
    query("INSERT INTO control_center_modul_web_builder_pages (project_id, name, slug, title, meta_description, is_home) 
          VALUES ($projectId, 'Homepage', 'home', '$title', '$metaDescription', 1)");
    
    $pageId = mysqli_insert_id($GLOBALS['con']);
    
    // Return created project
    $newProject = [
        'id' => $projectId,
        'user_id' => $userId,
        'name' => $data['name'],
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
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

/**
 * Update an existing project
 */
function updateProject($userId) {
    $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($projectId <= 0) {
        sendError('Invalid project ID', 400);
    }
    
    // Check ownership
    $checkResult = query("SELECT id FROM control_center_modul_web_builder_projects 
                         WHERE id = $projectId AND user_id = $userId");
    
    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        sendError('Project not found or access denied', 403);
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
    
    if (empty($updates)) {
        sendError('No data to update', 400);
    }
    
    $updates[] = "updated_at = NOW()";
    $updateSql = "UPDATE control_center_modul_web_builder_projects SET " . implode(", ", $updates) . " WHERE id = $projectId";
    
    $result = query($updateSql);
    
    if ($result) {
        // Get updated project
        $projectResult = query("SELECT id, user_id, name, description, created_at, updated_at 
                               FROM control_center_modul_web_builder_projects 
                               WHERE id = $projectId");
        $project = fetch_assoc($projectResult);
        
        sendJsonResponse('success', 'Project updated successfully', 200, $project);
    } else {
        sendError('Failed to update project', 500);
    }
}

/**
 * Delete a project
 */
function deleteProject($userId) {
    $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($projectId <= 0) {
        sendError('Invalid project ID', 400);
    }
    
    // Check ownership
    $checkResult = query("SELECT id FROM control_center_modul_web_builder_projects 
                         WHERE id = $projectId AND user_id = $userId");
    
    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        sendError('Project not found or access denied', 403);
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
