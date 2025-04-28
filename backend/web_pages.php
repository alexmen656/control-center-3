<?php
include 'head.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$headers = getRequestHeaders();

if ($headers['Authorization']) {
  $token = escape_string($headers['Authorization']);
  $userData = query("SELECT * FROM control_center_users WHERE loginToken='$token'");
  if (mysqli_num_rows($userData) == 1) {
    $userData = fetch_assoc($userData);
    $userID = $userData['userID'];

    if (isset($_POST['getPagesByProject']) && isset($_POST['project'])) {
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get pages for this project
        $pagesQuery = query("SELECT id, name, slug, title, meta_description, is_home, created_at, updated_at 
                            FROM control_center_web_builder_pages 
                            WHERE project_id='$projectId' 
                            ORDER BY is_home DESC, name ASC");
        
        $pages = [];
        while ($page = fetch_assoc($pagesQuery)) {
          $pages[] = $page;
        }
        
        echo json_encode([
          'success' => true,
          'pages' => $pages
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['getPage']) && isset($_POST['name']) && isset($_POST['project'])) {
      $pageName = escape_string($_POST['name']);
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page details
        $pageQuery = query("SELECT id, name, slug, title, meta_description, is_home 
                           FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND slug='$pageName'");//name
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Get components for this page
          $componentsQuery = query("SELECT id, component_id, original_template_id, html_code, position 
                                   FROM control_center_web_builder_components 
                                   WHERE page_id='$pageId' 
                                   ORDER BY position ASC");
          
          $components = [];
          while ($component = fetch_assoc($componentsQuery)) {
            // Wenn eine original_template_id existiert, hole den Templatenamen
            if ($component['original_template_id'] !== NULL) {
              $templateQuery = query("SELECT title FROM control_center_web_builder_templates WHERE id='" . $component['original_template_id'] . "'");
              if (mysqli_num_rows($templateQuery) > 0) {
                $template = fetch_assoc($templateQuery);
                $component['template_name'] = $template['title'];
              }
            } else {
              $component['template_name'] = "Header"; // Standard-Name wie in sidebar.php
            }
            
            $components[] = $component;
          }
          
          $pageData['components'] = $components;
          
          echo json_encode([
            'success' => true,
            'page' => $pageData
          ]);
        } else {
          echo json_encode([
            'success' => false,
            'message' => 'Page not found'
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['deletePage']) && isset($_POST['name']) && isset($_POST['project'])) {
      $pageName = escape_string($_POST['name']);
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Find page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='$projectId' AND slug='$pageName'");//name
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Delete the page (components will be deleted due to foreign key constraint)
          query("DELETE FROM control_center_web_builder_pages WHERE id='$pageId'");
          
          if (mysqli_affected_rows() > 0) {
            echo json_encode([
              'success' => true,
              'message' => 'Page deleted successfully'
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Failed to delete page'
            ]);
          }
        } else {
          echo json_encode([
            'success' => false,
            'message' => 'Page not found'
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['updateHTML']) && isset($_POST['name']) && isset($_POST['project']) && isset($_POST['html'])) {
      $pageName = escape_string($_POST['name']);
      $project = escape_string($_POST['project']);
      $htmlContent = $_POST['html']; // Not escaping as it will contain HTML code
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Find page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='$projectId' AND slug='$pageName'");//name
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Check if we're updating existing components or creating new ones
          if (isset($_POST['component_id'])) {
            $componentId = escape_string($_POST['component_id']);
            
            // Update existing component
            query("UPDATE control_center_web_builder_components 
                  SET html_code='$htmlContent', updated_at=NOW() 
                  WHERE page_id='$pageId' AND component_id='$componentId'");
            
            if (mysqli_affected_rows() > 0) {
              echo json_encode([
                'success' => true,
                'message' => 'Component updated successfully'
              ]);
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Failed to update component'
              ]);
            }
          } else {
            // Create new component
            $position = 0;
            $positionQuery = query("SELECT MAX(position) as max_pos FROM control_center_web_builder_components WHERE page_id='$pageId'");
            if (mysqli_num_rows($positionQuery) == 1) {
              $posData = fetch_assoc($positionQuery);
              if ($posData['max_pos'] !== null) {
                $position = $posData['max_pos'] + 1;
              }
            }
            
            // Generate UUID for component_id
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
              mt_rand(0, 0xffff), mt_rand(0, 0xffff),
              mt_rand(0, 0xffff),
              mt_rand(0, 0x0fff) | 0x4000,
              mt_rand(0, 0x3fff) | 0x8000,
              mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, html_code, position) 
                  VALUES ('$pageId', '$uuid', '$htmlContent', '$position')");
            
            if (mysqli_insert_id($con) > 0) {
              echo json_encode([
                'success' => true,
                'message' => 'Component added successfully',
                'component_id' => $uuid
              ]);
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Failed to add component'
              ]);
            }
          }
        } else {
          echo json_encode([
            'success' => false,
            'message' => 'Page not found'
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['newPage']) && isset($_POST['name']) && isset($_POST['code']) && isset($_POST['project'])) {
      $pageName = escape_string($_POST['name']);
      $project = escape_string($_POST['project']);
      $code = escape_string($_POST['code']);
      
      // Generate slug from the name
      $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $pageName));
      $slug = preg_replace('/-+/', '-', $slug); // Replace multiple dashes with single dash
      $slug = trim($slug, '-'); // Remove leading/trailing dashes
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Check if page already exists
        $pageCheck = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='$projectId' AND (name='$pageName' OR slug='$slug')");
        if (mysqli_num_rows($pageCheck) > 0) {
          echo json_encode([
            'success' => false,
            'message' => 'A page with this name or URL slug already exists'
          ]);
          exit;
        }
        
        // Set is_home to 1 if this is the first page in the project
        $isHome = 0;
        $pageCountQuery = query("SELECT COUNT(*) as count FROM control_center_web_builder_pages WHERE project_id='$projectId'");
        if (mysqli_num_rows($pageCountQuery) == 1) {
          $countData = fetch_assoc($pageCountQuery);
          if ($countData['count'] == 0) {
            $isHome = 1;
          }
        }
        
        // Set default title if not provided
        $title = isset($_POST['title']) ? escape_string($_POST['title']) : $pageName;
        $metaDescription = isset($_POST['description']) ? escape_string($_POST['description']) : '';
        
        // Create the page
        query("INSERT INTO control_center_web_builder_pages 
              (project_id, name, slug, title, meta_description, is_home) 
              VALUES ('$projectId', '$pageName', '$slug', '$title', '$metaDescription', '$isHome')");
        
        $pageId = mysqli_insert_id($con);
        
        if ($pageId > 0) {
          // If page creation was successful and we have initial HTML code, create a component
          if (!empty($code)) {
            // Generate UUID for component_id
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
              mt_rand(0, 0xffff), mt_rand(0, 0xffff),
              mt_rand(0, 0xffff),
              mt_rand(0, 0x0fff) | 0x4000,
              mt_rand(0, 0x3fff) | 0x8000,
              mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, html_code, position) 
                  VALUES ('$pageId', '$uuid', '$code', '0')");
          }
          
          echo json_encode([
            'success' => true,
            'message' => 'Page created successfully',
            'page_id' => $pageId,
            'slug' => $slug
          ]);
        } else {
          echo json_encode([
            'success' => false,
            'message' => 'Failed to create page'
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['getTemplates']) && isset($_POST['project'])) {
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Hole alle verfügbaren Templates, ohne das description-Feld
        $templatesQuery = query("SELECT id, title, html_code FROM control_center_web_builder_templates ORDER BY title ASC");
        
        $templates = [];
        while ($template = fetch_assoc($templatesQuery)) {
          $templates[] = $template;
        }
        
        echo json_encode([
          'success' => true,
          'templates' => $templates
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['addComponentFromTemplate']) && isset($_POST['project']) && isset($_POST['page']) && isset($_POST['template_id'])) {
      $project = escape_string($_POST['project']);
      $pageName = escape_string($_POST['page']);
      $templateId = escape_string($_POST['template_id']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Find page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='$projectId' AND slug='$pageName'");
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Get template data
          $templateQuery = query("SELECT html_code FROM control_center_web_builder_templates WHERE id='$templateId'");
          if (mysqli_num_rows($templateQuery) == 1) {
            $templateData = fetch_assoc($templateQuery);
            $htmlContent = $templateData['html_code'];
            
            // Find the next position for the component
            $position = 0;
            $positionQuery = query("SELECT MAX(position) as max_pos FROM control_center_web_builder_components WHERE page_id='$pageId'");
            if (mysqli_num_rows($positionQuery) == 1) {
              $posData = fetch_assoc($positionQuery);
              if ($posData['max_pos'] !== null) {
                $position = $posData['max_pos'] + 1;
              }
            }
            
            // Generate UUID for component_id
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
              mt_rand(0, 0xffff), mt_rand(0, 0xffff),
              mt_rand(0, 0xffff),
              mt_rand(0, 0x0fff) | 0x4000,
              mt_rand(0, 0x3fff) | 0x8000,
              mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Füge die Komponente mit Referenz zum Original-Template hinzu
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, html_code, position, original_template_id) 
                  VALUES ('$pageId', '$uuid', '$htmlContent', '$position', '$templateId')");
            
            if (mysqli_insert_id($con) > 0) {
              echo json_encode([
                'success' => true,
                'message' => 'Component added successfully',
                'component_id' => $uuid
              ]);
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Failed to add component'
              ]);
            }
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Template not found'
            ]);
          }
        } else {
          echo json_encode([
            'success' => false,
            'message' => 'Page not found'
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } else {
      // If no valid request is made, return an error
      echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
      ]);
    }
  } else {
    echo json_encode([
      'success' => false, 
      'message' => 'User not found'
    ]);
  }
} else {
  echo json_encode([
    'success' => false, 
    'message' => 'Authentication required'
  ]);
}
?>