<?php
/**
 * Beispiel-API die den neuen BaseAPI verwendet
 * Jede API muss ihre app_id (slug aus cms_apis) an BaseAPI übergeben
 */

require_once 'BaseAPI.php';

class ExampleAPI extends BaseAPI
{
    public function __construct()
    {
        // Jede API muss ihre app_id (slug) aus der cms_apis Tabelle übergeben
        parent::__construct('user-management'); // Beispiel app_id
        
        // Authentifizierung wird automatisch durchgeführt mit der app_id
        $this->handleRequest();
    }

    private function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];

        // Rate Limiting prüfen
        $this->checkRateLimit();

        switch ($method) {
            case 'GET':
                $this->handleGet();
                break;
            case 'POST':
                $this->handlePost();
                break;
            case 'PUT':
                $this->handlePut();
                break;
            case 'DELETE':
                $this->handleDelete();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function handleGet()
    {
        // Beispiel: User-Daten für das authentifizierte Projekt holen
        $users = query("SELECT * FROM users WHERE projectID = '{$this->projectID}'");
        
        $userData = [];
        while ($user = mysqli_fetch_assoc($users)) {
            $userData[] = $this->sanitize($user);
        }

        $this->logApiCall('/api/v1/users', 'GET', 200);
        $this->sendSuccess($userData, 'Users retrieved successfully');
    }

    private function handlePost()
    {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['name', 'email']);

        $name = escape_string($data['name']);
        $email = escape_string($data['email']);
        $projectID = escape_string($this->projectID);

        $result = query("INSERT INTO users (name, email, projectID, created_at) VALUES ('$name', '$email', '$projectID', NOW())");

        if ($result) {
            $userId = mysqli_insert_id($GLOBALS['connection']);
            $this->logApiCall('/api/v1/users', 'POST', 201);
            $this->sendSuccess(['user_id' => $userId], 'User created successfully');
        } else {
            $this->sendError('Failed to create user', 500);
        }
    }

    private function handlePut()
    {
        // PUT-Implementierung
        $this->sendError('PUT method not implemented yet', 501);
    }

    private function handleDelete()
    {
        // DELETE-Implementierung
        $this->sendError('DELETE method not implemented yet', 501);
    }
}

// API instantiieren
new ExampleAPI();
