<?php
require_once 'validation_helper.php';

function applyTemplate($templateId, $projectName, $projectIcon, $headers) {
    global $con;
    
    // Perform validations
    $validationResult = validateAll([
        validateTemplateId($templateId),
        validateProjectName($projectName)
    ]);
    
    if ($validationResult !== null) {
        return $validationResult;
    }

    // Generate project ID and URL-safe link
    $projectID = generateRandomString(20);
    $href = str_replace("\\", "", createLink($projectName));
    
    // Create project with proper ID and link
    query("INSERT INTO projects VALUES (0, '$projectIcon', '$projectName', '$href', CURDATE(), '$projectID')");
    $projectDatabaseId = mysqli_insert_id($con);
    
    // Get user ID from token
    $token = escape_string($headers['Authorization']);
    $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
    
    if (mysqli_num_rows($userData) == 1) {
        $userData = fetch_assoc($userData);
        $userId = $userData['userID'];
        $userEmail = $userData['email'];
        $userName = $userData['firstname'] . ' ' . $userData['lastname'];
        if (empty(trim($userName))) {
            $userName = 'User_' . $userId;
        }
        
        $linkResult = query("INSERT INTO control_center_user_projects (userID, projectID, role) VALUES ('$userId', '$projectID', 1)");
        
        // Check if the insertion was successful
        if (!$linkResult) {
            error_log("Failed to link user to project on first attempt: " . mysqli_error($con));
            $checkExisting = query("SELECT * FROM control_center_user_projects WHERE userID='$userId' AND projectID='$projectID'");
            
            if (mysqli_num_rows($checkExisting) === 0) {
                query("START TRANSACTION");
                $secondAttempt = query("INSERT INTO control_center_user_projects (userID, projectID, role) VALUES ('$userId', '$projectID', 1)");
                if ($secondAttempt) {
                    query("COMMIT");
                } else {
                    query("ROLLBACK");
                    return [
                        'success' => false,
                        'message' => 'Failed to link user to project: ' . mysqli_error($con)
                    ];
                }
            }
        }
        
        // Get template components
        $componentsQuery = query("SELECT * FROM project_template_components WHERE template_id = '$templateId' ORDER BY component_order");
        $pageComponents = [];
        $endpointsConfig = [];

        while ($component = fetch_assoc($componentsQuery)) {
            $componentName = $component['name'];
            $componentType = $component['component_type'];
            $icon = $component['icon'];
            $link = isset($config['link']) ? $config['link'] : strtolower(str_replace(' ', '-', $componentName));
            $config = json_decode($component['config'], true);
            $link = isset($config['link']) ? $config['link'] : strtolower(str_replace(' ', '-', $componentName));

            switch ($componentType) {
                case 'tool':
                    $hasConfig = isset($config['hasConfig']) ? $config['hasConfig'] : '0';

                    query("INSERT INTO project_tools (projectID, name, icon, hasConfig, `order`) 
                           VALUES ('$projectID', '$componentName', '$icon', '$hasConfig', '0')");
 
                   $endpointsConfig[] = [
                        'path' => $link,
                        'title' => $componentName,
                        'icon' => $icon,
                        'isVisible' => 'true'
                   ];

                   if($hasConfig == '1'){
                        $endpointsConfig[] = [
                            'path' => $link.'/config',
                            'title' => $componentName .' Config',
                            'icon' => $icon,
                            'isVisible' => 'true'
                        ];                    
                    }
                    break;
                    
                case 'page':
                    $pageComponents[] = [
                        'name' => $componentName,
                        'icon' => $icon,
                        'config' => $config
                    ];
                    break;
                    
                case 'api':
                    break;
            }
        }
        
        // Create URLs for each page
        $urls = [];
        $pagesQuery = "INSERT INTO control_center_pages VALUES ";
        $pageValues = [];
        
        // Add default endpoints
        $endpointsConfig = array_merge($endpointsConfig, [
            [
                'path' => '',
                'title' => 'Project Dashboard',
                'icon' => '',
                'isVisible' => 'true'
            ],
            [
                'path' => 'new/tool',
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
                'path' => 'info',
                'title' => 'Project Info',
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
            ]
        ]);
        
        // Add endpoint for each page component
        foreach ($pageComponents as $pageComponent) {
            $componentName = $pageComponent['name'];
            $slug = strtolower(str_replace(' ', '-', $componentName));
        }
        
        // Generate URLs and page entries

        //print_r($endpointsConfig);
        foreach ($endpointsConfig as $endpoint) {
            $url = "project/" . $href . ($endpoint['path'] ? "/" . $endpoint['path'] : "");
            $urls[] = $url;
            
            $title = $endpoint['title'];
            $icon = $endpoint['icon'];
            $isVisible = $endpoint['isVisible'];
            $pageValues[] = "(0, '$url', '$isVisible', '$icon', '$title', '', 0)";
        }

        // Insert all pages in a single SQL statement
        if (!empty($pageValues)) {
            $pagesQuery .= implode(', ', $pageValues);
            query($pagesQuery);
            
            // Project views
            foreach ($urls as $u) {
                $page = query("SELECT * FROM control_center_pages WHERE url='$u'");
                if (mysqli_num_rows($page) == 1) {
                    $page = fetch_assoc($page);
                    $pageID = $page['id'];
                    query("INSERT INTO control_center_project_views VALUES (0, $pageID, '$projectID')");
                }
            }
        }
        
        return [
            'success' => true,
            'project_id' => $projectID,
            'project_name' => $projectName,
            'message' => 'Project created from template successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'User not authenticated'
        ];
    }
}
?>
