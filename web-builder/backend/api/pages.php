<?php
require_once __DIR__ . '/api_base.php';
require_once __DIR__ . '/../models/Page.php';

$pageModel = new Page();

// Route API requests based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];
$projectId = $_GET['project_id'] ?? 1; // Default to project_id 1 for now

switch ($method) {
    case 'GET':
        // Check if requesting a specific page by ID or slug
        $pageId = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;
        
        if ($pageId) {
            // Get specific page by ID
            $page = $pageModel->getById($pageId);
            
            if (!$page) {
                sendError("Page not found", 404);
            }
            
            sendResponse($page);
        } else if ($slug) {
            // Get specific page by slug
            $page = $pageModel->getBySlug($projectId, $slug);
            
            if (!$page) {
                sendError("Page not found", 404);
            }
            
            sendResponse($page);
        } else {
            // Get all pages for project
            $pages = $pageModel->getAllByProject($projectId);
            sendResponse($pages);
        }
        break;
        
    case 'POST':
        // Create new page
        $data = getJsonData();
        validateRequiredFields($data, ['name', 'slug']);
        
        // Add project_id if not provided in JSON
        $data['project_id'] = $data['project_id'] ?? $projectId;
        
        // Create slug from name if not provided
        if (empty($data['slug'])) {
            $data['slug'] = strtolower(
                trim(
                    preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']), 
                    '-'
                )
            );
        }
        
        // Check if a page with this slug already exists
        $existingPage = $pageModel->getBySlug($data['project_id'], $data['slug']);
        if ($existingPage) {
            sendError("A page with this slug already exists", 409);
        }
        
        $pageId = $pageModel->create($data);
        
        if ($pageId) {
            $newPage = $pageModel->getById($pageId);
            sendResponse($newPage);
        } else {
            sendError("Failed to create page", 500);
        }
        break;
        
    case 'PUT':
        // Update existing page
        $pageId = $_GET['id'] ?? null;
        
        if (!$pageId) {
            sendError("Missing page ID", 400);
        }
        
        $data = getJsonData();
        
        // Check if the page exists
        $page = $pageModel->getById($pageId);
        if (!$page) {
            sendError("Page not found", 404);
        }
        
        // If updating slug, check if new slug already exists
        if (isset($data['slug']) && $data['slug'] !== $page['slug']) {
            $existingPage = $pageModel->getBySlug($page['project_id'], $data['slug']);
            if ($existingPage) {
                sendError("A page with this slug already exists", 409);
            }
        }
        
        $result = $pageModel->update($pageId, $data);
        
        if ($result) {
            $updatedPage = $pageModel->getById($pageId);
            sendResponse($updatedPage);
        } else {
            sendError("Failed to update page", 500);
        }
        break;
        
    case 'DELETE':
        // Delete page
        $pageId = $_GET['id'] ?? null;
        
        if (!$pageId) {
            sendError("Missing page ID", 400);
        }
        
        $result = $pageModel->delete($pageId);
        
        if ($result) {
            sendResponse(['success' => true]);
        } else {
            sendError("Failed to delete page. Make sure it's not the only home page.", 500);
        }
        break;
        
    default:
        sendError("Method not allowed", 405);
}