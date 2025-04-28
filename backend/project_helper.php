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
    $userExists = query("SELECT id FROM control_center_web_builder_users WHERE id='$userID'");
    
    // Wenn der Benutzer nicht existiert, Standard-Admin-Benutzer (ID 1) verwenden
    // oder bei Bedarf einen neuen Benutzer anlegen
    if (mysqli_num_rows($userExists) == 0) {
        $userID = 1; // Admin-Benutzer
    }
    
    // Erstelle einen Eintrag in der control_center_web_builder_projects Tabelle
    $currentDate = date('Y-m-d H:i:s');
    query("INSERT INTO control_center_web_builder_projects 
           (name, description, user_id, created_at, updated_at) 
           VALUES ('$name', 'Web Builder Project for $name', '$userID', '$currentDate', '$currentDate')");
           
    $webBuilderProjectId = mysqli_insert_id($con);
    
    // Erstelle eine Standard-Homepage
    if ($webBuilderProjectId) {
        query("INSERT INTO control_center_web_builder_pages 
               (project_id, name, slug, title, meta_description, is_home, created_at, updated_at) 
               VALUES ('$webBuilderProjectId', 'Home', 'home', 'Homepage', 'Welcome to the $name website', 1, '$currentDate', '$currentDate')");
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
    
    return true;
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