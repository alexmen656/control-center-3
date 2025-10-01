<?php
include_once 'config.php';
include_once 'head.php';

// Get project_id from query or session
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_SESSION['project_id']) ? (int)$_SESSION['project_id'] : null);

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Project ID is required']);
    exit;
}

// GET - Get connection for project
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT app_sku, app_title FROM appstore_connections WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $connection = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'connection' => $connection ?: null,
            'has_connection' => (bool)$connection
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// POST - Create or update connection
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['app_sku']) || !isset($input['app_title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'app_sku and app_title are required']);
            exit;
        }
        
        $app_sku = trim($input['app_sku']);
        $app_title = trim($input['app_title']);
        
        // Insert or update
        $stmt = $pdo->prepare("
            INSERT INTO appstore_connections (project_id, app_sku, app_title) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                app_sku = VALUES(app_sku),
                app_title = VALUES(app_title),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$project_id, $app_sku, $app_title]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection saved',
            'connection' => [
                'app_sku' => $app_sku,
                'app_title' => $app_title
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// DELETE - Remove connection
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $stmt = $pdo->prepare("DELETE FROM appstore_connections WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection removed'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
