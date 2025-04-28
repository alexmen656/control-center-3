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

    if (isset($_POST['getComponentsByPage']) && isset($_POST['page']) && isset($_POST['project'])) {
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND slug='$pageName'");//
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Get all components for this page
          $componentsQuery = query("SELECT c.id, c.component_id, c.original_template_id, c.html_code, 
                                  c.position, c.created_at, c.updated_at, t.title as template_title 
                                  FROM control_center_web_builder_components c 
                                  LEFT JOIN control_center_web_builder_templates t ON c.original_template_id = t.id 
                                  WHERE c.page_id='$pageId' 
                                  ORDER BY c.position ASC");
          
          $components = [];
          while ($component = fetch_assoc($componentsQuery)) {
            $components[] = $component;
          }
          
          echo json_encode([
            'success' => true,
            'components' => $components
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
    } elseif (isset($_POST['getComponent']) && isset($_POST['component_id']) && isset($_POST['page']) && isset($_POST['project'])) {
      $componentId = escape_string($_POST['component_id']);
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
    //  echo $projectQuery;
      //echo $project;
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND slug='$pageName'");//name
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Get the specific component
          $componentQuery = query("SELECT c.id, c.component_id, c.original_template_id, c.html_code, 
                                  c.position, c.created_at, c.updated_at, t.title as template_title,
                                  t.html_code as original_template_code 
                                  FROM control_center_web_builder_components c 
                                  LEFT JOIN control_center_web_builder_templates t ON c.original_template_id = t.id 
                                  WHERE c.page_id='$pageId' AND c.component_id='$componentId'");
          
          if (mysqli_num_rows($componentQuery) == 1) {
            $component = fetch_assoc($componentQuery);
            
            echo json_encode([
              'success' => true,
              'component' => $component
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Component not found'
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
    } elseif (isset($_POST['deleteComponent']) && isset($_POST['component_id']) && isset($_POST['page']) && isset($_POST['project'])) {
      $componentId = escape_string($_POST['component_id']);
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND name='$pageName'");
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Check if component exists first
          $checkQuery = query("SELECT id, position FROM control_center_web_builder_components 
                              WHERE page_id='$pageId' AND component_id='$componentId'");
          
          if (mysqli_num_rows($checkQuery) == 1) {
            $componentData = fetch_assoc($checkQuery);
            $position = $componentData['position'];
            
            // Delete the component
            query("DELETE FROM control_center_web_builder_components 
                  WHERE page_id='$pageId' AND component_id='$componentId'");
            
            if (mysqli_affected_rows() > 0) {
              // Reorder remaining components
              query("UPDATE control_center_web_builder_components 
                    SET position = position - 1 
                    WHERE page_id='$pageId' AND position > $position");
              
              echo json_encode([
                'success' => true,
                'message' => 'Component deleted successfully'
              ]);
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Failed to delete component'
              ]);
            }
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Component not found'
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
    } elseif (isset($_POST['updateHTML']) && isset($_POST['component_id']) && isset($_POST['page']) && isset($_POST['project']) && isset($_POST['html'])) {
      $componentId = escape_string($_POST['component_id']);
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      $htmlContent = $_POST['html']; // Not escaping as it will contain HTML code
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND name='$pageName'");
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Update the component HTML
          query("UPDATE control_center_web_builder_components 
                SET html_code='$htmlContent', updated_at=NOW() 
                WHERE page_id='$pageId' AND component_id='$componentId'");
          
          if (mysqli_affected_rows() > 0) {
            echo json_encode([
              'success' => true,
              'message' => 'Component HTML updated successfully'
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Failed to update component HTML or no changes made'
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
    } elseif (isset($_POST['newComponent']) && isset($_POST['page']) && isset($_POST['project']) && isset($_POST['html'])) {
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      $htmlContent = $_POST['html']; // Not escaping as it will contain HTML code
      $templateId = isset($_POST['template_id']) ? escape_string($_POST['template_id']) : null;
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND slug='$pageName'");//name
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Calculate position (max position + 1)
          $position = 0;
          $positionQuery = query("SELECT MAX(position) as max_pos 
                                 FROM control_center_web_builder_components 
                                 WHERE page_id='$pageId'");
          
          if (mysqli_num_rows($positionQuery) == 1) {
            $posData = fetch_assoc($positionQuery);
            if (!is_null($posData['max_pos'])) {
              $position = intval($posData['max_pos']) + 1;
            }
          }
          
          // Generate UUID for component_id
          $componentUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
          );
          
          // Insert new component
          if ($templateId) {
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, original_template_id, html_code, position) 
                  VALUES ('$pageId', '$componentUuid', '$templateId', '$htmlContent', '$position')");
          } else {
            query("INSERT INTO control_center_web_builder_components 
                  (page_id, component_id, html_code, position) 
                  VALUES ('$pageId', '$componentUuid', '$htmlContent', '$position')");
          }
          
          $newId = mysqli_insert_id($con);
          
          if ($newId > 0) {
            echo json_encode([
              'success' => true,
              'message' => 'Component created successfully',
              'component_id' => $componentUuid,
              'id' => $newId,
              'position' => $position
            ]);
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Failed to create component'
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
    } elseif (isset($_POST['moveComponent']) && isset($_POST['component_id']) && isset($_POST['page']) && isset($_POST['project']) && isset($_POST['direction'])) {
      $componentId = escape_string($_POST['component_id']);
      $pageName = escape_string($_POST['page']);
      $project = escape_string($_POST['project']);
      $direction = escape_string($_POST['direction']); // 'up' or 'down'
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Get page ID
        $pageQuery = query("SELECT id FROM control_center_web_builder_pages 
                           WHERE project_id='$projectId' AND name='$pageName'");
        
        if (mysqli_num_rows($pageQuery) == 1) {
          $pageData = fetch_assoc($pageQuery);
          $pageId = $pageData['id'];
          
          // Get current component position
          $componentQuery = query("SELECT id, position 
                                  FROM control_center_web_builder_components 
                                  WHERE page_id='$pageId' AND component_id='$componentId'");
          
          if (mysqli_num_rows($componentQuery) == 1) {
            $component = fetch_assoc($componentQuery);
            $currentPosition = $component['position'];
            
            // Check if move is possible
            if ($direction == 'up' && $currentPosition > 0) {
              // Find component at position - 1
              $swapQuery = query("SELECT id, component_id 
                                FROM control_center_web_builder_components 
                                WHERE page_id='$pageId' AND position=" . ($currentPosition - 1));
              
              if (mysqli_num_rows($swapQuery) == 1) {
                $swapComponent = fetch_assoc($swapQuery);
                
                // Swap positions
                query("UPDATE control_center_web_builder_components 
                      SET position = " . ($currentPosition - 1) . " 
                      WHERE page_id='$pageId' AND component_id='$componentId'");
                
                query("UPDATE control_center_web_builder_components 
                      SET position = $currentPosition 
                      WHERE page_id='$pageId' AND component_id='" . $swapComponent['component_id'] . "'");
                
                echo json_encode([
                  'success' => true,
                  'message' => 'Component moved up successfully'
                ]);
              } else {
                echo json_encode([
                  'success' => false,
                  'message' => 'Could not find adjacent component to swap with'
                ]);
              }
            } elseif ($direction == 'down') {
              // Find component at position + 1
              $swapQuery = query("SELECT id, component_id 
                                FROM control_center_web_builder_components 
                                WHERE page_id='$pageId' AND position=" . ($currentPosition + 1));
              
              if (mysqli_num_rows($swapQuery) == 1) {
                $swapComponent = fetch_assoc($swapQuery);
                
                // Swap positions
                query("UPDATE control_center_web_builder_components 
                      SET position = " . ($currentPosition + 1) . " 
                      WHERE page_id='$pageId' AND component_id='$componentId'");
                
                query("UPDATE control_center_web_builder_components 
                      SET position = $currentPosition 
                      WHERE page_id='$pageId' AND component_id='" . $swapComponent['component_id'] . "'");
                
                echo json_encode([
                  'success' => true,
                  'message' => 'Component moved down successfully'
                ]);
              } else {
                echo json_encode([
                  'success' => false,
                  'message' => 'Could not find adjacent component to swap with'
                ]);
              }
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Invalid direction or component already at limit'
              ]);
            }
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Component not found'
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
    } elseif (isset($_POST['getTemplates'])) {
      // Get all available templates
      $templatesQuery = query("SELECT id, title, html_code, cover_image, created_at 
                             FROM control_center_web_builder_templates 
                             ORDER BY title ASC");
      
      $templates = [];
      while ($template = fetch_assoc($templatesQuery)) {
        $templates[] = $template;
      }
      
      echo json_encode([
        'success' => true,
        'templates' => $templates
      ]);
    } elseif (isset($_POST['getTemplate']) && isset($_POST['id'])) {
      $templateId = escape_string($_POST['id']);
      
      // Get specific template
      $templateQuery = query("SELECT id, title, html_code, cover_image, created_at 
                            FROM control_center_web_builder_templates 
                            WHERE id='$templateId'");
      
      if (mysqli_num_rows($templateQuery) == 1) {
        $template = fetch_assoc($templateQuery);
        
        echo json_encode([
          'success' => true,
          'template' => $template
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Template not found'
        ]);
      }
    } elseif (isset($_POST['createTemplate']) && isset($_POST['title']) && isset($_POST['html'])) {
      $title = escape_string($_POST['title']);
      $htmlCode = $_POST['html']; // Not escaping as it will contain HTML
      $coverImage = isset($_POST['cover_image']) ? escape_string($_POST['cover_image']) : null;
      
      // Insert new template
      if ($coverImage) {
        query("INSERT INTO control_center_web_builder_templates 
              (title, html_code, cover_image) 
              VALUES ('$title', '$htmlCode', '$coverImage')");
      } else {
        query("INSERT INTO control_center_web_builder_templates 
              (title, html_code) 
              VALUES ('$title', '$htmlCode')");
      }
      
      $newId = mysqli_insert_id();
      
      if ($newId > 0) {
        echo json_encode([
          'success' => true,
          'message' => 'Template created successfully',
          'template_id' => $newId
        ]);
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Failed to create template'
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