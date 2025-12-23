<?php
require_once __DIR__ . '/../utils/Database.php';

/**
 * Page Model
 * 
 * Handles CRUD operations for pages in the web builder
 */
class Page {
    private $db;
    private $conn;
    private $tableName = 'control_center_web_builder_pages';
    
    public function __construct() {
        // Direkte Instanziierung der Database-Klasse
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Get all pages for a project
     *
     * @param int $projectId The ID of the project
     * @return array The pages
     */
    public function getAllByProject($projectId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE project_id = ? ORDER BY is_home DESC, name ASC";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pages = [];
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        
        return $pages;
    }
    
    /**
     * Get a page by ID
     *
     * @param int $pageId The ID of the page
     * @return array|null The page or null if not found
     */
    public function getById($pageId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get a page by slug for a project
     *
     * @param int $projectId The ID of the project
     * @param string $slug The slug of the page
     * @return array|null The page or null if not found
     */
    public function getBySlug($projectId, $slug) {
        $sql = "SELECT * FROM {$this->tableName} WHERE project_id = ? AND slug = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("is", $projectId, $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get the home page for a project
     *
     * @param int $projectId The ID of the project
     * @return array|null The home page or null if not found
     */
    public function getHomePage($projectId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE project_id = ? AND is_home = 1";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new page
     *
     * @param array $data The page data
     * @return int|bool The ID of the created page or false on failure
     */
    public function create($data) {
        // Check if home page exists and this is also a home page
        if (isset($data['is_home']) && $data['is_home'] == 1) {
            // Reset all other home pages
            $resetSql = "UPDATE {$this->tableName} SET is_home = 0 WHERE project_id = ?";
            $resetStmt = $this->conn->prepare($resetSql);
            
            if ($resetStmt) {
                $resetStmt->bind_param("i", $data['project_id']);
                $resetStmt->execute();
            }
        }
        
        $sql = "INSERT INTO {$this->tableName} (project_id, name, slug, title, meta_description, is_home) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $title = $data['title'] ?? $data['name'];
        $metaDescription = $data['meta_description'] ?? null;
        $isHome = $data['is_home'] ?? 0;
        
        $stmt->bind_param(
            "issssi", 
            $data['project_id'], 
            $data['name'], 
            $data['slug'], 
            $title, 
            $metaDescription,
            $isHome
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing page
     *
     * @param int $pageId The ID of the page
     * @param array $data The updated page data
     * @return bool Success or failure
     */
    public function update($pageId, $data) {
        $page = $this->getById($pageId);
        if (!$page) {
            return false;
        }
        
        // Check if home page exists and this is also a home page
        if (isset($data['is_home']) && $data['is_home'] == 1) {
            // Reset all other home pages
            $resetSql = "UPDATE {$this->tableName} SET is_home = 0 WHERE project_id = ?";
            $resetStmt = $this->conn->prepare($resetSql);
            
            if ($resetStmt) {
                $resetStmt->bind_param("i", $page['project_id']);
                $resetStmt->execute();
            }
        }
        
        $sql = "UPDATE {$this->tableName} SET 
                name = ?, 
                slug = ?, 
                title = ?, 
                meta_description = ?, 
                is_home = ?, 
                updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $name = $data['name'] ?? $page['name'];
        $slug = $data['slug'] ?? $page['slug'];
        $title = $data['title'] ?? $page['title'];
        $metaDescription = $data['meta_description'] ?? $page['meta_description'];
        $isHome = $data['is_home'] ?? $page['is_home'];
        
        $stmt->bind_param(
            "ssssis", 
            $name, 
            $slug, 
            $title, 
            $metaDescription, 
            $isHome, 
            $pageId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete a page
     *
     * @param int $pageId The ID of the page
     * @return bool Success or failure
     */
    public function delete($pageId) {
        // Get the page to check if it's a home page
        $page = $this->getById($pageId);
        if (!$page) {
            return false;
        }
        
        // Don't allow deleting the home page if it's the only page
        if ($page['is_home'] == 1) {
            $countSql = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE project_id = ?";
            $countStmt = $this->conn->prepare($countSql);
            
            if ($countStmt) {
                $countStmt->bind_param("i", $page['project_id']);
                $countStmt->execute();
                $result = $countStmt->get_result();
                $count = $result->fetch_assoc();
                
                if ($count['count'] <= 1) {
                    return false;
                }
            }
        }
        
        $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $pageId);
        return $stmt->execute();
    }
}