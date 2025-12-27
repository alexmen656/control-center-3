<?php
/**
 * Web Builder Components API
 * 
 * Handles CRUD operations for web builder page components
 * Uses Control Center authentication
 */

require_once __DIR__ . '/api_base.php';

// Authenticate user
$userId = authenticateUser();

// HTTP method
$method = $_SERVER['REQUEST_METHOD'];
$pageId = $_GET['page_id'] ?? null;

debug_log("Components API Request", [
    'method' => $method,
    'page_id' => $pageId,
    'user_id' => $userId
]);

// Validate page_id for all endpoints
if (!$pageId) {
    sendError('Missing page_id parameter', 400);
}

$pageId = intval($pageId);

// Verify user has access to this page and the linked CC project
$pageResult = query("SELECT p.*, pr.user_id, pr.project_id FROM control_center_modul_web_builder_pages p
                    INNER JOIN control_center_modul_web_builder_projects pr ON p.project_id = pr.id
                    WHERE p.id = $pageId");

if (!$pageResult || mysqli_num_rows($pageResult) === 0) {
    sendError('Page not found', 404);
}

$page = fetch_assoc($pageResult);

if ($page['user_id'] != $userId) {
    sendError('Access denied', 403);
}

// Verify user still has access to the linked CC project
if (!userHasProjectAccess($userId, $page['project_id'])) {
    sendError('Access denied: You no longer have access to the linked Control Center project', 403);
}

switch ($method) {
    case 'GET':
        getComponents($pageId);
        break;
    
    case 'POST':
        createComponents($pageId);
        break;
    
    case 'PUT':
        updateComponent($pageId);
        break;
    
    case 'DELETE':
        deleteComponent($pageId);
        break;
    
    default:
        sendError('Method not allowed', 405);
        break;
}

/**
 * Get all components for a page
 */
function getComponents($pageId) {
    $result = query("SELECT * FROM control_center_modul_web_builder_components 
                    WHERE page_id = $pageId 
                    ORDER BY position ASC");
    
    $components = [];
    while ($row = fetch_assoc($result)) {
        $components[] = [
            'id' => $row['component_id'],
            'html_code' => $row['html_code'],
            'position' => (int)$row['position']
        ];
    }
    
    debug_log("GET components", ["count" => count($components)]);
    sendResponse($components);
}

/**
 * Create or replace components
 */
function createComponents($pageId) {
    $data = getJsonData();
    
    debug_log("POST data received", ["components_count" => is_array($data) ? count($data) : "single"]);
    
    // Check if we're receiving an array of components or a single component
    if (isset($data[0]) && is_array($data[0])) {
        // Array of components - replacing all components for the page
        
        // First, delete all existing components
        query("DELETE FROM control_center_modul_web_builder_components WHERE page_id = $pageId");
        debug_log("Deleted all components for page", ["page_id" => $pageId]);
        
        // Insert each new component
        $insertResults = [];
        foreach ($data as $index => $component) {
            if (!isset($component['id']) || !isset($component['html_code'])) {
                continue;
            }
            
            $componentId = escape_string($component['id']);
            $htmlCode = escape_string($component['html_code']);
            $position = intval($index);
            
            $result = query("INSERT INTO control_center_modul_web_builder_components 
                            (page_id, component_id, html_code, position) 
                            VALUES ($pageId, '$componentId', '$htmlCode', $position)");
            
            $insertResults[] = [
                "component_id" => $componentId,
                "success" => ($result !== false)
            ];
        }
        
        debug_log("Inserted components", ["results" => $insertResults]);
        sendResponse(['success' => true, 'message' => 'All components updated']);
        
    } else {
        // Single component - add new component
        if (!isset($data['id']) || !isset($data['html_code'])) {
            sendError('Missing required fields: id, html_code', 400);
        }
        
        $componentId = escape_string($data['id']);
        $htmlCode = escape_string($data['html_code']);
        
        // Count existing components to determine position
        $countResult = query("SELECT COUNT(*) as count FROM control_center_modul_web_builder_components WHERE page_id = $pageId");
        $count = fetch_assoc($countResult);
        $position = intval($count['count']);
        
        $result = query("INSERT INTO control_center_modul_web_builder_components 
                        (page_id, component_id, html_code, position) 
                        VALUES ($pageId, '$componentId', '$htmlCode', $position)");
        
        debug_log("Inserted single component", ["component_id" => $componentId, "success" => ($result !== false)]);
        
        if ($result) {
            sendResponse(['success' => true, 'id' => $data['id']]);
        } else {
            sendError('Failed to create component', 500);
        }
    }
}

/**
 * Update a specific component
 */
function updateComponent($pageId) {
    $data = getJsonData();
    
    if (!isset($data['id']) || !isset($data['html_code'])) {
        sendError('Missing required fields: id, html_code', 400);
    }
    
    $componentId = escape_string($data['id']);
    $htmlCode = escape_string($data['html_code']);
    
    $result = query("UPDATE control_center_modul_web_builder_components SET 
                    html_code = '$htmlCode', 
                    updated_at = NOW()
                    WHERE page_id = $pageId AND component_id = '$componentId'");
    
    debug_log("Updated component", ["component_id" => $componentId, "success" => $result]);
    
    if ($result) {
        sendResponse(['success' => true]);
    } else {
        sendError('Failed to update component', 500);
    }
}

/**
 * Delete a component
 */
function deleteComponent($pageId) {
    $componentId = $_GET['component_id'] ?? null;
    
    if (!$componentId) {
        sendError('Missing component_id parameter', 400);
    }
    
    $componentId = escape_string($componentId);
    
    $result = query("DELETE FROM control_center_modul_web_builder_components 
                    WHERE page_id = $pageId AND component_id = '$componentId'");
    
    debug_log("Deleted component", ["component_id" => $componentId, "success" => $result]);
    
    if ($result) {
        sendResponse(['success' => true]);
    } else {
        sendError('Failed to delete component', 500);
    }
}
