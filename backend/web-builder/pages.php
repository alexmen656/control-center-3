<?php
/**
 * Web Builder Pages API
 * 
 * Handles CRUD operations for web builder pages
 * Uses Control Center authentication
 */

require_once __DIR__ . '/api_base.php';

// Authenticate user
$userId = authenticateUser();

// HTTP method
$method = $_SERVER['REQUEST_METHOD'];
$projectId = $_GET['project_id'] ?? null;

// Validate project ownership for all requests
if ($projectId) {
    $projectId = intval($projectId);
    $checkResult = query("SELECT id FROM control_center_modul_web_builder_projects 
                         WHERE id = $projectId AND user_id = $userId");
    
    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        sendError('Project not found or access denied', 403);
    }
}

switch ($method) {
    case 'GET':
        getPages($userId, $projectId);
        break;
    
    case 'POST':
        createPage($userId, $projectId);
        break;
    
    case 'PUT':
        updatePage($userId);
        break;
    
    case 'DELETE':
        deletePage($userId);
        break;
    
    default:
        sendError('Method not allowed', 405);
        break;
}

/**
 * Get pages
 */
function getPages($userId, $projectId) {
    $pageId = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    
    if ($pageId) {
        // Get specific page by ID
        $pageId = intval($pageId);
        $result = query("SELECT p.* FROM control_center_modul_web_builder_pages p
                        INNER JOIN control_center_modul_web_builder_projects pr ON p.project_id = pr.id
                        WHERE p.id = $pageId AND pr.user_id = $userId");
        
        if (!$result || mysqli_num_rows($result) === 0) {
            sendError('Page not found', 404);
        }
        
        sendResponse(fetch_assoc($result));
        
    } else if ($slug && $projectId) {
        // Get page by slug
        $slug = escape_string($slug);
        $result = query("SELECT * FROM control_center_modul_web_builder_pages 
                        WHERE project_id = $projectId AND slug = '$slug'");
        
        if (!$result || mysqli_num_rows($result) === 0) {
            sendError('Page not found', 404);
        }
        
        sendResponse(fetch_assoc($result));
        
    } else if ($projectId) {
        // Get all pages for project
        $result = query("SELECT * FROM control_center_modul_web_builder_pages 
                        WHERE project_id = $projectId 
                        ORDER BY is_home DESC, name ASC");
        
        $pages = [];
        while ($row = fetch_assoc($result)) {
            $pages[] = $row;
        }
        
        sendResponse($pages);
        
    } else {
        sendError('Missing project_id parameter', 400);
    }
}

/**
 * Create a new page
 */
function createPage($userId, $projectId) {
    if (!$projectId) {
        sendError('Missing project_id parameter', 400);
    }
    
    $data = getJsonData();
    validateRequiredFields($data, ['name']);
    
    $name = escape_string($data['name']);
    
    // Generate slug from name if not provided
    $slug = isset($data['slug']) && !empty($data['slug']) 
        ? escape_string($data['slug'])
        : escape_string(strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')));
    
    // Check if slug already exists
    $existingResult = query("SELECT id FROM control_center_modul_web_builder_pages 
                            WHERE project_id = $projectId AND slug = '$slug'");
    
    if ($existingResult && mysqli_num_rows($existingResult) > 0) {
        sendError('A page with this slug already exists', 409);
    }
    
    $title = isset($data['title']) ? escape_string($data['title']) : $name;
    $metaDescription = isset($data['meta_description']) ? escape_string($data['meta_description']) : '';
    $isHome = isset($data['is_home']) ? intval($data['is_home']) : 0;
    
    // If setting as home page, reset others
    if ($isHome) {
        query("UPDATE control_center_modul_web_builder_pages SET is_home = 0 WHERE project_id = $projectId");
    }
    
    // Insert page
    $result = query("INSERT INTO control_center_modul_web_builder_pages 
                    (project_id, name, slug, title, meta_description, is_home) 
                    VALUES ($projectId, '$name', '$slug', '$title', '$metaDescription', $isHome)");
    
    if (!$result) {
        sendError('Failed to create page', 500);
    }
    
    $pageId = mysqli_insert_id($GLOBALS['con']);
    
    // Return created page
    $pageResult = query("SELECT * FROM control_center_modul_web_builder_pages WHERE id = $pageId");
    sendResponse(fetch_assoc($pageResult));
}

/**
 * Update an existing page
 */
function updatePage($userId) {
    $pageId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($pageId <= 0) {
        sendError('Missing page ID', 400);
    }
    
    // Check page exists and user has access
    $pageResult = query("SELECT p.*, pr.user_id FROM control_center_modul_web_builder_pages p
                        INNER JOIN control_center_modul_web_builder_projects pr ON p.project_id = pr.id
                        WHERE p.id = $pageId");
    
    if (!$pageResult || mysqli_num_rows($pageResult) === 0) {
        sendError('Page not found', 404);
    }
    
    $page = fetch_assoc($pageResult);
    
    if ($page['user_id'] != $userId) {
        sendError('Access denied', 403);
    }
    
    $data = getJsonData();
    $projectId = $page['project_id'];
    
    // Check if new slug conflicts
    if (isset($data['slug']) && $data['slug'] !== $page['slug']) {
        $newSlug = escape_string($data['slug']);
        $existingResult = query("SELECT id FROM control_center_modul_web_builder_pages 
                                WHERE project_id = $projectId AND slug = '$newSlug' AND id != $pageId");
        
        if ($existingResult && mysqli_num_rows($existingResult) > 0) {
            sendError('A page with this slug already exists', 409);
        }
    }
    
    // Build update
    $name = isset($data['name']) ? escape_string($data['name']) : $page['name'];
    $slug = isset($data['slug']) ? escape_string($data['slug']) : $page['slug'];
    $title = isset($data['title']) ? escape_string($data['title']) : $page['title'];
    $metaDescription = isset($data['meta_description']) ? escape_string($data['meta_description']) : $page['meta_description'];
    $isHome = isset($data['is_home']) ? intval($data['is_home']) : $page['is_home'];
    
    // If setting as home page, reset others
    if ($isHome && !$page['is_home']) {
        query("UPDATE control_center_modul_web_builder_pages SET is_home = 0 WHERE project_id = $projectId");
    }
    
    $result = query("UPDATE control_center_modul_web_builder_pages SET 
                    name = '$name', 
                    slug = '$slug', 
                    title = '$title', 
                    meta_description = '$metaDescription', 
                    is_home = $isHome,
                    updated_at = NOW()
                    WHERE id = $pageId");
    
    if ($result) {
        $updatedResult = query("SELECT * FROM control_center_modul_web_builder_pages WHERE id = $pageId");
        sendResponse(fetch_assoc($updatedResult));
    } else {
        sendError('Failed to update page', 500);
    }
}

/**
 * Delete a page
 */
function deletePage($userId) {
    $pageId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($pageId <= 0) {
        sendError('Missing page ID', 400);
    }
    
    // Check page exists and user has access
    $pageResult = query("SELECT p.*, pr.user_id FROM control_center_modul_web_builder_pages p
                        INNER JOIN control_center_modul_web_builder_projects pr ON p.project_id = pr.id
                        WHERE p.id = $pageId");
    
    if (!$pageResult || mysqli_num_rows($pageResult) === 0) {
        sendError('Page not found', 404);
    }
    
    $page = fetch_assoc($pageResult);
    
    if ($page['user_id'] != $userId) {
        sendError('Access denied', 403);
    }
    
    $projectId = $page['project_id'];
    
    // Don't allow deleting the only home page
    if ($page['is_home']) {
        $countResult = query("SELECT COUNT(*) as count FROM control_center_modul_web_builder_pages WHERE project_id = $projectId");
        $count = fetch_assoc($countResult);
        
        if ($count['count'] <= 1) {
            sendError('Cannot delete the only page in the project', 400);
        }
    }
    
    // Delete components first
    query("DELETE FROM control_center_modul_web_builder_components WHERE page_id = $pageId");
    
    // Delete page
    $result = query("DELETE FROM control_center_modul_web_builder_pages WHERE id = $pageId");
    
    if ($result) {
        // If deleted home page, set another page as home
        if ($page['is_home']) {
            query("UPDATE control_center_modul_web_builder_pages SET is_home = 1 
                  WHERE project_id = $projectId ORDER BY id ASC LIMIT 1");
        }
        
        sendResponse(['success' => true]);
    } else {
        sendError('Failed to delete page', 500);
    }
}
