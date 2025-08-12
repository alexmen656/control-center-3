<?php

/**
 * Database API - Datenbankzugriff für CMS Projekte
 * Zugriff nur auf erlaubte Project Tables über project_tools
 */

require_once 'helper/BaseAPI.php';

class DatabaseAPI extends BaseAPI {

    private $allowedTables = [];
    private $projectName = '';

    public function __construct() {
        // Keine automatische Authentifizierung im Constructor
        parent::__construct();
    }

    public function handleRequest() {
        try {
            // Authentifizierung mit API-ID für database service
            $this->authenticate('3');
            
            // Rate Limiting prüfen
            $this->checkRateLimit();
            
            // Erlaubte Tabellen für dieses Projekt laden
            $this->loadAllowedTables();

            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_GET['action'] ?? 'tables';

            switch ($method) {
                case 'GET':
                    switch ($action) {
                        case 'tables':
                            $this->listTables();
                            break;
                        case 'query':
                            $this->queryTable();
                            break;
                        default:
                            $this->listTables();
                    }
                    break;
                case 'POST':
                    switch ($action) {
                        case 'query':
                            $this->queryTable();
                            break;
                        case 'insert':
                            $this->insertRecord();
                            break;
                        default:
                            $this->sendError('Invalid action for POST', 400);
                    }
                    break;
                case 'PUT':
                    if ($action === 'update') {
                        $this->updateRecord();
                    } else {
                        $this->sendError('Invalid action for PUT', 400);
                    }
                    break;
                case 'DELETE':
                    if ($action === 'delete') {
                        $this->deleteRecord();
                    } else {
                        $this->sendError('Invalid action for DELETE', 400);
                    }
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } catch (Exception $e) {
            $this->sendError('Database API error: ' . $e->getMessage(), 500);
        } catch (mysqli_sql_exception $e) {
            $this->sendError('Database SQL error: ' . $e->getMessage(), 500);
        } catch (Error $e) {
            $this->sendError('Database system error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lädt alle erlaubten Tabellen für dieses Projekt
     */
    private function loadAllowedTables() {
        $projectID = escape_string($this->projectID);
        
        $result = query("
            SELECT pt.link as table_link, p.link as project_name
            FROM project_tools pt
            JOIN projects p ON pt.projectID = p.projectID
            WHERE pt.projectID = '$projectID' 
            AND pt.icon = 'document-text-outline'
        ");

        if ($result) {
            while ($row = fetch_assoc($result)) {
                $projectName = str_replace(['-', ' '], '_', strtolower($row['project_name']));
                $tableLink = str_replace(['-', ' '], '_', strtolower($row['table_link']));
                
                $fullTableName = $projectName . '_' . $tableLink;
                $this->allowedTables[] = $fullTableName;
                
                if (empty($this->projectName)) {
                    $this->projectName = $projectName;
                }
            }
        }

        if (empty($this->allowedTables)) {
            $this->sendError('No database tables found for this project', 403);
        }
    }

    /**
     * Prüft ob Zugriff auf Tabelle erlaubt ist
     */
    private function isTableAllowed($tableName) {
        $tableName = strtolower($tableName);
        
        // Prüfe ob es eine der erlaubten Tabellen ist
        foreach ($this->allowedTables as $allowedTable) {
            if ($tableName === $allowedTable || $tableName === str_replace($this->projectName . '_', '', $allowedTable)) {
                return $allowedTable; // Gib den vollständigen Tabellennamen zurück
            }
        }
        
        return false;
    }

    /**
     * Listet alle verfügbaren Tabellen auf
     */
    private function listTables() {
        $tables = [];
        
        foreach ($this->allowedTables as $fullTableName) {
            $shortName = str_replace($this->projectName . '_', '', $fullTableName);
            
            // Prüfe ob Tabelle tatsächlich existiert
            $checkResult = query("SHOW TABLES LIKE '$fullTableName'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $tables[] = [
                    'name' => $shortName,
                    'full_name' => $fullTableName,
                    'project' => $this->projectName
                ];
            }
        }

        $this->sendSuccess([
            'tables' => $tables,
            'project' => $this->projectName,
            'count' => count($tables)
        ]);
    }

    /**
     * Abfrage einer Tabelle
     */
    private function queryTable() {
        try {
            $data = $this->getJsonInput();
            $this->validateRequired($data, ['table']);
            
            $requestedTable = $this->sanitize($data['table']);
            $fullTableName = $this->isTableAllowed($requestedTable);
            
            if (!$fullTableName) {
                $this->sendError('Access denied to table: ' . $requestedTable, 403);
            }

            $conditions = $data['conditions'] ?? [];
            $options = $data['options'] ?? [];
            
            // SQL Query aufbauen
            $sql = "SELECT * FROM `$fullTableName`";
            $whereParts = [];
            
            // Bedingungen hinzufügen
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $field = escape_string($this->sanitize($field));
                    $value = escape_string($this->sanitize($value));
                    $whereParts[] = "`$field` = '$value'";
                }
            }
            
            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }
            
            // Optionen hinzufügen
            if (isset($options['orderBy'])) {
                $orderBy = escape_string($this->sanitize($options['orderBy']));
                $direction = isset($options['direction']) && strtoupper($options['direction']) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY `$orderBy` $direction";
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
                while ($row = fetch_assoc($result)) {
                    $records[] = $row;
                }
                
                $this->sendSuccess([
                    'records' => $records,
                    'count' => count($records),
                    'table' => $requestedTable
                ]);
            } else {
                $this->sendError('Query failed: ' . mysqli_error($GLOBALS['con']), 500);
            }
        } catch (Exception $e) {
            $this->sendError('Query error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Neuen Datensatz einfügen
     */
    private function insertRecord() {
        try {
            $data = $this->getJsonInput();
            $this->validateRequired($data, ['table', 'data']);
            
            $requestedTable = $this->sanitize($data['table']);
            $fullTableName = $this->isTableAllowed($requestedTable);
            
            if (!$fullTableName) {
                $this->sendError('Access denied to table: ' . $requestedTable, 403);
            }

            $recordData = $data['data'];
            
            // Felder und Werte extrahieren
            $fields = [];
            $values = [];
            
            foreach ($recordData as $field => $value) {
                $fields[] = '`' . escape_string($this->sanitize($field)) . '`';
                $values[] = "'" . escape_string($this->sanitize($value)) . "'";
            }
            
            $sql = "INSERT INTO `$fullTableName` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            
            $result = query($sql);
            
            if ($result) {
                $newId = mysqli_insert_id($GLOBALS['con']);
                
                $this->sendSuccess([
                    'id' => $newId,
                    'table' => $requestedTable,
                    'inserted' => true
                ], 'Record inserted successfully');
            } else {
                $this->sendError('Insert failed: ' . mysqli_error($GLOBALS['con']), 500);
            }
        } catch (Exception $e) {
            $this->sendError('Insert error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Datensatz aktualisieren
     */
    private function updateRecord() {
        try {
            $data = $this->getJsonInput();
            $this->validateRequired($data, ['table', 'id', 'data']);
            
            $requestedTable = $this->sanitize($data['table']);
            $fullTableName = $this->isTableAllowed($requestedTable);
            
            if (!$fullTableName) {
                $this->sendError('Access denied to table: ' . $requestedTable, 403);
            }

            $id = (int)$data['id'];
            $recordData = $data['data'];
            
            // Update-Klauseln aufbauen
            $updates = [];
            
            foreach ($recordData as $field => $value) {
                $field = escape_string($this->sanitize($field));
                $value = escape_string($this->sanitize($value));
                $updates[] = "`$field` = '$value'";
            }
            
            $sql = "UPDATE `$fullTableName` SET " . implode(', ', $updates) . " WHERE id = $id";
            
            $result = query($sql);
            
            if ($result) {
                $this->sendSuccess([
                    'id' => $id,
                    'table' => $requestedTable,
                    'updated' => true
                ], 'Record updated successfully');
            } else {
                $this->sendError('Update failed: ' . mysqli_error($GLOBALS['con']), 500);
            }
        } catch (Exception $e) {
            $this->sendError('Update error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Datensatz löschen
     */
    private function deleteRecord() {
        try {
            $data = $this->getJsonInput();
            $this->validateRequired($data, ['table', 'id']);
            
            $requestedTable = $this->sanitize($data['table']);
            $fullTableName = $this->isTableAllowed($requestedTable);
            
            if (!$fullTableName) {
                $this->sendError('Access denied to table: ' . $requestedTable, 403);
            }

            $id = (int)$data['id'];
            
            $sql = "DELETE FROM `$fullTableName` WHERE id = $id";
            
            $result = query($sql);
            
            if ($result) {
                $this->sendSuccess([
                    'id' => $id,
                    'table' => $requestedTable,
                    'deleted' => true
                ], 'Record deleted successfully');
            } else {
                $this->sendError('Delete failed: ' . mysqli_error($GLOBALS['con']), 500);
            }
        } catch (Exception $e) {
            $this->sendError('Delete error: ' . $e->getMessage(), 500);
        }
    }
}

// Handle the request
$api = new DatabaseAPI();
$api->handleRequest();
