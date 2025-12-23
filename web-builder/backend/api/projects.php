<?php
require_once '../config/database.php';
require_once 'api_base.php';
require_once '../utils/JwtUtil.php';
require_once '../utils/Database.php';

// Authentifizierung mit JWT
$token = JwtUtil::getBearerToken();
if (!$token) {
    sendJsonResponse('error', 'Nicht autorisiert. Bitte loggen Sie sich ein.', 401);
    exit;
}

// Token validieren
$payload = JwtUtil::validateToken($token);
if (!$payload) {
    sendJsonResponse('error', 'Nicht autorisiert. Ungültiger Token.', 401);
    exit;
}

// Benutzer-ID aus dem Token extrahieren
$userId = $payload['user_id'];

$db = new Database();
$conn = $db->getConnection();

// HTTP-Methode ermitteln
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // Projekte abrufen
    case 'GET':
        getProjects($conn, $userId);
        break;
    
    // Neues Projekt erstellen
    case 'POST':
        createProject($conn, $userId);
        break;
    
    // Projekt aktualisieren
    case 'PUT':
        updateProject($conn, $userId);
        break;
    
    // Projekt löschen
    case 'DELETE':
        deleteProject($conn, $userId);
        break;
    
    default:
        sendJsonResponse('error', 'Nicht unterstützte HTTP-Methode', 405);
        break;
}

// Alle Projekte eines Benutzers holen
function getProjects($conn, $userId) {
    try {
        $query = "SELECT id, name, description, created_at, updated_at 
                 FROM control_center_web_builder_projects 
                 WHERE user_id = ? 
                 ORDER BY updated_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            // Hol die Seiten für jedes Projekt
            $pageQuery = "SELECT id, name, slug, title, meta_description, is_home 
                         FROM control_center_web_builder_pages 
                         WHERE project_id = ?";
            
            $pageStmt = $conn->prepare($pageQuery);
            $pageStmt->bind_param("i", $row['id']);
            $pageStmt->execute();
            $pageResult = $pageStmt->get_result();
            
            $pages = [];
            while ($page = $pageResult->fetch_assoc()) {
                $pages[] = $page;
            }
            
            $row['pages'] = $pages;
            $projects[] = $row;
        }
        
        sendJsonResponse('success', 'Projekte erfolgreich abgerufen', 200, $projects);
    } catch (Exception $e) {
        sendJsonResponse('error', 'Fehler beim Abrufen der Projekte: ' . $e->getMessage(), 500);
    }
}

// Ein neues Projekt erstellen
function createProject($conn, $userId) {
    try {
        // Daten aus dem POST-Request holen
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty($data['name'])) {
            sendJsonResponse('error', 'Projektname ist erforderlich', 400);
            return;
        }
        
        $name = $data['name'];
        $description = isset($data['description']) ? $data['description'] : '';
        
        // Projekt in die Datenbank einfügen
        $query = "INSERT INTO control_center_web_builder_projects (user_id, name, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $userId, $name, $description);
        $stmt->execute();
        
        $projectId = $stmt->insert_id;
        
        // Standard-Homepage erstellen
        $pageQuery = "INSERT INTO control_center_web_builder_pages (project_id, name, slug, title, meta_description, is_home) 
                     VALUES (?, 'Homepage', 'home', ?, ?, 1)";
        $pageStmt = $conn->prepare($pageQuery);
        $title = "Willkommen auf " . $name;
        $metaDescription = "Homepage von " . $name;
        $pageStmt->bind_param("iss", $projectId, $title, $metaDescription);
        $pageStmt->execute();
        
        $pageId = $pageStmt->insert_id;
        
        // Das erstellte Projekt zurückgeben
        $newProject = [
            'id' => $projectId,
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'pages' => [
                [
                    'id' => $pageId,
                    'name' => 'Homepage',
                    'slug' => 'home',
                    'title' => $title,
                    'meta_description' => $metaDescription,
                    'is_home' => 1
                ]
            ]
        ];
        
        sendJsonResponse('success', 'Projekt erfolgreich erstellt', 201, $newProject);
    } catch (Exception $e) {
        sendJsonResponse('error', 'Fehler beim Erstellen des Projekts: ' . $e->getMessage(), 500);
    }
}

// Ein Projekt aktualisieren
function updateProject($conn, $userId) {
    try {
        // Projekt-ID aus der URL holen
        $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($projectId <= 0) {
            sendJsonResponse('error', 'Ungültige Projekt-ID', 400);
            return;
        }
        
        // Überprüfen, ob das Projekt dem Benutzer gehört
        $checkQuery = "SELECT id FROM control_center_web_builder_projects WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ii", $projectId, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            sendJsonResponse('error', 'Projekt nicht gefunden oder Sie haben keine Berechtigung', 403);
            return;
        }
        
        // Daten aus dem PUT-Request holen
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = isset($data['name']) ? $data['name'] : null;
        $description = isset($data['description']) ? $data['description'] : null;
        
        // Baue die UPDATE-Abfrage dynamisch auf
        $updates = [];
        $types = '';
        $params = [];
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $types .= "s";
            $params[] = $name;
        }
        
        if ($description !== null) {
            $updates[] = "description = ?";
            $types .= "s";
            $params[] = $description;
        }
        
        if (empty($updates)) {
            sendJsonResponse('error', 'Keine Daten zum Aktualisieren angegeben', 400);
            return;
        }
        
        // Projekt aktualisieren
        $query = "UPDATE control_center_web_builder_projects SET " . implode(", ", $updates) . " WHERE id = ? AND user_id = ?";
        $types .= "ii";
        $params[] = $projectId;
        $params[] = $userId;
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Aktualisiertes Projekt abrufen
            $selectQuery = "SELECT id, name, description, created_at, updated_at 
                           FROM control_center_web_builder_projects 
                           WHERE id = ?";
            $selectStmt = $conn->prepare($selectQuery);
            $selectStmt->bind_param("i", $projectId);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $project = $result->fetch_assoc();
            
            sendJsonResponse('success', 'Projekt erfolgreich aktualisiert', 200, $project);
        } else {
            sendJsonResponse('error', 'Keine Änderungen vorgenommen', 200);
        }
    } catch (Exception $e) {
        sendJsonResponse('error', 'Fehler beim Aktualisieren des Projekts: ' . $e->getMessage(), 500);
    }
}

// Ein Projekt löschen
function deleteProject($conn, $userId) {
    try {
        // Projekt-ID aus der URL holen
        $projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($projectId <= 0) {
            sendJsonResponse('error', 'Ungültige Projekt-ID', 400);
            return;
        }
        
        // Überprüfen, ob das Projekt dem Benutzer gehört
        $checkQuery = "SELECT id FROM control_center_web_builder_projects WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ii", $projectId, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            sendJsonResponse('error', 'Projekt nicht gefunden oder Sie haben keine Berechtigung', 403);
            return;
        }
        
        // Projekt löschen (kaskadierendes Löschen sollte durch Fremdschlüsselbeziehungen bereits konfiguriert sein)
        $query = "DELETE FROM control_center_web_builder_projects WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            sendJsonResponse('success', 'Projekt erfolgreich gelöscht', 200);
        } else {
            sendJsonResponse('error', 'Projekt konnte nicht gelöscht werden', 500);
        }
    } catch (Exception $e) {
        sendJsonResponse('error', 'Fehler beim Löschen des Projekts: ' . $e->getMessage(), 500);
    }
}