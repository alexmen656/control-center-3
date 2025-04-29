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
        
        // Check if user exists in web builder users table
        $webBuilderUserQuery = query("SELECT * FROM control_center_web_builder_users WHERE email='$userEmail'");
        $webBuilderUserId = 0;
        
        if (mysqli_num_rows($webBuilderUserQuery) == 1) {
            $webBuilderUserId = fetch_assoc($webBuilderUserQuery)['id'];
        } else {
            // Create a new web builder user with required fields from schema
            $safeUsername = preg_replace('/[^a-z0-9]/', '_', strtolower($userName)) . '_' . $userId;
            $defaultPassword = password_hash('changeMe' . $userId, PASSWORD_DEFAULT);
            
            query("INSERT INTO control_center_web_builder_users (username, password, email, created_at) VALUES ('$safeUsername', '$defaultPassword', '$userEmail', NOW())");
            $webBuilderUserId = mysqli_insert_id($con);
        }
        
        // Create web builder project and link to Control Center project
        $projectDesc = "Project created from template (Control Center Project ID: $projectID)";
        query("INSERT INTO control_center_web_builder_projects (user_id, name, description, created_at) VALUES ('$webBuilderUserId', '$projectName', '$projectDesc', NOW())");
        $webBuilderProjectId = mysqli_insert_id($con);
                
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
                    
                case 'service':
                    $description = isset($config['description']) ? $config['description'] : '';
                    
                    query("INSERT INTO project_services (projectID, name, icon, link, description) 
                           VALUES ('$projectID', '$componentName', '$icon', '$link', '$description')");

                    $endpointsConfig[] = [
                        'path' => 'services/'.$link,
                        'title' => $componentName,
                        'icon' => $icon,
                        'isVisible' => 'true'
                    ];

                    $endpointsConfig[] = [
                        'path' => 'services/'.$link.'/config',
                        'title' => $componentName.' Config',
                        'icon' => 'cog-outline',
                        'isVisible' => 'true'
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
        
        // Now process page components since we have the web builder project ID
        foreach ($pageComponents as $pageComponent) {
            $componentName = $pageComponent['name'];
            $config = $pageComponent['config'];
            
            $slug = strtolower(str_replace(' ', '-', $componentName));
            $isHome = isset($config['is_home']) && $config['is_home'] ? 1 : 0;
            $title = isset($config['title']) ? $config['title'] : $componentName;
            $metaDescription = isset($config['meta_description']) ? $config['meta_description'] : '';
            
            // Now insert with the correct web builder project ID
            query("INSERT INTO control_center_web_builder_pages (project_id, name, slug, title, meta_description, is_home, created_at) 
                   VALUES ('$webBuilderProjectId', '$componentName', '$slug', '$title', '$metaDescription', '$isHome', NOW())");
            
            // Create a default component for this page
            $pageId = mysqli_insert_id($con);
            if ($pageId) {
                // Generate UUID for the component
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                  mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                  mt_rand(0, 0xffff),
                  mt_rand(0, 0x0fff) | 0x4000,
                  mt_rand(0, 0x3fff) | 0x8000,
                  mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $defaultHTML = "<section><div class=\"container mt-5\"><h1>$componentName</h1><p>This is your $componentName page. Start editing to customize it!</p></div></section>";
                
                query("INSERT INTO control_center_web_builder_components 
                      (page_id, component_id, original_template_id, html_code, position, created_at, updated_at) 
                      VALUES ('$pageId', '$uuid', 15, '$defaultHTML', 0, NOW(), NOW())");
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
