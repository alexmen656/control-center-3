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
    $dataDir = "/data/projects/" . $userID . "/" . $href;
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    // Initialize git repository
    $cwd = getcwd();
    chdir($dataDir);
    
    // Initialize git if not exists
    if (!is_dir('.git')) {
        exec('git init 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            // Set basic git config
            exec('git config user.email "ide@controlcenter.dev" 2>&1');
            exec('git config user.name "Control Center IDE" 2>&1');
            
            // Create initial files
            file_put_contents('README.md', "# " . $name . "\n\nCreated with Control Center IDE\n");
            file_put_contents('main.js', "// Welcome to " . $name . "\nconsole.log('Hello World!');\n");
            file_put_contents('style.css', "/* Styles for " . $name . " */\nbody {\n    font-family: Arial, sans-serif;\n}\n");
            file_put_contents('index.html', "<!DOCTYPE html>\n<html>\n<head>\n    <title>" . $name . "</title>\n    <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n    <h1>Welcome to " . $name . "</h1>\n    <script src=\"main.js\"></script>\n</body>\n</html>");
            
            // Initial commit
            exec('git add . 2>&1');
            exec('git commit -m "Initial project setup" 2>&1');
        }
    }
    
    chdir($cwd);
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