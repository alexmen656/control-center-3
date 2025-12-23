<?php
require_once __DIR__ . '/api_base.php';
require_once __DIR__ . '/../models/Component.php';

// Füge eine Debug-Protokollierung hinzu
function debug_log($message, $data = null) {
    $logFile = __DIR__ . '/../logs/debug.log';
    $dir = dirname($logFile);
    
    // Erstelle das Logs-Verzeichnis, falls es nicht existiert
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= ": " . json_encode($data);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

$componentModel = new Component();

// Route API requests based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];
$pageId = $_GET['page_id'] ?? null;

debug_log("API Request", [
    'method' => $method,
    'page_id' => $pageId,
    'query_params' => $_GET
]);

// Validate page_id for all endpoints
if (!$pageId) {
    sendError("Missing page_id parameter", 400);
}

switch ($method) {
    case 'GET':
        // Get all components for a page
        $components = $componentModel->getAllByPage($pageId);
        debug_log("GET components", ["count" => count($components)]);
        
        // Transform DB rows to match the frontend expected format
        $transformedComponents = [];
        foreach ($components as $component) {
            $transformedComponents[] = [
                'id' => $component['component_id'],
                'html_code' => $component['html_code'],
                'position' => (int)$component['position']
            ];
        }
        
        sendResponse($transformedComponents);
        break;
        
    case 'POST':
        // Create a new component or update the entire component list
        $data = getJsonData();
        debug_log("POST data received", ["components_count" => is_array($data) ? count($data) : "single"]);
        
        // Check if we're receiving a single component or an array
        if (isset($data[0]) && is_array($data[0])) {
            // Array of components - replacing all components for the page
            
            // First, delete all existing components
            $deleteResult = $componentModel->deleteAllByPage($pageId);
            debug_log("Deleted all components", ["success" => $deleteResult, "page_id" => $pageId]);
            
            // Insert each new component
            $insertResults = [];
            foreach ($data as $index => $component) {
                validateRequiredFields($component, ['id', 'html_code']);
                
                $componentId = $component['id'];
                $htmlCode = $component['html_code'];
                
                $insertId = $componentModel->create($pageId, $componentId, $htmlCode, $index);
                $insertResults[] = [
                    "component_id" => $componentId,
                    "insert_id" => $insertId,
                    "success" => ($insertId !== false)
                ];
            }
            
            debug_log("Inserted components", ["results" => $insertResults]);
            sendResponse(['success' => true, 'message' => 'All components updated']);
        } else {
            // Single component - add new component
            validateRequiredFields($data, ['id', 'html_code']);
            
            // Count existing components to determine position
            $existingComponents = $componentModel->getAllByPage($pageId);
            $position = count($existingComponents);
            
            $result = $componentModel->create($pageId, $data['id'], $data['html_code'], $position);
            debug_log("Inserted single component", ["component_id" => $data['id'], "success" => ($result !== false)]);
            
            if ($result) {
                sendResponse(['success' => true, 'id' => $data['id']]);
            } else {
                sendError("Failed to create component", 500);
            }
        }
        break;
        
    case 'PUT':
        // Update a specific component
        $data = getJsonData();
        validateRequiredFields($data, ['id', 'html_code']);
        
        $componentId = $data['id'];
        $htmlCode = $data['html_code'];
        
        $result = $componentModel->update($pageId, $componentId, $htmlCode);
        debug_log("Updated component", ["component_id" => $componentId, "success" => $result]);
        
        if ($result) {
            sendResponse(['success' => true]);
        } else {
            sendError("Failed to update component", 500);
        }
        break;
        
    case 'DELETE':
        // Delete a component
        $componentId = $_GET['component_id'] ?? null;
        
        if (!$componentId) {
            sendError("Missing component_id parameter", 400);
        }
        
        $result = $componentModel->delete($pageId, $componentId);
        debug_log("Deleted component", ["component_id" => $componentId, "success" => $result]);
        
        if ($result) {
            sendResponse(['success' => true]);
        } else {
            sendError("Failed to delete component", 500);
        }
        break;
        
    default:
        sendError("Method not allowed", 405);
}