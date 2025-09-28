<?php
include "head.php";

// Cloudflare IP detection (wie in login.php integriert)
function getClientIP() {
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Generate unique slug
function generateSlug($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $slug = '';
    for ($i = 0; $i < $length; $i++) {
        $slug .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $slug;
}

// Check if project exists and user has access
function checkProjectAccess($project_link) {
    global $userID;
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    if (mysqli_num_rows($project) == 0) return false;
    
    $projectData = fetch_assoc($project);
    return checkUserProjectPermission($userID, $projectData['projectID']);
}

// Create database tables
$linksTable = "CREATE TABLE IF NOT EXISTS link_tracker_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projectID VARCHAR(255) NOT NULL,
    title VARCHAR(500) NOT NULL,
    target_url TEXT NOT NULL,
    slug VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_project_slug (projectID, slug)
)";

$visitsTable = "CREATE TABLE IF NOT EXISTS link_tracker_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    referer TEXT,
    visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES link_tracker_links(id) ON DELETE CASCADE
)";

query($linksTable);
query($visitsTable);

if (isset($_POST['createLink'])) {
    $project_link = escape_string($_POST['project']);
    if (!checkProjectAccess($project_link)) {
        echo jsonResponse('Kein Zugriff auf das Projekt');
        exit;
    }

    $title = escape_string($_POST['title']);
    $target_url = escape_string($_POST['target_url']);
    $custom_slug = escape_string($_POST['custom_slug'] ?? '');
    
    // Get project data
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    $projectData = fetch_assoc($project);
    $projectID = $projectData['projectID'];
    
    // Get project domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) > 0) {
        $domain_data = fetch_assoc($domain_result);
        $domain = $domain_data['domain'];
    } else {
        $domain = $project_link . '.links.control-center.eu';
    }
    
    // Generate slug
    if ($custom_slug) {
        $slug_check = query("SELECT id FROM link_tracker_links WHERE slug='$custom_slug' AND projectID='$projectID'");
        if (mysqli_num_rows($slug_check) > 0) {
            echo jsonResponse('Slug bereits vergeben');
            exit;
        }
        $slug = $custom_slug;
    } else {
        do {
            $slug = generateSlug();
        } while(query("SELECT id FROM link_tracker_links WHERE slug='$slug' AND projectID='$projectID'") && mysqli_num_rows(query("SELECT id FROM link_tracker_links WHERE slug='$slug' AND projectID='$projectID'")) > 0);
    }
    
    $result = query("INSERT INTO link_tracker_links (projectID, title, target_url, slug) VALUES ('$projectID', '$title', '$target_url', '$slug')");
    
    if ($result) {
        $short_url = "https://$slug.$domain/";
        echo jsonResponse(['success' => true, 'short_url' => $short_url, 'slug' => $slug]);
    } else {
        echo jsonResponse('Fehler beim Erstellen des Links');
    }
} elseif (isset($_POST['getLinks'])) {
    $project_link = escape_string($_POST['project']);
    if (!checkProjectAccess($project_link)) {
        echo jsonResponse('Kein Zugriff auf das Projekt');
        exit;
    }
    
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    $projectData = fetch_assoc($project);
    $projectID = $projectData['projectID'];
    
    // Get domain
    $domain_result = query("SELECT domain FROM control_center_project_domains WHERE project='$project_link'");
    if (mysqli_num_rows($domain_result) > 0) {
        $domain_data = fetch_assoc($domain_result);
        $domain = $domain_data['domain'];
    } else {
        $domain = $project_link . '.links.control-center.eu';
    }
    
    $links = [];
    $result = query("SELECT l.*, COUNT(v.id) as visits FROM link_tracker_links l 
                     LEFT JOIN link_tracker_visits v ON l.id = v.link_id 
                     WHERE l.projectID='$projectID' 
                     GROUP BY l.id 
                     ORDER BY l.created_at DESC");
    
    while ($row = fetch_assoc($result)) {
        $row['short_url'] = "https://{$row['slug']}.$domain/";
        $links[] = $row;
    }
    
    echo jsonResponse(['links' => $links]);
} elseif (isset($_POST['deleteLink'])) {
    $project_link = escape_string($_POST['project']);
    $link_id = escape_string($_POST['link_id']);
    
    if (!checkProjectAccess($project_link)) {
        echo jsonResponse('Kein Zugriff auf das Projekt');
        exit;
    }
    
    $project = query("SELECT * FROM projects WHERE link='$project_link'");
    $projectData = fetch_assoc($project);
    $projectID = $projectData['projectID'];
    
    $result = query("DELETE FROM link_tracker_links WHERE id='$link_id' AND projectID='$projectID'");
    
    if ($result) {
        echo jsonResponse('Link gelöscht');
    } else {
        echo jsonResponse('Fehler beim Löschen');
    }
    
    if ($action === 'create_link') {
        $title = escape_string($input['title']);
        $description = escape_string($input['description'] ?? '');
        $targetUrl = escape_string($input['targetUrl']);
        $customSlug = escape_string($input['slug'] ?? '');
        $expiresAt = $input['expiresAt'] ? escape_string($input['expiresAt']) : 'NULL';
        
        // Get project info from session/user context
        $project = getCurrentProject(); // You'll need to implement this based on your session management
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        $projectDomain = $project['project_domain'] ?? $project['link'] . '.links.control-center.eu';
        
        // Setup link tracking for project if not done yet
        setupLinkTrackerForProject($projectID, $projectDomain);
        
        // Generate or validate slug
        if ($customSlug) {
            if (slugExists($customSlug, $projectID)) {
                echo echoJSON('Slug already exists', false);
                exit;
            }
            $slug = $customSlug;
        } else {
            do {
                $slug = generateSlug();
            } while (slugExists($slug, $projectID));
        }
        
        $shortUrl = "https://" . $projectDomain . "/" . $slug;
        
        $expiresAtValue = ($expiresAt !== 'NULL') ? "'$expiresAt'" : 'NULL';
        
        $result = query("
            INSERT INTO link_tracker_links 
            (projectID, title, description, target_url, slug, short_url, expires_at) 
            VALUES 
            ('$projectID', '$title', '$description', '$targetUrl', '$slug', '$shortUrl', $expiresAtValue)
        ");
        
        if ($result) {
            echo echoJSON([
                'success' => true,
                'link' => [
                    'id' => mysqli_insert_id($con),
                    'title' => $title,
                    'shortUrl' => $shortUrl,
                    'targetUrl' => $targetUrl,
                    'slug' => $slug
                ]
            ]);
        } else {
            echo echoJSON('Failed to create link', false);
        }
    }
    
    elseif ($action === 'delete_link') {
        $linkId = escape_string($input['id']);
        $project = getCurrentProject();
        
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        
        // Verify link belongs to project
        $linkCheck = query("SELECT id FROM link_tracker_links WHERE id = '$linkId' AND projectID = '$projectID'");
        if (mysqli_num_rows($linkCheck) === 0) {
            echo echoJSON('Link not found or no permission', false);
            exit;
        }
        
        $result = query("DELETE FROM link_tracker_links WHERE id = '$linkId' AND projectID = '$projectID'");
        
        if ($result) {
            echo echoJSON('Link deleted successfully');
        } else {
            echo echoJSON('Failed to delete link', false);
        }
    }
    
    elseif ($action === 'update_link_status') {
        $linkId = escape_string($input['id']);
        $status = escape_string($input['status']);
        $project = getCurrentProject();
        
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        
        $result = query("
            UPDATE link_tracker_links 
            SET status = '$status' 
            WHERE id = '$linkId' AND projectID = '$projectID'
        ");
        
        if ($result) {
            echo echoJSON('Link status updated');
        } else {
            echo echoJSON('Failed to update link status', false);
        }
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = ['action'] ?? '';
    
    if ($action === 'analytics') {
        $period = escape_string(['period'] ?? '30');
        $project = getCurrentProject();
        
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        
        $dateCondition = "clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)";
        
        $analytics = query("
            SELECT a.*, l.title, l.slug, l.short_url
            FROM link_tracker_analytics a
            JOIN link_tracker_links l ON a.link_id = l.id
            WHERE l.projectID = '$projectID' AND $dateCondition
            ORDER BY a.clicked_at DESC
        ");
        
        $analyticsData = [];
        while ($row = fetch_assoc($analytics)) {
            $analyticsData[] = $row;
        }
        
        echo echoJSON(['analytics' => $analyticsData]);
    }
    
    elseif ($action === 'links') {
        $project = getCurrentProject();
        
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        
        $links = query("
            SELECT l.*, 
                   COUNT(a.id) as clicks,
                   COUNT(DISTINCT a.ip_address) as unique_visitors,
                   COUNT(CASE WHEN DATE(a.clicked_at) = CURDATE() THEN 1 END) as clicks_today,
                   COUNT(CASE WHEN YEARWEEK(a.clicked_at) = YEARWEEK(NOW()) THEN 1 END) as clicks_this_week
            FROM link_tracker_links l
            LEFT JOIN link_tracker_analytics a ON l.id = a.link_id
            WHERE l.projectID = '$projectID'
            GROUP BY l.id
            ORDER BY l.created_at DESC
        ");
        
        $linksData = [];
        while ($row = fetch_assoc($links)) {
            $linksData[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'targetUrl' => $row['target_url'],
                'shortUrl' => $row['short_url'],
                'slug' => $row['slug'],
                'status' => $row['status'],
                'created' => $row['created_at'],
                'clicks' => intval($row['clicks']),
                'uniqueVisitors' => intval($row['unique_visitors']),
                'clicksToday' => intval($row['clicks_today']),
                'clicksThisWeek' => intval($row['clicks_this_week'])
            ];
        }
        
        echo echoJSON(['links' => $linksData]);
    }
    
    elseif ($action === 'link_details') {
        $linkId = escape_string(['id']);
        $project = getCurrentProject();
        
        if (!$project) {
            echo echoJSON('No project found', false);
            exit;
        }
        
        $projectID = $project['projectID'];
        
        $link = query("
            SELECT l.*, 
                   COUNT(a.id) as clicks,
                   COUNT(DISTINCT a.ip_address) as unique_visitors,
                   COUNT(CASE WHEN DATE(a.clicked_at) = CURDATE() THEN 1 END) as clicks_today,
                   COUNT(CASE WHEN YEARWEEK(a.clicked_at) = YEARWEEK(NOW()) THEN 1 END) as clicks_this_week
            FROM link_tracker_links l
            LEFT JOIN link_tracker_analytics a ON l.id = a.link_id
            WHERE l.id = '$linkId' AND l.projectID = '$projectID'
            GROUP BY l.id
        ");
        
        if (mysqli_num_rows($link) === 0) {
            echo echoJSON('Link not found', false);
            exit;
        }
        
        $linkData = fetch_assoc($link);
        $linkDetails = [
            'id' => $linkData['id'],
            'title' => $linkData['title'],
            'description' => $linkData['description'],
            'targetUrl' => $linkData['target_url'],
            'shortUrl' => $linkData['short_url'],
            'slug' => $linkData['slug'],
            'status' => $linkData['status'],
            'created' => $linkData['created_at'],
            'clicks' => intval($linkData['clicks']),
            'uniqueVisitors' => intval($linkData['unique_visitors']),
            'clicksToday' => intval($linkData['clicks_today']),
            'clicksThisWeek' => intval($linkData['clicks_this_week'])
        ];
        
        echo echoJSON(['link' => $linkDetails]);
    }
    
    elseif ($action === 'redirect') {
        // This handles the actual link redirection and tracking
        $slug = escape_string(['slug'] ?? '');
        
        if (!$slug) {
            header("HTTP/1.0 404 Not Found");
            echo "Link not found";
            exit;
        }
        
        // Find the link
        $linkResult = query("
            SELECT * FROM link_tracker_links 
            WHERE slug = '$slug' AND status = 'active'
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        if (mysqli_num_rows($linkResult) === 0) {
            header("HTTP/1.0 404 Not Found");
            echo "Link not found or expired";
            exit;
        }
        
        $link = fetch_assoc($linkResult);
        
        // Track the click
        $trackingData = getTrackingData();
        $linkId = $link['id'];
        $ipAddress = escape_string($trackingData['ip_address']);
        $userAgent = escape_string($trackingData['user_agent']);
        $referer = escape_string($trackingData['referer']);
        $country = escape_string($trackingData['country']);
        
        query("
            INSERT INTO link_tracker_analytics 
            (link_id, ip_address, user_agent, referer, country) 
            VALUES 
            ('$linkId', '$ipAddress', '$userAgent', '$referer', '$country')
        ");
        
        // Redirect to target URL
        header("Location: " . $link['target_url']);
        exit;
    }
}

// Helper function to get current project with domain integration
function getCurrentProject() {
    global $userID;

    if (!$userID) {
        return null;
    }
    
    // Get the current project from URL or session
    $projectLink = $_GET['project'] ?? null;
    
    if ($projectLink) {
        $result = query("
            SELECT p.*, pd.domain as project_domain 
            FROM projects p 
            LEFT JOIN control_center_project_domains pd ON p.link = pd.project 
            WHERE p.link = '$projectLink'
        ");
        
        if (mysqli_num_rows($result) > 0) {
            //echo "skskskss";
            $project = fetch_assoc($result);
            
            // If no custom domain is set, use a default pattern
            if (!$project['project_domain']) {
                $project['project_domain'] = $projectLink . '.links.control-center.eu';
            }
            
            return $project;
        }
    }
    
    return null;
}

// Function to get project by domain (for redirect handling)
function getProjectByDomain($domain) {
    $domain = escape_string($domain);
    
    $result = query("
        SELECT p.*, pd.domain as project_domain 
        FROM projects p 
        JOIN control_center_project_domains pd ON p.link = pd.project 
        WHERE pd.domain = '$domain'
    ");
    
    if (mysqli_num_rows($result) > 0) {
        return fetch_assoc($result);
    }
    
    return null;
}

// Function to setup link tracking for a project
function setupLinkTrackerForProject($projectID, $domain) {
    // Check if already setup
    $existing = query("
        SELECT id FROM link_tracker_project_settings 
        WHERE projectID = '$projectID'
    ");
    
    if (mysqli_num_rows($existing) === 0) {
        // Create project settings
        query("
            INSERT INTO link_tracker_project_settings 
            (projectID, domain, created_at) 
            VALUES ('$projectID', '$domain', NOW())
        ");
    }
    
    return true;
}
?>