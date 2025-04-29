<?php
$origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
$allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost']; // replace with query for domains.
$request_host = parse_url($origin_url, PHP_URL_HOST);
$host_domain = implode('.', array_slice(explode('.', $request_host), -2));
include 'head.php';

// Include the helper file
include 'project_templates_helper.php';

$headers = getRequestHeaders();

if (isset($headers['Authorization'])) {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    switch ($action) {
        case 'list':
            // List all templates
            $query = query("SELECT * FROM project_templates ORDER BY name");
            $templates = [];
            
            if (mysqli_num_rows($query) > 0) {
                while ($template = fetch_assoc($query)) {
                    $templateId = $template['id'];
                    
                    // Get components for this template
                    $componentsQuery = query("SELECT * FROM project_template_components WHERE template_id = '$templateId' ORDER BY component_order");
                    $components = [];
                    
                    while ($component = fetch_assoc($componentsQuery)) {
                        $components[] = [
                            'id' => $component['id'],
                            'name' => $component['name'],
                            'component_type' => $component['component_type'], // tool, page, service, api
                            'icon' => $component['icon'],
                            'config' => json_decode($component['config'], true)
                        ];
                    }
                    
                    $templates[] = [
                        'id' => $template['id'],
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'category' => $template['category'],
                        'thumbnail' => $template['thumbnail'],
                        'components' => $components
                    ];
                }
                
                echo echoJson([
                    'success' => true,
                    'templates' => $templates
                ]);
            } else {
                echo echoJson([
                    'success' => true,
                    'templates' => []
                ]);
            }
            break;
            
        case 'get':
            // Get specific template
            $templateId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            
            if (!$templateId) {
                echo echoJson([
                    'success' => false,
                    'message' => 'Template ID is required'
                ]);
                break;
            }
            
            $query = query("SELECT * FROM project_templates WHERE id = '$templateId'");
            
            if (mysqli_num_rows($query) > 0) {
                $template = fetch_assoc($query);
                $templateId = $template['id'];
                
                // Get components for this template
                $componentsQuery = query("SELECT * FROM project_template_components WHERE template_id = '$templateId' ORDER BY component_order");
                $components = [];
                
                while ($component = fetch_assoc($componentsQuery)) {
                    $components[] = [
                        'id' => $component['id'],
                        'name' => $component['name'],
                        'component_type' => $component['component_type'], // tool, page, service, api
                        'icon' => $component['icon'],
                        'config' => json_decode($component['config'], true)
                    ];
                }
                
                $template['components'] = $components;
                
                echo echoJson([
                    'success' => true,
                    'template' => $template
                ]);
            } else {
                echo echoJson([
                    'success' => false,
                    'message' => 'Template not found'
                ]);
            }
            break;
            
        case 'create':
            // Create new template
            $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
            $description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
            $category = isset($_REQUEST['category']) ? $_REQUEST['category'] : 'general';
            $thumbnail = isset($_REQUEST['thumbnail']) ? $_REQUEST['thumbnail'] : '';
            $components = isset($_REQUEST['components']) ? json_decode($_REQUEST['components'], true) : [];
            
            if (empty($name)) {
                echo echoJson([
                    'success' => false,
                    'message' => 'Template name is required'
                ]);
                break;
            }
            
            // Insert template
            query("INSERT INTO project_templates (name, description, category, thumbnail, created_at) VALUES ('$name', '$description', '$category', '$thumbnail', NOW())");
            $templateId = mysqli_insert_id($con);
            
            // Insert components
            foreach ($components as $index => $component) {
                $componentName = $component['name'] ?? '';
                $componentType = $component['component_type'] ?? 'tool';
                $icon = $component['icon'] ?? '';
                $config = json_encode($component['config'] ?? []);
                
                query("INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order) 
                       VALUES ('$templateId', '$componentName', '$componentType', '$icon', '$config', '$index')");
            }
            
            echo echoJson([
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template created successfully'
            ]);
            break;
            
        case 'update':
            // Update template
            $templateId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
            $description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
            $category = isset($_REQUEST['category']) ? $_REQUEST['category'] : '';
            $thumbnail = isset($_REQUEST['thumbnail']) ? $_REQUEST['thumbnail'] : '';
            $components = isset($_REQUEST['components']) ? json_decode($_REQUEST['components'], true) : [];
            
            if (!$templateId) {
                echo echoJson([
                    'success' => false,
                    'message' => 'Template ID is required'
                ]);
                break;
            }
            
            // Update template
            query("UPDATE project_templates SET name = '$name', description = '$description', category = '$category', thumbnail = '$thumbnail', updated_at = NOW() WHERE id = '$templateId'");
            
            // Delete existing components
            query("DELETE FROM project_template_components WHERE template_id = '$templateId'");
            
            // Insert updated components
            foreach ($components as $index => $component) {
                $componentName = $component['name'] ?? '';
                $componentType = $component['component_type'] ?? 'tool';
                $icon = $component['icon'] ?? '';
                $config = json_encode($component['config'] ?? []);
                
                query("INSERT INTO project_template_components (template_id, name, component_type, icon, config, component_order) 
                       VALUES ('$templateId', '$componentName', '$componentType', '$icon', '$config', '$index')");
            }
            
            echo echoJson([
                'success' => true,
                'message' => 'Template updated successfully'
            ]);
            break;
            
        case 'delete':
            // Delete template
            $templateId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            
            if (!$templateId) {
                echo echoJson([
                    'success' => false,
                    'message' => 'Template ID is required'
                ]);
                break;
            }
            
            // Delete components first to avoid foreign key constraint issues
            query("DELETE FROM project_template_components WHERE template_id = '$templateId'");
            
            // Delete template
            query("DELETE FROM project_templates WHERE id = '$templateId'");
            
            echo echoJson([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
            break;
            
        case 'apply':
            // Apply template to create a new project
            $templateId = isset($_REQUEST['template_id']) ? $_REQUEST['template_id'] : 0;
            $projectName = isset($_REQUEST['project_name']) ? $_REQUEST['project_name'] : '';
            $projectIcon = isset($_REQUEST['project_icon']) ? $_REQUEST['project_icon'] : 'folder-outline';
            
            // Call the extracted function from helper file
            $result = applyTemplate($templateId, $projectName, $projectIcon, $headers);
            echo echoJson($result);
            break;
            
        default:
            echo echoJson([
                'success' => false,
                'message' => 'Unknown action'
            ]);
            break;
    }
} else {
    echo echoJson([
        'success' => false,
        'message' => 'Authorization required'
    ]);
}
?>