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
    } elseif (isset($_POST['newPage']) && isset($_POST['name']) && isset($_POST['project'])) {
      $pageName = escape_string($_POST['name']);
      $project = escape_string($_POST['project']);
      $code = isset($_POST['code']) ? escape_string($_POST['code']) : '';
      
      // Verwende den benutzerdefinierten Slug, wenn vorhanden, oder generiere einen
      if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = escape_string($_POST['slug']);
        // Slug bereinigen
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', $slug); // Mehrfach-Bindestriche durch einen ersetzen
        $slug = trim($slug, '-'); // Führende/abschließende Bindestriche entfernen
      } else {
        // Slug aus dem Namen generieren
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $pageName));
        $slug = preg_replace('/-+/', '-', $slug); // Replace multiple dashes with single dash
        $slug = trim($slug, '-'); // Remove leading/trailing dashes
      }
      
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
        
        // Starten einer Transaktion für atomare Operationen
        mysqli_begin_transaction($con);
        
        try {
          // Prüfe, ob die Seite als Homepage markiert sein soll
          $isHome = isset($_POST['is_home']) && $_POST['is_home'] == 1 ? 1 : 0;
          
          // Falls das die erste Seite im Projekt ist, mach sie zur Homepage
          if ($isHome == 0) {
            $pageCountQuery = query("SELECT COUNT(*) as count FROM control_center_web_builder_pages WHERE project_id='$projectId'");
            $countData = fetch_assoc($pageCountQuery);
            if ($countData['count'] == 0) {
              $isHome = 1;
            }
          }
          
          // Wenn diese Seite die Homepage sein soll, setze den Homepage-Status aller anderen Seiten zurück
          if ($isHome == 1) {
            //echo "UPDATE control_center_web_builder_pages SET is_home=0 WHERE project_id='$projectId'";
            query("UPDATE control_center_web_builder_pages SET is_home=0 WHERE project_id=$projectId");
          }
          
          // Set default title if not provided or use provided title
          $title = isset($_POST['title']) ? escape_string($_POST['title']) : $pageName;
          $metaDescription = isset($_POST['meta_description']) ? escape_string($_POST['meta_description']) : '';
          
          // Create the page
          query("INSERT INTO control_center_web_builder_pages 
                (project_id, name, slug, title, meta_description, is_home, created_at, updated_at) 
                VALUES ('$projectId', '$pageName', '$slug', '$title', '$metaDescription', '$isHome', NOW(), NOW())");
          
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
            
            // Commit der Transaktion, wenn alles erfolgreich war
            mysqli_commit($con);
            
            echo json_encode([
              'success' => true,
              'message' => 'Page created successfully',
              'page_id' => $pageId,
              'slug' => $slug
            ]);
          } else {
            // Rollback bei Fehler
            mysqli_rollback($con);
            
            echo json_encode([
              'success' => false,
              'message' => 'Failed to create page'
            ]);
          }
        } catch (Exception $e) {
          // Rollback bei jeder Exception
          mysqli_rollback($con);
          
          echo json_encode([
            'success' => false,
            'message' => 'Error processing request: ' . $e->getMessage()
          ]);
        }
      } else {
        echo json_encode([
          'success' => false,
          'message' => 'Project not found'
        ]);
      }
    } elseif (isset($_POST['createPage'])) {
      $project = escape_string($_POST['project']);
      $pageName = escape_string($_POST['name']);
      
      // Verwende den benutzerdefinierten Slug, wenn vorhanden, oder generiere einen
      if (isset($_POST['slug']) && !empty($_POST['slug'])) {
        $slug = escape_string($_POST['slug']);
        // Slug bereinigen
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', $slug); // Mehrfach-Bindestriche durch einen ersetzen
        $slug = trim($slug, '-'); // Führende/abschließende Bindestriche entfernen
      } else {
        // Slug aus dem Namen generieren
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $pageName));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
      }
      
      // Optionale Parameter für Metatitel und Beschreibung
      $title = isset($_POST['title']) ? escape_string($_POST['title']) : $pageName;
      $metaDescription = isset($_POST['meta_description']) ? escape_string($_POST['meta_description']) : '';
      
      // Bestimmen, ob es die Homepage sein soll
      $isHome = isset($_POST['is_home']) && $_POST['is_home'] == 1 ? 1 : 0;
      
      // Get project ID first
      $projectQuery = query("SELECT id FROM control_center_web_builder_projects WHERE name='$project'");
      if (mysqli_num_rows($projectQuery) == 1) {
        $projectData = fetch_assoc($projectQuery);
        $projectId = $projectData['id'];
        
        // Prüfen, ob bereits eine Seite mit diesem Namen oder Slug existiert
        $pageCheck = query("SELECT id FROM control_center_web_builder_pages WHERE project_id='$projectId' AND (name='$pageName' OR slug='$slug')");
        if (mysqli_num_rows($pageCheck) > 0) {
          echo json_encode([
            'success' => false,
            'message' => 'Eine Seite mit diesem Namen oder URL-Slug existiert bereits'
          ]);
          exit;
        }
        
        // Wenn diese Seite die Homepage sein soll, stelle sicher, dass keine andere Seite als Homepage markiert ist
        if ($isHome == 1) {
          query("UPDATE control_center_web_builder_pages SET is_home=0 WHERE project_id='$projectId'");
        } 
        // Wenn keine Homepage gesetzt ist und dies die erste Seite ist, mache sie automatisch zur Homepage
        else if ($isHome == 0) {
          $homeCheck = query("SELECT COUNT(*) as count FROM control_center_web_builder_pages WHERE project_id='$projectId' AND is_home=1");
          $homeData = fetch_assoc($homeCheck);
          if ($homeData['count'] == 0) {
            $pageCountQuery = query("SELECT COUNT(*) as count FROM control_center_web_builder_pages WHERE project_id='$projectId'");
            $countData = fetch_assoc($pageCountQuery);
            if ($countData['count'] == 0) {
              $isHome = 1;
            }
          }
        }
        
        // Create the page
        query("INSERT INTO control_center_web_builder_pages 
              (project_id, name, slug, title, meta_description, is_home, created_at, updated_at) 
              VALUES ('$projectId', '$pageName', '$slug', '$title', '$metaDescription', '$isHome', NOW(), NOW())");
        
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
    } elseif (isset($_POST['getTemplates'])) {
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
    } elseif (isset($_POST['updateComponentsOrder']) && isset($_POST['project']) && isset($_POST['pageName']) && isset($_POST['components'])) {
      $project = escape_string($_POST['project']);
      $pageName = escape_string($_POST['pageName']);
      $componentsData = json_decode($_POST['components'], true);
      
      if (!is_array($componentsData)) {
        echo json_encode([
          'success' => false,
          'message' => 'Invalid components data format'
        ]);
        exit;
      }
      
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
          
          // Begin transaction for updating all component positions
          mysqli_begin_transaction($con);
          
          $success = true;
          foreach ($componentsData as $component) {
            if (!isset($component['component_id']) || !isset($component['position'])) {
              $success = false;
              break;
            }
            
            $componentId = escape_string($component['component_id']);
            $position = (int)$component['position'];
            
            // Update the component position
            query("UPDATE control_center_web_builder_components 
                  SET position='$position', updated_at=NOW() 
                  WHERE page_id='$pageId' AND component_id='$componentId'");
            
            if (mysqli_affected_rows($con) <= 0) {
              $success = false;
              break;
            }
          }
          
          if ($success) {
            // Commit changes if all updates were successful
            mysqli_commit($con);
            echo json_encode([
              'success' => true,
              'message' => 'Component order updated successfully'
            ]);
          } else {
            // Rollback changes if any update failed
            mysqli_rollback($con);
            echo json_encode([
              'success' => false,
              'message' => 'Failed to update component order'
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
    } elseif (isset($_POST['deleteComponent']) && isset($_POST['project']) && isset($_POST['pageName']) && isset($_POST['component_id'])) {
      $project = escape_string($_POST['project']);
      $pageName = escape_string($_POST['pageName']);
      $componentId = escape_string($_POST['component_id']);
      
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
          
          // Check if component exists
          $componentQuery = query("SELECT id FROM control_center_web_builder_components WHERE page_id='$pageId' AND component_id='$componentId'");
          if (mysqli_num_rows($componentQuery) == 1) {
            // Delete the component
            query("DELETE FROM control_center_web_builder_components WHERE page_id='$pageId' AND component_id='$componentId'");
            
            if (mysqli_affected_rows($con) > 0) {
              // Renumber remaining components to ensure consistent positions
              $remainingComponents = query("SELECT id, position FROM control_center_web_builder_components 
                                           WHERE page_id='$pageId' 
                                           ORDER BY position ASC");
              
              $position = 1;
              while ($component = fetch_assoc($remainingComponents)) {
                $componentDbId = $component['id'];
                query("UPDATE control_center_web_builder_components SET position='$position', updated_at=NOW() WHERE id='$componentDbId'");
                $position++;
              }
              
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