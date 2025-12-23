<?php
require_once __DIR__ . '/../utils/Database.php';

/**
 * Component Model
 * 
 * Handles CRUD operations for page builder components
 */
class Component {
    private $db;
    private $conn;
    private $tableName = 'control_center_web_builder_components';
    
    public function __construct() {
        // Direkt eine neue Database-Instanz erstellen statt getInstance() zu verwenden
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Get all components for a page
     *
     * @param int $pageId The ID of the page
     * @return array The components
     */
    public function getAllByPage($pageId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE page_id = ? ORDER BY position ASC";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $components = [];
        while ($row = $result->fetch_assoc()) {
            $components[] = $row;
        }
        
        return $components;
    }
    
    /**
     * Get component by its UUID
     *
     * @param int $pageId The ID of the page
     * @param string $componentId The UUID of the component
     * @return array|null The component or null if not found
     */
    public function getByUUID($pageId, $componentId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE page_id = ? AND component_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("is", $pageId, $componentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new component
     *
     * @param int $pageId The ID of the page
     * @param string $componentId The UUID of the component
     * @param string $htmlCode The HTML code of the component
     * @param int $position The position of the component in the page
     * @return int|bool The ID of the created component or false on failure
     */
    public function create($pageId, $componentId, $htmlCode, $position = 0) {
        $sql = "INSERT INTO {$this->tableName} (page_id, component_id, html_code, position) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("issi", $pageId, $componentId, $htmlCode, $position);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing component
     *
     * @param int $pageId The ID of the page
     * @param string $componentId The UUID of the component
     * @param string $htmlCode The updated HTML code
     * @return bool Success or failure
     */
    public function update($pageId, $componentId, $htmlCode) {
        $sql = "UPDATE {$this->tableName} SET html_code = ?, updated_at = NOW() 
                WHERE page_id = ? AND component_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sis", $htmlCode, $pageId, $componentId);
        return $stmt->execute();
    }
    
    /**
     * Update component position
     *
     * @param int $pageId The ID of the page
     * @param string $componentId The UUID of the component
     * @param int $position The new position
     * @return bool Success or failure
     */
    public function updatePosition($pageId, $componentId, $position) {
        $sql = "UPDATE {$this->tableName} SET position = ?, updated_at = NOW() 
                WHERE page_id = ? AND component_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("iis", $position, $pageId, $componentId);
        return $stmt->execute();
    }
    
    /**
     * Delete a component
     *
     * @param int $pageId The ID of the page
     * @param string $componentId The UUID of the component
     * @return bool Success or failure
     */
    public function delete($pageId, $componentId) {
        $sql = "DELETE FROM {$this->tableName} WHERE page_id = ? AND component_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("is", $pageId, $componentId);
        return $stmt->execute();
    }
    
    /**
     * Delete all components for a page
     *
     * @param int $pageId The ID of the page
     * @return bool Success or failure
     */
    public function deleteAllByPage($pageId) {
        $sql = "DELETE FROM {$this->tableName} WHERE page_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $pageId);
        return $stmt->execute();
    }
}