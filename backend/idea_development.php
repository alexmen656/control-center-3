<?php
include_once 'config.php';
include_once 'head.php';

class IdeaDevelopmentAPI {
    
    private function getTableName($project) {
        return 'idea_development_' . str_replace('-', '_', $project);
    }
    
    private function createTableIfNotExists($project) {
        $tableName = $this->getTableName($project);
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text,
            `status` enum('draft','in_progress','completed','archived') DEFAULT 'draft',
            `milestones` longtext, -- JSON array of milestones: [{id, title, isCompleted, dueDate}]
            `notes` longtext, -- Markdown content
            `assets` longtext, -- JSON array of assets: [{id, type, name, url}]
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $result = $GLOBALS['con']->query($sql);
        if ($result === FALSE) {
            throw new Exception("Error creating table: " . $GLOBALS['con']->error);
        }
    }
    
    public function getIdeas($project) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $sql = "SELECT * FROM `$tableName` ORDER BY created_at DESC";
        $result = $GLOBALS['con']->query($sql);
        
        $ideas = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Decode JSON fields
                $row['milestones'] = json_decode($row['milestones'] ?? '[]', true);
                $row['assets'] = json_decode($row['assets'] ?? '[]', true);
                $ideas[] = $row;
            }
        }
        return $ideas;
    }
    
    public function getIdea($project, $id) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $sql = "SELECT * FROM `$tableName` WHERE id = " . intval($id);
        $result = $GLOBALS['con']->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['milestones'] = json_decode($row['milestones'] ?? '[]', true);
            $row['assets'] = json_decode($row['assets'] ?? '[]', true);
            return $row;
        }
        return null;
    }
    
    public function createIdea($project, $data) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $title = $GLOBALS['con']->real_escape_string($data['title']);
        $description = $GLOBALS['con']->real_escape_string($data['description'] ?? '');
        $status = $GLOBALS['con']->real_escape_string($data['status'] ?? 'draft');
        $milestones = $GLOBALS['con']->real_escape_string(json_encode($data['milestones'] ?? []));
        $notes = $GLOBALS['con']->real_escape_string($data['notes'] ?? '');
        $assets = $GLOBALS['con']->real_escape_string(json_encode($data['assets'] ?? []));
        
        $sql = "INSERT INTO `$tableName` (title, description, status, milestones, notes, assets) 
                VALUES ('$title', '$description', '$status', '$milestones', '$notes', '$assets')";
                
        if ($GLOBALS['con']->query($sql) === TRUE) {
            return $GLOBALS['con']->insert_id;
        } else {
            throw new Exception("Error creating idea: " . $GLOBALS['con']->error);
        }
    }
    
    public function updateIdea($project, $id, $data) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $updates = [];
        if (isset($data['title'])) $updates[] = "title = '" . $GLOBALS['con']->real_escape_string($data['title']) . "'";
        if (isset($data['description'])) $updates[] = "description = '" . $GLOBALS['con']->real_escape_string($data['description']) . "'";
        if (isset($data['status'])) $updates[] = "status = '" . $GLOBALS['con']->real_escape_string($data['status']) . "'";
        if (isset($data['milestones'])) $updates[] = "milestones = '" . $GLOBALS['con']->real_escape_string(json_encode($data['milestones'])) . "'";
        if (isset($data['notes'])) $updates[] = "notes = '" . $GLOBALS['con']->real_escape_string($data['notes']) . "'";
        if (isset($data['assets'])) $updates[] = "assets = '" . $GLOBALS['con']->real_escape_string(json_encode($data['assets'])) . "'";
        
        if (empty($updates)) {
            return $this->getIdea($project, $id);
        }
        
        $sql = "UPDATE `$tableName` SET " . implode(", ", $updates) . " WHERE id = " . intval($id);
        
        if ($GLOBALS['con']->query($sql) === TRUE) {
            return $this->getIdea($project, $id);
        } else {
            throw new Exception("Error updating idea: " . $GLOBALS['con']->error);
        }
    }
    
    public function deleteIdea($project, $id) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $sql = "DELETE FROM `$tableName` WHERE id = " . intval($id);
        
        if ($GLOBALS['con']->query($sql) !== TRUE) {
            throw new Exception("Error deleting idea: " . $GLOBALS['con']->error);
        }
        return true;
    }
}

// Check for project ID
$project = $_GET['project'] ?? '';

if (empty($project)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Project ID is required']);
    exit();
}

$api = new IdeaDevelopmentAPI();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $idea = $api->getIdea($project, $_GET['id']);
                if ($idea) {
                    echo json_encode(['success' => true, 'data' => $idea]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Idea not found']);
                }
            } else {
                $ideas = $api->getIdeas($project);
                echo json_encode(['success' => true, 'data' => $ideas]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $_GET['action'] ?? 'create';
            
            if ($action === 'create') {
                $id = $api->createIdea($project, $input);
                $idea = $api->getIdea($project, $id);
                echo json_encode(['success' => true, 'data' => $idea]);
            } elseif ($action === 'update') {
                if (!isset($input['id'])) {
                    throw new Exception('ID is required for update');
                }
                $idea = $api->updateIdea($project, $input['id'], $input);
                echo json_encode(['success' => true, 'data' => $idea]);
            } elseif ($action === 'delete') {
                 if (!isset($input['id'])) {
                    throw new Exception('ID is required for delete');
                }
                $api->deleteIdea($project, $input['id']);
                echo json_encode(['success' => true, 'message' => 'Idea deleted']);
            } else {
                throw new Exception('Invalid action');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
