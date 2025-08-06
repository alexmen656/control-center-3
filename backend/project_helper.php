<?php
/**
 * project_helper.php
 * 
 * Helper-Funktionen für Projektoperationen im Control Center
 */

/**
 * Erstellt das Hauptverzeichnis für das Dateisystem eines Projekts
 *
 * @param string $projectID Die Projekt-ID
 * @return bool True bei Erfolg, False bei Fehler
 */
function createFileSystemMainDir($projectID) {
    $dir = "/data/project_filesystems/" . $projectID;
    if (!mkdir($dir, 0777, true)) {
        return false;
    }
    
    return chmod($dir, 0777);
}

/**
 * Erstellt einen Dateisystem-Eintrag in der Datenbank und das zugehörige Verzeichnis
 *
 * @param string $projectID Die Projekt-ID
 * @return bool True bei Erfolg, False bei Fehler
 */
function createFileSystem($projectID) {
    if (!query("INSERT INTO project_filesystem VALUES (0, '', '', NULL, 0, '$projectID')")) {
        return false;
    }
    
    return createFileSystemMainDir($projectID);
}

/**
 * Findet ein Projekt anhand des Link-Namens
 *
 * @param string $link Der Link-Name des Projekts
 * @return array|null Die Projektdaten oder null, wenn nicht gefunden
 */
function getProjectByLink($link) {
    $link = escape_string($link);
    $query = query("SELECT * FROM projects WHERE link='$link'");
    
    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

/**
 * Findet ein Projekt anhand der Projekt-ID
 *
 * @param string $projectID Die Projekt-ID
 * @return array|null Die Projektdaten oder null, wenn nicht gefunden
 */
function getProjectByID($projectID) {
    $projectID = escape_string($projectID);
    $query = query("SELECT * FROM projects WHERE projectID='$projectID'");
    
    return (mysqli_num_rows($query) == 1) ? fetch_assoc($query) : null;
}

/**
 * Findet einen Benutzer anhand des Login-Tokens
 *
 * @param string $token Das Login-Token
 * @return array|null Die Benutzerdaten oder null, wenn nicht gefunden
 */
function getUserByToken($token) {
    $token = escape_string($token);
    $data = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
    
    return (mysqli_num_rows($data) == 1) ? fetch_assoc($data) : null;
}

/**
 * Ruft alle Benutzer eines Projekts ab
 *
 * @param string $projectID Die Projekt-ID
 * @return array Ein Array mit Benutzerdaten
 */
function getUsersByProjectID($projectID) {
    $users = query("SELECT * FROM control_center_user_projects WHERE projectID='$projectID'");
    $result = [];
    
    if (mysqli_num_rows($users) > 0) {
        foreach ($users as $user) {
            $userID = $user['userID'];
            $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));
            
            if ($userData) {
                $result[] = [
                    'id' => $userData['userID'],
                    'name' => $userData['firstname'] . " " . $userData['lastname'],
                    'email' => $userData['email']
                ];
            }
        }
    }
    
    return $result;
}

/**
 * Ruft alle Ansichten eines Projekts ab
 *
 * @param string $projectID Die Projekt-ID
 * @return array Ein Array mit Ansichtsdaten
 */
function getProjectViewsByProjectID($projectID) {
    $views = query("SELECT * FROM control_center_project_views WHERE projectID='$projectID'");
    $result = [];
    
    if (mysqli_num_rows($views) > 0) {
        foreach ($views as $view) {
            $viewID = $view['pageID'];
            $viewData = fetch_assoc(query("SELECT * FROM control_center_pages WHERE id='$viewID'"));
            
            if ($viewData) {
                $result[] = [
                    'id' => $viewData['id'],
                    'name' => $viewData['title'],
                    'url' => $viewData['url'],
                    'icon' => $viewData['icon']
                ];
            }
        }
    }
    
    return $result;
}

/**
 * Ruft alle Projekte eines Benutzers ab
 *
 * @param int $userID Die Benutzer-ID
 * @return array Ein Array mit Projektdaten
 */
function getUserProjectsByUserID($userID) {
    $projects = query("SELECT * FROM control_center_user_projects WHERE userID='$userID'");
    $result = [];
    
    foreach ($projects as $p) {
        $projectID = $p['projectID'];
        $project = query("SELECT * FROM projects WHERE projectID='$projectID'");
        
        if (mysqli_num_rows($project) == 1) {
            $projectData = fetch_assoc($project);
            $result[] = [
                "id" => $projectData['id'],
                "icon" => $projectData['icon'],
                "name" => $projectData['name'],
                "link" => $projectData['link']
            ];
        }
    }
    
    return $result;
}

/**
 * Prüft, ob ein Benutzer Berechtigungen für ein Projekt hat
 *
 * @param int $userID Die Benutzer-ID
 * @param string $projectID Die Projekt-ID
 * @return bool True wenn Berechtigungen vorhanden, sonst False
 */
function checkUserProjectPermission($userID, $projectID) {
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
    return mysqli_num_rows($check) == 1;
}

/**
 * Erzeugt eine standardisierte JSON-Antwort
 *
 * @param mixed $data Die Daten oder Nachricht
 * @param bool $success True für Erfolg, False für Fehler
 * @return string Die JSON-Antwort
 */
function jsonResponse($data, $success = true) {
    if ($success) {
        if (is_string($data)) {
            $response = ['success' => true, 'message' => $data];
        } else {
            $response = array_merge(['success' => true], $data);
        }
    } else {
        $response = ['success' => false, 'message' => $data];
    }
    
    return echoJson($response);
}

/**
 * Richtet ein Web Builder Projekt ein
 *
 * @param string $projectID Die Projekt-ID
 * @param string $href Der Link-Name des Projekts
 * @param string $name Der Anzeigename des Projekts
 * @param int $userID Die Benutzer-ID des Erstellers (optional)
 * @return int|bool Die ID des Web Builder Projekts oder False bei Fehler
 */
function setupWebBuilderProject($projectID, $href, $name, $userID = 1) {
    global $con;
    
    // Zuerst prüfen, ob der Benutzer in der Web Builder Tabelle existiert
    //$userExists = query("SELECT id FROM control_center_web_builder_users WHERE id='$userID'");
    $username = escape_string("user_$userID"). "_". $userID;
    $userExists = query("SELECT id, username FROM control_center_web_builder_users WHERE username='user_$userID' OR username='$username' OR id='$userID'");
    // Wenn der Benutzer nicht existiert, einen neuen Benutzer anlegen
    if (mysqli_num_rows($userExists) == 0) {
        // Benutzerinformationen aus der Haupttabelle holen
        $userData = fetch_assoc(query("SELECT * FROM control_center_users WHERE userID='$userID'"));
        
        if ($userData) {
            // Benutzer in der Web Builder Tabelle anlegen
            $firstName = escape_string($userData['firstname']);
            $lastName = escape_string($userData['lastname']);
            $email = escape_string($userData['email']);
            $currentDate = date('Y-m-d H:i:s');
            
            query("INSERT INTO control_center_web_builder_users 
                  (id, username, email, created_at, updated_at) 
                  VALUES ('$userID', 'user_$userID', '$email', '$currentDate', '$currentDate')");
                  //$firstName $lastName
            // Wenn das nicht geklappt hat, Admin-Benutzer verwenden
            if (mysqli_affected_rows($con) == 0) {
                $userID = 1; // Admin-Benutzer
            }
        } else {
            $userID = 1; // Admin-Benutzer, wenn keine Benutzerdaten gefunden wurden
        }
    }else{
        // Benutzer existiert bereits, also verwenden wir die vorhandene ID
        $userID = mysqli_fetch_assoc($userExists)['id'];
    }

    // Erstelle einen Eintrag in der control_center_web_builder_projects Tabelle
    // mit eindeutiger Referenz zum Control Center Projekt
    $currentDate = date('Y-m-d H:i:s');
    $description = "Web Builder Project for $name (Control Center Project ID: $projectID)";
    
    query("INSERT INTO control_center_web_builder_projects 
           (name, description, user_id, created_at, updated_at) 
           VALUES ('$name', '$description', '$userID', '$currentDate', '$currentDate')");
           
    $webBuilderProjectId = mysqli_insert_id($con);
    
    // Erstelle eine Standard-Homepage
    if ($webBuilderProjectId) {
        query("INSERT INTO control_center_web_builder_pages 
               (project_id, name, slug, title, meta_description, is_home, created_at, updated_at) 
               VALUES ('$webBuilderProjectId', 'Home', 'home', 'Homepage', 'Welcome to the $name website', 1, '$currentDate', '$currentDate')");
               
        // Erstelle eine erste HTML-Komponente für die Homepage
        $homepageId = mysqli_insert_id($con);
        if ($homepageId) {
            // Generiere UUID für die Komponente
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
              mt_rand(0, 0xffff), mt_rand(0, 0xffff),
              mt_rand(0, 0xffff),
              mt_rand(0, 0x0fff) | 0x4000,
              mt_rand(0, 0x3fff) | 0x8000,
              mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $defaultHTML = "<div class=\"container mt-5\"><h1>Welcome to $name</h1><p>This is your new homepage. Start editing to customize it!</p></div>";
            
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, html_code, position, created_at, updated_at) 
                  VALUES ('$homepageId', '$uuid', '$defaultHTML', 0, '$currentDate', '$currentDate')");
        }
        
        // Auch einen Benutzer-Projekt-Eintrag in der Web Builder Tabelle erstellen
        query("INSERT INTO control_center_user_projects 
               (userID, projectID, role) 
               VALUES ('$userID', '$webBuilderProjectId', 'owner')");


// , created_at, updated_at
// /, '$currentDate', '$currentDate'
    }
    
    return $webBuilderProjectId;
}

/**
 * Prüft, ob ein Projekt bereits existiert
 *
 * @param string $name Der Name des Projekts
 * @return bool True wenn das Projekt existiert, sonst False
 */
function projectExists($name) {
    $name = escape_string($name);
    $href = str_replace("\\", "", createLink($name));
    $check = query("SELECT * FROM projects WHERE link='$href' OR name='$name'");
    
    return mysqli_num_rows($check) > 0;
}

/**
 * Erstellt die Verzeichnisstruktur für ein neues Projekt
 *
 * @param string $href Der Link-Name des Projekts
 * @param string $name Der Anzeigename des Projekts
 * @param string $projectID Die Projekt-ID
 * @return bool True bei Erfolg, False bei Fehler
 */
function createProjectDirectories($href, $name, $projectID) {
    global $userID; // Make userID available
    
    // Create web directory
    if (!mkdir("/www/" . $href, 0777, true) || !chmod("/www/" . $href, 0777)) {
        return false;
    }
    
    // Template-Dateien erstellen
    $indexContent = str_replace(
        array('[{[pLink]}]', '[{[pName]}]', '[{[pID]}]'), 
        array($href, $name, $projectID), 
        file_get_contents("templates/website/index.php")
    );
    
    file_put_contents("/www/" . $href . "/index.php", $indexContent, 0777);
    chmod("/www/" . $href . "/index.php", 0777);
    file_put_contents("/www/" . $href . "/main.php", "//Put here main content of your site", 0777);
    chmod("/www/" . $href . "/main.php", 0777);
    
    // Create Monaco IDE data directory
    createMonacoProjectDirectory($href, $name, $userID);
    
    return true;
}

/**
 * Creates a Monaco IDE project directory structure
 */
function createMonacoProjectDirectory($href, $name, $userID) {
    // Create project data directory for Monaco IDE
    $dataDir = __DIR__ . "/../data/projects/" . $userID . "/" . $href;
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    
    // Initialize Monaco IDE metadata instead of git
    if (!file_exists($dataDir . '/.monaco_initialized')) {
        // Create initial Monaco IDE metadata files
        file_put_contents($dataDir . '/.monaco_initialized', json_encode([
            'initialized' => true,
            'created' => date('c'),
            'project_name' => $name
        ]));
        
        file_put_contents($dataDir . '/.monaco_staged.json', '{}');
        file_put_contents($dataDir . '/.monaco_lastcommit.json', '{}');
        file_put_contents($dataDir . '/.monaco_commits.json', '[]');
        
        // Create initial files
        //file_put_contents($dataDir . '/README.md', "# " . $name . "\n\nCreated with Control Center IDE\n");
        file_put_contents($dataDir . '/main.js', "// Welcome to " . $name . "\nconsole.log('Hello World!');\n");
        file_put_contents($dataDir . '/style.css', "/* Styles for " . $name . " */\nbody {\n    font-family: Arial, sans-serif;\n}\n");
        file_put_contents($dataDir . '/index.html', "<!DOCTYPE html>\n<html>\n<head>\n    <title>" . $name . "</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <h1>Welcome to " . $name . "</h1>\n    <script src=\"main.js\"></script>\n</body>\n</html>");
        
        // Create initial commit metadata
        $initialCommit = [
            'hash' => 'initial-' . time(),
            'short_hash' => 'initial',
            'author' => 'Control Center IDE',
            'email' => 'ide@controlcenter.dev',
            'date' => date('c'),
            'message' => 'Initial project setup',
            'files' => [
               // ['path' => 'README.md'],
                ['path' => 'main.js'],
                ['path' => 'style.css'],
                ['path' => 'index.html']
            ],
            'parents' => []
        ];
        
        file_put_contents($dataDir . '/.monaco_commits.json', json_encode([$initialCommit], JSON_PRETTY_PRINT));
        
        // Set last commit state
        $lastCommit = [];
        $files = ['main.js', 'style.css', 'index.html'];//'README.md', 
        foreach ($files as $file) {
            $lastCommit[$file] = md5(file_get_contents($dataDir . '/' . $file));
        }
        file_put_contents($dataDir . '/.monaco_lastcommit.json', json_encode($lastCommit, JSON_PRETTY_PRINT));
        
        // Create CMS APIs setup and copy subscribed APIs
        createCMSAPISetup($dataDir, $projectID);
    }
}

/**
 * Creates the CMS APIs setup for a Monaco project
 */
function createCMSAPISetup($projectDir, $projectID) {
    // Create .monaco_apis directory
    $apisDir = $projectDir . '/.monaco_apis';
    if (!is_dir($apisDir)) {
        mkdir($apisDir, 0777, true);
    }
    
    // Create the main APIs index file (empty initially)
    $indexContent = '// CMS APIs Integration
// This file contains all subscribed APIs for your project
// Subscribe to APIs in the main Control Center to get access

// No APIs are currently subscribed for this project
// When you subscribe to APIs, they will be available here like:
// import { UsersAPI, FilesAPI } from \'apis\';

export default {};
';
    
    file_put_contents($apisDir . '/index.js', $indexContent);
    
    // Create API configuration template
    $configContent = '// CMS API Configuration
// This file contains API keys and configuration for your project
// Keys are automatically injected at runtime

const API_CONFIG = {
  baseUrl: \'/backend/api/v1\',
  timeout: 30000,
  retries: 3,
  
  // API keys will be injected here automatically
  keys: {
    // Auto-populated based on your subscriptions
  }
};

export default API_CONFIG;
';
    
    file_put_contents($apisDir . '/config.js', $configContent);
    
    // Create a README for developers
    $readmeContent = '# CMS APIs Integration

This directory contains the CMS APIs integration for your project.

## Currently Available APIs

No APIs are currently subscribed.

## Usage

To start using APIs:
1. Go to your project in Control Center
2. Navigate to the API Management section  
3. Subscribe to the APIs you need
4. Refresh your Monaco project to get the latest SDKs

Once subscribed, import APIs like this:
```javascript
import { UsersAPI, FilesAPI } from \'apis\';

// Use the APIs
const users = await UsersAPI.getAll();
const uploadResult = await FilesAPI.upload(file);
```

## Files in this directory

- `index.js` - Main API exports (auto-generated when APIs are subscribed)
- `config.js` - API configuration  
- `*SDK.js` - Individual API SDKs (copied when APIs are subscribed)

## Security

- API keys are never exposed in your code
- All requests are authenticated automatically
- Rate limiting is enforced per project
';
    
    file_put_contents($apisDir . '/README.md', $readmeContent);
}

/**
 * Fügt einen Benutzer zu einem Projekt hinzu
 *
 * @param int $userID Die Benutzer-ID
 * @param string $projectID Die Projekt-ID
 * @return bool True bei Erfolg, False bei Fehler
 */
function addUserToProject($userID, $projectID) {
    $userID = (int)$userID;
    $projectID = escape_string($projectID);
    $check = query("SELECT * FROM control_center_user_projects WHERE userID=$userID AND projectID='$projectID'");
    
    if (mysqli_num_rows($check) == 0) {
        return (bool)query("INSERT INTO control_center_user_projects VALUES (0, $userID, '$projectID', 1)");
    }
    
    return true; // Benutzer ist bereits Teil des Projekts
}

/**
 * Generiert die Web Builder URL für ein Projekt
 * 
 * @param string $projectLink Der Link-Name des Projekts
 * @param string $page Optional: Die Seite im Web Builder
 * @return string Die vollständige Web Builder URL
 */
function getWebBuilderUrl($projectLink, $page = '') {
    $baseUrl = "https://web-builder.control-center.eu/";
    
    if (!empty($page)) {
        return $baseUrl . "editor/" . $projectLink . "/" . $page;
    }
    
    return $baseUrl . "project/" . $projectLink;
}
?>