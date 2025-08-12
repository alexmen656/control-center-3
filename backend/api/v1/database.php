<?php

/**
 * Database API - Datenbankzugriff für CMS Projekte
 * Zugriff auf CMS Forms/Tabellen
 */

require_once 'BaseAPI.php';

class DatabaseAPI extends BaseAPI {

    public function __construct() {
        parent::__construct();
        $this->initDatabase();
    }

    private function initDatabase() {
        // Include mysql.php für query() Funktionen
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Log API call
        $this->logApiCall('database', $method);

        switch ($method) {
            case 'POST':
                if (isset($pathParts[3])) {
                    switch ($pathParts[3]) {
                        case 'query':
                            $this->queryTable();
                            break;
                        case 'insert':
                            $this->insertRecord();
                            break;
                        default:
                            $this->sendError('Invalid endpoint', 404);
                    }
                }
                break;
            case 'PUT':
                if (isset($pathParts[3]) && $pathParts[3] === 'update') {
                    $this->updateRecord();
                }
                break;
            case 'DELETE':
                if (isset($pathParts[3]) && $pathParts[3] === 'delete') {
                    $this->deleteRecord();
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function queryTable() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['table']);
        
        $table = $this->sanitize($data['table']);
        $conditions = $data['conditions'] ?? [];
        $options = $data['options'] ?? [];
        
        // Tabellennamen für das Projekt formatieren
        $tableName = $this->getProjectTableName($table);
        
        // SQL Query aufbauen
        $sql = "SELECT * FROM $tableName";
        $whereParts = [];
        
        // Bedingungen hinzufügen
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                $field = $this->sanitize($field);
                $value = $this->sanitize($value);
                $whereParts[] = "$field = '$value'";
            }
        }
        
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        
        // Optionen hinzufügen
        if (isset($options['orderBy'])) {
            $orderBy = $this->sanitize($options['orderBy']);
            $direction = isset($options['direction']) && strtoupper($options['direction']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $orderBy $direction";
        }
        
        if (isset($options['limit'])) {
            $limit = (int)$options['limit'];
            $sql .= " LIMIT $limit";
            
            if (isset($options['offset'])) {
                $offset = (int)$options['offset'];
                $sql .= " OFFSET $offset";
            }
        }
        
        $result = query($sql);
        
        if ($result) {
            $records = [];
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $records[] = $row;
                }
            }
            
            $this->sendSuccess([
                'records' => $records,
                'count' => count($records),
                'table' => $table
            ]);
        } else {
            $this->sendError('Query failed', 500);
        }
    }

    private function insertRecord() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['table', 'data']);
        
        $table = $this->sanitize($data['table']);
        $recordData = $data['data'];
        
        $tableName = $this->getProjectTableName($table);
        
        // Felder und Werte extrahieren
        $fields = [];
        $values = [];
        
        foreach ($recordData as $field => $value) {
            $fields[] = $this->sanitize($field);
            $values[] = "'" . $this->sanitize($value) . "'";
        }
        
        $sql = "INSERT INTO $tableName (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        
        $result = query($sql);
        
        if ($result) {
            // Neue Record ID holen (falls verfügbar)
            $newId = null;
            if (function_exists('mysqli_insert_id') && isset($GLOBALS['con'])) {
                $newId = mysqli_insert_id($GLOBALS['con']);
            }
            
            $this->sendSuccess([
                'id' => $newId,
                'table' => $table,
                'inserted' => true
            ], 'Record inserted successfully');
        } else {
            $this->sendError('Insert failed', 500);
        }
    }

    private function updateRecord() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['table', 'id', 'data']);
        
        $table = $this->sanitize($data['table']);
        $id = (int)$data['id'];
        $recordData = $data['data'];
        
        $tableName = $this->getProjectTableName($table);
        
        // Update-Klauseln aufbauen
        $updates = [];
        
        foreach ($recordData as $field => $value) {
            $field = $this->sanitize($field);
            $value = $this->sanitize($value);
            $updates[] = "$field = '$value'";
        }
        
        $sql = "UPDATE $tableName SET " . implode(', ', $updates) . " WHERE id = $id";
        
        $result = query($sql);
        
        if ($result) {
            $this->sendSuccess([
                'id' => $id,
                'table' => $table,
                'updated' => true
            ], 'Record updated successfully');
        } else {
            $this->sendError('Update failed', 500);
        }
    }

    private function deleteRecord() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['table', 'id']);
        
        $table = $this->sanitize($data['table']);
        $id = (int)$data['id'];
        
        $tableName = $this->getProjectTableName($table);
        
        $sql = "DELETE FROM $tableName WHERE id = $id";
        
        $result = query($sql);
        
        if ($result) {
            $this->sendSuccess([
                'id' => $id,
                'table' => $table,
                'deleted' => true
            ], 'Record deleted successfully');
        } else {
            $this->sendError('Delete failed', 500);
        }
    }

    /**
     * Generiert den Tabellennamen für ein Projekt basierend auf dem CMS-Schema
     */
    private function getProjectTableName($table) {
        // Projekt-Name aus projectID holen (falls verfügbar)
        if ($this->projectID) {
            $projectResult = query("SELECT link FROM projects WHERE projectID = '{$this->projectID}'");
            if ($projectResult && mysqli_num_rows($projectResult) > 0) {
                $project = mysqli_fetch_assoc($projectResult);
                $projectLink = $project['link'];
                
                // CMS Tabellennamen-Schema: projectlink_tablename
                $cleanProjectLink = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($projectLink));
                $cleanTableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table));
                
                return $cleanProjectLink . "_" . $cleanTableName;
            }
        }
        
        // Fallback: nur Tabellenname
        return strtolower($table);
    }
}

// Handle the request
$api = new DatabaseAPI();
$api->handleRequest();
