<?php

/**
 * Users API - Benutzermanagement für CMS Projekte
 * Zugriff auf echte control_center_users Tabelle
 */

require_once 'BaseAPI.php';

class UsersAPI extends BaseAPI {

    public function __construct() {
        parent::__construct();
        $this->initDatabase();
    }

    private function initDatabase() {
        // Include mysql.php für query() und fetch_assoc() Funktionen
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Log API call
        $this->logApiCall('users', $method);

        switch ($method) {
            case 'GET':
                if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                    $this->getUser($pathParts[3]);
                } else {
                    $this->getUsers();
                }
                break;
            case 'POST':
                $this->createUser();
                break;
            case 'PUT':
                if (isset($pathParts[3])) {
                    $this->updateUser($pathParts[3]);
                }
                break;
            case 'DELETE':
                if (isset($pathParts[3])) {
                    $this->deleteUser($pathParts[3]);
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }



    private function getUsers() {
        $params = $_GET;
        
        // SQL-Query für echte Benutzer aus control_center_users
        $sql = "SELECT userID as id, name, email, created_at, updated_at FROM control_center_users WHERE 1=1";
        
        // Filter für Projekt-Benutzer (falls spezifiziert)
        if ($this->projectID) {
            $sql .= " AND userID IN (SELECT userID FROM control_center_user_projects WHERE projectID = '{$this->projectID}')";
        }
        
        // Paginierung
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $limit = isset($params['limit']) ? max(1, min(100, (int)$params['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        // Count query für Gesamtanzahl
        $countSql = str_replace("SELECT userID as id, name, email, created_at, updated_at", "SELECT COUNT(*)", $sql);
        
        try {
            $countResult = query($countSql);
            $total = 0;
            if ($countResult && mysqli_num_rows($countResult) > 0) {
                $row = mysqli_fetch_row($countResult);
                $total = (int)$row[0];
            }
            
            $sql .= " LIMIT $limit OFFSET $offset";
            $result = query($sql);
            
            $users = [];
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $users[] = $row;
                }
            }
            
            $this->sendSuccess([
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            $this->sendError('Database error: ' . $e->getMessage(), 500);
        }
    }

    private function getUser($id) {
        $id = (int)$id;
        $sql = "SELECT userID as id, name, email, created_at, updated_at FROM control_center_users WHERE userID = $id";
        
        // Prüfen ob Benutzer zu diesem Projekt gehört
        if ($this->projectID) {
            $sql .= " AND userID IN (SELECT userID FROM control_center_user_projects WHERE projectID = '{$this->projectID}')";
        }
        
        $result = query($sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $this->sendSuccess($user);
        } else {
            $this->sendError('User not found', 404);
        }
    }

    private function createUser() {
        $data = $this->getJsonInput();
        
        $this->validateRequired($data, ['name', 'email']);
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
        }

        // Check if email already exists
        $email = $this->sanitize($data['email']);
        $checkResult = query("SELECT userID FROM control_center_users WHERE email = '$email'");
        
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $this->sendError('Email already exists', 400);
        }

        // Insert new user
        $name = $this->sanitize($data['name']);
        $password = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : password_hash('defaultpass', PASSWORD_DEFAULT);
        $loginToken = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO control_center_users (name, email, password, loginToken, created_at) 
                VALUES ('$name', '$email', '$password', '$loginToken', NOW())";
        
        $result = query($sql);
        
        if ($result) {
            // Get the new user ID
            $getUserResult = query("SELECT userID as id, name, email, created_at FROM control_center_users WHERE email = '$email' ORDER BY userID DESC LIMIT 1");
            
            if ($getUserResult && mysqli_num_rows($getUserResult) > 0) {
                $newUser = mysqli_fetch_assoc($getUserResult);
                $newId = $newUser['id'];
                
                // Benutzer zum Projekt hinzufügen falls projektspezifisch
                if ($this->projectID) {
                    query("INSERT INTO control_center_user_projects (userID, projectID, active) VALUES ($newId, '{$this->projectID}', 1)");
                }
                
                $this->sendSuccess($newUser, 'User created successfully');
            } else {
                $this->sendError('Failed to retrieve created user', 500);
            }
        } else {
            $this->sendError('Failed to create user', 500);
        }
    }

    private function updateUser($id) {
        $data = $this->getJsonInput();
        $id = (int)$id;
        
        // Prüfen ob Benutzer existiert
        $checkResult = query("SELECT userID FROM control_center_users WHERE userID = $id");
        if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
            $this->sendError('User not found', 404);
        }

        $updates = [];
        
        // Update fields
        if (isset($data['name'])) {
            $name = $this->sanitize($data['name']);
            $updates[] = "name = '$name'";
        }
        
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Invalid email format', 400);
            }
            
            // Check if email already exists (excluding current user)
            $email = $this->sanitize($data['email']);
            $emailCheck = query("SELECT userID FROM control_center_users WHERE email = '$email' AND userID != $id");
            
            if ($emailCheck && mysqli_num_rows($emailCheck) > 0) {
                $this->sendError('Email already exists', 400);
            }
            
            $updates[] = "email = '$email'";
        }
        
        if (!empty($updates)) {
            $updates[] = "updated_at = NOW()";
            $sql = "UPDATE control_center_users SET " . implode(', ', $updates) . " WHERE userID = $id";
            
            $result = query($sql);
            
            if ($result) {
                // Aktualisierte Benutzer-Daten zurückgeben
                $userResult = query("SELECT userID as id, name, email, created_at, updated_at FROM control_center_users WHERE userID = $id");
                $user = mysqli_fetch_assoc($userResult);
                
                $this->sendSuccess($user, 'User updated successfully');
            } else {
                $this->sendError('Failed to update user', 500);
            }
        } else {
            $this->sendError('No valid fields to update', 400);
        }
    }

    private function deleteUser($id) {
        $id = (int)$id;
        
        // Prüfen ob Benutzer existiert
        $checkResult = query("SELECT userID, name FROM control_center_users WHERE userID = $id");
        if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
            $this->sendError('User not found', 404);
        }

        // TODO: Prüfen ob es der letzte Admin ist (falls role Feld existiert)
        // Don't allow deleting important system users
        
        // Benutzer aus Projekt-Zuordnungen entfernen
        if ($this->projectID) {
            query("DELETE FROM control_center_user_projects WHERE userID = $id AND projectID = '{$this->projectID}'");
        }
        
        // Für vollständige Löschung (nur wenn nicht projektspezifisch)
        if (!$this->projectID) {
            query("DELETE FROM control_center_users WHERE userID = $id");
        }

        $this->sendSuccess(null, 'User deleted successfully');
    }

}

// Handle the request
$api = new UsersAPI();
$api->handleRequest();