<?php
/**
 * Database Connection Class
 * 
 * Verwaltet die Datenbankverbindung für die Web Builder Anwendung
 */
class Database {
    private $connection;
    private $config;
    
    /**
     * Konstruktor
     * 
     * Lädt die Datenbankonfiguration und stellt eine Verbindung her
     */
    public function __construct() {
        // Konfiguration laden
        $this->config = require_once 'database.php';
        
        // Verbindung herstellen
        $this->connect();
    }
    
    /**
     * Verbindung zur Datenbank herstellen
     * 
     * @return void
     */
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database']
            );
            
            // Prüfen, ob die Verbindung erfolgreich war
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Zeichensatz festlegen
            $this->connection->set_charset($this->config['charset']);
            
        } catch (Exception $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }
    
    /**
     * Verbindungsobjekt zurückgeben
     * 
     * @return mysqli Die Datenbankverbindung
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Führt eine SQL-Abfrage aus und gibt das Ergebnis zurück
     * 
     * @param string $sql Die auszuführende SQL-Abfrage
     * @return mysqli_result|bool Das Abfrageergebnis oder false bei Fehler
     */
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    /**
     * Bereitet ein vorbereitetes Statement vor
     * 
     * @param string $sql Das SQL-Statement mit Platzhaltern
     * @return mysqli_stmt Das vorbereitete Statement
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Gibt die letzte eingefügte ID zurück
     * 
     * @return int|string Die letzte eingefügte ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Führt eine SQL-Abfrage aus und gibt das Ergebnis als assoziatives Array zurück
     * 
     * @param string $sql Die auszuführende SQL-Abfrage
     * @return array Das Ergebnis als assoziatives Array
     */
    public function fetchAll($sql) {
        $result = $this->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Führt eine SQL-Abfrage aus und gibt die erste Zeile als assoziatives Array zurück
     * 
     * @param string $sql Die auszuführende SQL-Abfrage
     * @return array|null Die erste Zeile als assoziatives Array oder null
     */
    public function fetchOne($sql) {
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Maskiert einen String für die sichere Verwendung in SQL-Abfragen
     * 
     * @param string $value Der zu maskierende Wert
     * @return string Der maskierte Wert
     */
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * Schließt die Datenbankverbindung
     * 
     * @return void
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Destruktor
     * 
     * Schließt die Verbindung automatisch, wenn das Objekt zerstört wird
     */
    public function __destruct() {
        $this->close();
    }
}