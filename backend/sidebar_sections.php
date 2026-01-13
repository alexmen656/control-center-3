<?php
include 'head.php';

// Helper function to check if user has access to project
function userHasProjectAccess($userID, $projectID) {
    $result = query("SELECT * FROM control_center_user_projects WHERE userID='$userID' AND projectID='$projectID'");
    return mysqli_num_rows($result) > 0;
}

// GET - List all sections for a project
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['project'])) {
    $projectID = getProjectID($_GET['project']);
    
    if (!$projectID) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }
    
    if (!userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $sections = query("SELECT * FROM project_sidebar_sections WHERE projectID='$projectID' ORDER BY order_index ASC");
    $result = [];
    
    if (mysqli_num_rows($sections) > 0) {
        foreach ($sections as $section) {
            $sectionId = $section['id'];
            
            // Get tool count for this section
            $toolCount = mysqli_num_rows(query("SELECT id FROM project_tools WHERE section_id='$sectionId'"));
            
            $result[] = [
                'id' => (int)$section['id'],
                'name' => $section['name'],
                'slug' => $section['slug'],
                'icon' => $section['icon'],
                'order_index' => (int)$section['order_index'],
                'is_default' => (bool)$section['is_default'],
                'is_collapsible' => (bool)$section['is_collapsible'],
                'show_add_button' => (bool)$section['show_add_button'],
                'add_button_route' => $section['add_button_route'],
                'info_route' => $section['info_route'],
                'manage_route' => $section['manage_route'],
                'tool_count' => $toolCount,
                'created_at' => $section['created_at'],
                'updated_at' => $section['updated_at']
            ];
        }
    }
    
    echo json_encode(['sections' => $result]);
    exit;
}

// GET - Get section templates
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['templates'])) {
    $templates = query("SELECT * FROM sidebar_section_templates ORDER BY default_order ASC");
    $result = [];
    
    if (mysqli_num_rows($templates) > 0) {
        foreach ($templates as $template) {
            $result[] = [
                'id' => (int)$template['id'],
                'name' => $template['name'],
                'slug' => $template['slug'],
                'icon' => $template['icon'],
                'default_order' => (int)$template['default_order'],
                'description' => $template['description'],
                'is_system' => (bool)$template['is_system']
            ];
        }
    }
    
    echo json_encode(['templates' => $result]);
    exit;
}

// POST - Create new section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createSection'])) {
    $projectName = escape_string($_POST['project'] ?? '');
    $name = escape_string($_POST['name'] ?? '');
    $icon = escape_string($_POST['icon'] ?? 'folder-outline');
    $slug = escape_string($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));
    
    if (empty($projectName) || empty($name)) {
        echo json_encode(['error' => 'Project and name are required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID) {
        echo json_encode(['error' => 'Project not found']);
        exit;
    }
    
    if (!userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Get next order index
    $maxOrder = fetch_assoc(query("SELECT MAX(order_index) as max_order FROM project_sidebar_sections WHERE projectID='$projectID'"));
    $orderIndex = ($maxOrder['max_order'] ?? 0) + 1;
    
    // Optional fields
    $isCollapsible = isset($_POST['is_collapsible']) ? (int)$_POST['is_collapsible'] : 1;
    $showAddButton = isset($_POST['show_add_button']) ? (int)$_POST['show_add_button'] : 1;
    $addButtonRoute = escape_string($_POST['add_button_route'] ?? '');
    $infoRoute = escape_string($_POST['info_route'] ?? '');
    $manageRoute = escape_string($_POST['manage_route'] ?? '');
    
    $result = query("INSERT INTO project_sidebar_sections 
        (projectID, name, slug, icon, order_index, is_collapsible, show_add_button, add_button_route, info_route, manage_route) 
        VALUES ('$projectID', '$name', '$slug', '$icon', '$orderIndex', '$isCollapsible', '$showAddButton', '$addButtonRoute', '$infoRoute', '$manageRoute')");
    
    if ($result) {
        $newId = mysqli_insert_id($GLOBALS['con']);
        echo json_encode([
            'success' => true,
            'section' => [
                'id' => $newId,
                'name' => $name,
                'slug' => $slug,
                'icon' => $icon,
                'order_index' => $orderIndex
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create section']);
    }
    exit;
}

// POST - Update section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSection'])) {
    $sectionId = intval($_POST['section_id'] ?? 0);
    $projectName = escape_string($_POST['project'] ?? '');
    
    if (!$sectionId || empty($projectName)) {
        echo json_encode(['error' => 'Section ID and project are required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID || !userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Build update query dynamically
    $updates = [];
    if (isset($_POST['name'])) $updates[] = "name='" . escape_string($_POST['name']) . "'";
    if (isset($_POST['icon'])) $updates[] = "icon='" . escape_string($_POST['icon']) . "'";
    if (isset($_POST['slug'])) $updates[] = "slug='" . escape_string($_POST['slug']) . "'";
    if (isset($_POST['order_index'])) $updates[] = "order_index=" . intval($_POST['order_index']);
    if (isset($_POST['is_collapsible'])) $updates[] = "is_collapsible=" . intval($_POST['is_collapsible']);
    if (isset($_POST['show_add_button'])) $updates[] = "show_add_button=" . intval($_POST['show_add_button']);
    if (isset($_POST['add_button_route'])) $updates[] = "add_button_route='" . escape_string($_POST['add_button_route']) . "'";
    if (isset($_POST['info_route'])) $updates[] = "info_route='" . escape_string($_POST['info_route']) . "'";
    if (isset($_POST['manage_route'])) $updates[] = "manage_route='" . escape_string($_POST['manage_route']) . "'";
    
    if (empty($updates)) {
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }
    
    $updateStr = implode(', ', $updates);
    $result = query("UPDATE project_sidebar_sections SET $updateStr WHERE id='$sectionId' AND projectID='$projectID'");
    
    echo json_encode(['success' => (bool)$result]);
    exit;
}

// POST - Delete section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteSection'])) {
    $sectionId = intval($_POST['section_id'] ?? 0);
    $projectName = escape_string($_POST['project'] ?? '');
    
    if (!$sectionId || empty($projectName)) {
        echo json_encode(['error' => 'Section ID and project are required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID || !userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Check if section is default (cannot delete default sections)
    $section = fetch_assoc(query("SELECT is_default FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'"));
    if ($section && $section['is_default']) {
        echo json_encode(['error' => 'Cannot delete default section']);
        exit;
    }
    
    // Move tools from this section to uncategorized (section_id = NULL)
    query("UPDATE project_tools SET section_id = NULL WHERE section_id='$sectionId'");
    
    // Delete the section
    $result = query("DELETE FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'");
    
    echo json_encode(['success' => (bool)$result]);
    exit;
}

// POST - Reorder sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorderSections'])) {
    $projectName = escape_string($_POST['project'] ?? '');
    $order = json_decode($_POST['order'] ?? '[]', true);
    
    if (empty($projectName) || empty($order)) {
        echo json_encode(['error' => 'Project and order are required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID || !userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    foreach ($order as $index => $sectionId) {
        $sectionId = intval($sectionId);
        query("UPDATE project_sidebar_sections SET order_index='$index' WHERE id='$sectionId' AND projectID='$projectID'");
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// POST - Assign tool to section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignToolToSection'])) {
    $toolId = intval($_POST['tool_id'] ?? 0);
    $sectionId = intval($_POST['section_id'] ?? 0); // 0 means uncategorized
    $projectName = escape_string($_POST['project'] ?? '');
    
    if (!$toolId || empty($projectName)) {
        echo json_encode(['error' => 'Tool ID and project are required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID || !userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Verify tool belongs to this project
    $tool = query("SELECT id FROM project_tools WHERE id='$toolId' AND projectID='$projectID'");
    if (mysqli_num_rows($tool) == 0) {
        echo json_encode(['error' => 'Tool not found in this project']);
        exit;
    }
    
    // If section_id is provided, verify it belongs to this project
    if ($sectionId > 0) {
        $section = query("SELECT id FROM project_sidebar_sections WHERE id='$sectionId' AND projectID='$projectID'");
        if (mysqli_num_rows($section) == 0) {
            echo json_encode(['error' => 'Section not found in this project']);
            exit;
        }
    }
    
    $sectionValue = $sectionId > 0 ? "'$sectionId'" : "NULL";
    $result = query("UPDATE project_tools SET section_id=$sectionValue WHERE id='$toolId' AND projectID='$projectID'");
    
    echo json_encode(['success' => (bool)$result]);
    exit;
}

// POST - Create default sections for a project (migration helper)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createDefaultSections'])) {
    $projectName = escape_string($_POST['project'] ?? '');
    
    if (empty($projectName)) {
        echo json_encode(['error' => 'Project is required']);
        exit;
    }
    
    $projectID = getProjectID($projectName);
    if (!$projectID || !userHasProjectAccess($userID, $projectID)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    // Check if project already has sections
    $existingSections = mysqli_num_rows(query("SELECT id FROM project_sidebar_sections WHERE projectID='$projectID'"));
    if ($existingSections > 0) {
        echo json_encode(['error' => 'Project already has sections', 'existing_count' => $existingSections]);
        exit;
    }
    
    // Create default "Tools" section and assign existing tools to it
    $result = query("INSERT INTO project_sidebar_sections 
        (projectID, name, slug, icon, order_index, is_default, is_collapsible, show_add_button, add_button_route) 
        VALUES ('$projectID', 'Tools', 'tools', 'construct-outline', 1, 1, 1, 1, '/project/$projectName/new-tool/')");
    
    if ($result) {
        $toolsSectionId = mysqli_insert_id($GLOBALS['con']);
        
        // Assign all existing tools to this section
        query("UPDATE project_tools SET section_id='$toolsSectionId' WHERE projectID='$projectID' AND (section_id IS NULL OR section_id = 0)");
        
        echo json_encode([
            'success' => true,
            'section_id' => $toolsSectionId,
            'message' => 'Default Tools section created and existing tools assigned'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create default section']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
