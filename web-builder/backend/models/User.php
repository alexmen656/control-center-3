<?php
require_once __DIR__ . '/../utils/Database.php';

class User {
    private $db;
    private $conn;
    private $tableName = 'control_center_web_builder_users';
    
    public function __construct() {
        // Direkte Instanziierung der Database-Klasse
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Find a user by username
     * 
     * @param string $username The username to search for
     * @return array|false User data or false if not found
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->tableName} WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Find a user by ID
     * 
     * @param int $id The user ID
     * @return array|false User data or false if not found
     */
    public function findById($id) {
        $sql = "SELECT id, username, email, created_at FROM {$this->tableName} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data (username, password, email)
     * @return int|false The new user ID or false on failure
     */
    public function create($userData) {
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO {$this->tableName} (username, password, email) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sss", $userData['username'], $hashedPassword, $userData['email']);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
}