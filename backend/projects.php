<?php
include "head.php";
include "project_helper.php"; // Einbinden der Helper-Funktionen

$headers = getRequestHeaders();

/**
 * Hauptprogrammlogik
 */
if (isset($headers['Authorization'])) {
    $token = escape_string($headers['Authorization']);
    $userData = getUserByToken($token);
    
    if (!$userData) {
        echo jsonResponse("Unauthorized access", false);
        exit;
    }
    
    $userID = $userData['userID'];
    
    // Handler für verschiedene Anfragen
    if (isset($_POST['createProject']) && isset($_POST['projectName'])) {
        // Neues Projekt erstellen
        $name = escape_string($_POST['projectName']);
        $icon = escape_string($_POST['projectIcon']);
        $href = str_replace("\\", "", createLink($name));
        $projectID = generateRandomString(20);
        
        // Prüfe ob das Projekt bereits existiert
        if (projectExists($name)) {
            echo jsonResponse("A project with this name already exists", false);
            exit;
        }
        
        $mysqli = query("INSERT INTO projects VALUES (0, '$icon', '$name', '$href', CURDATE(), '$projectID')");
        
        if (!$mysqli) {
            echo jsonResponse("Failed to create project", false);
            exit;
        }
        
        // Standard-Endpoints mit allen Eigenschaften in einem Array definieren
        $endpointsConfig = [
            [
                'path' => '',
                'title' => 'Project Dashboard',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'new-tool',
                'title' => 'Create new tool',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'manage/tools',
                'title' => 'Manage Tools',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'manage/pages',
                'title' => 'Manage Pages',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'new/page',
                'title' => 'Create New Component',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'info',
                'title' => 'Project Info',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'page/main',
                'title' => 'Main',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'module-store',
                'title' => 'Module Store',
                'icon' => '',
                'isVisible' => 'false'
            ],
            [
                'path' => 'package-manager',
                'title' => 'Package Manager',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'filesystem',
                'title' => 'Filesystem',
                'icon' => 'file-tray-full-outlinepr',
                'isVisible' => 'true'
            ],
            [
                'path' => 'new/service',
                'title' => 'New Service',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'manage/services',
                'title' => 'Manage Services',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'web-builder',
                'title' => 'Web Builder',
                'icon' => 'globe-outline',
                'isVisible' => 'true'
            ]
        ];
        
        // URLs generieren und Seiten erstellen
        $urls = [];
        $pagesQuery = "INSERT INTO control_center_pages VALUES ";
        $pageValues = [];
        
        foreach ($endpointsConfig as $endpoint) {
            $path = $endpoint['path'];
            $title = $endpoint['title'];
            $icon = $endpoint['icon'];
            $isVisible = $endpoint['isVisible'];
            
            // URL für diesen Endpunkt generieren
            $url = "project/" . $href . ($path ? "/" . $path : "");
            $urls[] = $url;
            
            // SQL-Wert für diesen Endpunkt erstellen
            $pageValues[] = "(0, '$url', '$isVisible', '$icon', '$title', '', 0)";
        }
        
        // Alle Seiten in einer SQL-Anweisung einfügen
        $pagesQuery .= implode(', ', $pageValues);
        query($pagesQuery);
        
        // Projekt-Ansichten erstellen
        foreach ($urls as $u) {
            $page = query("SELECT * FROM control_center_pages WHERE url='$u'");
            if (mysqli_num_rows($page) == 1) {
                $page = fetch_assoc($page);
                $pageID = $page['id'];
                query("INSERT INTO control_center_project_views VALUES (0, $pageID, '$projectID')");
            }
        }
        
        // Verzeichnisstruktur erstellen
        if (!createProjectDirectories($href, $name, $projectID)) {
            echo jsonResponse("Failed to create project directories", false);
            exit;
        }
        
        // Haupt-Komponente erstellen
        query("INSERT INTO project_components VALUES (0, 'main.php', 'script', 'Main', 'main', NOW(), NOW(), 'System', '1234567890', '$projectID', NULL)");
        
        // Projekt-Tools erstellen
        query("INSERT INTO project_tools 
              (`id`, `icon`, `name`, `link`, `hasConfig`, `order`, `projectID`) 
              VALUES 
              (0, 'file-tray-full-outline', 'Filesystem', 'filesystem', 0, 0, '$projectID'),
              (0, 'storefront-outline', 'Module Store', 'module-store', 0, 1, '$projectID'),
              (0, 'globe-outline', 'Web Builder', 'web-builder', 0, 2, '$projectID')");
        
        // Web Builder Setup mit Benutzer-ID
        $webBuilderProjectId = setupWebBuilderProject($projectID, $href, $name, $userID);
        
        // Benutzer zum Projekt hinzufügen
        if (!addUserToProject($userID, $projectID)) {
            echo jsonResponse("Failed to add user to project", false);
            exit;
        }
        
        // Standard-Service "My Service" erstellen
        $serviceIcon = "cog-outline";
        $serviceName = "My Service";
        $serviceLink = "my-service";
        $serviceDescription = "Default service for your project";
        
        query("INSERT INTO project_services VALUES (0, '$serviceIcon', '$serviceName', '$serviceLink', '$serviceDescription', 'active', '$projectID')");
        
        // Service-Seiten in Control Center Pages hinzufügen
        $serviceUrl = "project/" . $href . "/services/" . $serviceLink;
        $serviceConfigUrl = $serviceUrl . "/config";
        
        query("INSERT INTO control_center_pages VALUES (0, '$serviceUrl', 'true', '$serviceIcon', '$serviceName', '', 0)");
        query("INSERT INTO control_center_pages VALUES (0, '$serviceConfigUrl', 'true', 'cog-outline', '$serviceName Config', '', 0)");
        
        // Dateisystem erstellen
        if (createFileSystem($projectID)) {
            echo jsonResponse('The project was created successfully. <a href="/paxar/projects/' . $href . '/">Go to the project</a>');
        } else {
            echo jsonResponse('Project created but file system setup failed', false);
        }
        
    } elseif (isset($_POST['deleteProject']) && isset($_POST['projectID'])) {
        // Projekt löschen
        $id = escape_string($_POST['projectID']);
        $mysqli = query("DELETE FROM projects WHERE id='$id'");
        
        if ($mysqli) {
            echo jsonResponse('Project deleted successfully');
        } else {
            echo jsonResponse('Failed to delete project', false);
        }
        
    } elseif (isset($_POST['getProjectInfo']) && isset($_POST['project'])) {
        // Projekt-Informationen abrufen
        $link = escape_string($_POST['project']);
        $project = getProjectByLink($link);
        
        if ($project) {
            echo jsonResponse([
                'icon' => $project['icon'],
                'name' => $project['name'],
                'createdOn' => $project['createdOn']
            ]);
        } else {
            echo jsonResponse("No project found", false);
        }
        
    } elseif (isset($_POST['getProjectUsers']) && isset($_POST['project'])) {
        // Projekt-Benutzer abrufen
        $link = escape_string($_POST['project']);
        $project = getProjectByLink($link);
        
        if ($project) {
            $users = getUsersByProjectID($project['projectID']);
            echo jsonResponse(['users' => $users]);
        } else {
            echo jsonResponse("No project found", false);
        }
        
    } elseif (isset($_POST['getProjectViews']) && isset($_POST['project'])) {
        // Projekt-Ansichten abrufen
        $link = escape_string($_POST['project']);
        $project = getProjectByLink($link);
        
        if ($project) {
            $views = getProjectViewsByProjectID($project['projectID']);
            echo jsonResponse(['views' => $views]);
        } else {
            echo jsonResponse("No project found", false);
        }
        
    } elseif (isset($_POST['addUserToProject']) && isset($_POST['project']) && isset($_POST['email'])) {
        // Benutzer zum Projekt hinzufügen
        $link = escape_string($_POST['project']);
        $email = escape_string($_POST['email']);
        $project = getProjectByLink($link);
        
        if (!$project) {
            echo jsonResponse("No project found", false);
            exit;
        }
        
        $projectID = $project['projectID'];
        $user = query("SELECT * FROM control_center_users WHERE email='$email'");
        
        if (mysqli_num_rows($user) == 1) {
            $newUserID = fetch_assoc($user)['userID'];
            
            if (addUserToProject($newUserID, $projectID)) {
                echo jsonResponse("User added to project successfully");
            } else {
                echo jsonResponse("Failed to add user to project", false);
            }
        } else {
            echo jsonResponse("User not found", false);
        }
        
    } elseif (isset($_POST['checkUserPermissions']) && isset($_POST['project'])) {
        // Benutzerberechtigungen für ein Projekt prüfen
        $link = escape_string($_POST['project']);
        $project = getProjectByLink($link);
        
        if (!$project) {
            echo jsonResponse("No project found", false);
            exit;
        }
        
        $projectID = $project['projectID'];
        $hasPermission = checkUserProjectPermission($userID, $projectID);
        
        if ($hasPermission) {
            echo jsonResponse(["success" => "authorized"]);
        } else {
            echo jsonResponse(["error" => "permission"], false);
        }
        
    } elseif (isset($_POST['openWebBuilder']) && isset($_POST['project'])) {
        // Web Builder für ein bestimmtes Projekt öffnen
        $link = escape_string($_POST['project']);
        $project = getProjectByLink($link);
        
        if (!$project) {
            echo jsonResponse("No project found", false);
            exit;
        }
        
        $projectID = $project['projectID'];
        $webBuilderUrl = getWebBuilderUrl($link);
        
        echo jsonResponse([
            "url" => $webBuilderUrl,
            "projectID" => $projectID,
            "projectName" => $project['name']
        ]);
    } else {
        // Alle Projekte des Benutzers abrufen
        $projects = getUserProjectsByUserID($userID);
        echo json_encode($projects);
    }
} else {
    echo jsonResponse("Authentication required", false);
}
?>