<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');

/**
 * Publish Script
 * 
 * This script generates HTML pages for a specified project.
 * It retrieves all pages and their components from the database and creates static HTML files.
 * The page with the lowest ID becomes index.html, others are named as {slug}.html
 * 
 * Parameter:
 * - project_id: ID des zu veröffentlichenden Projekts (erforderlich)
 * - css: Bei 'true' wird die CSS-Datei optimiert, um unbenutzte Klassen zu entfernen (optional)
 */

// CSS-Optimizer-Klasse einbinden
require_once 'css-optimizer.php';

// Check if project_id is provided
if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    die('Error: Project ID is required. Use ?project_id=X in the URL.');
}

$projectId = (int)$_GET['project_id'];

// Überprüfen, ob CSS-Optimierung aktiviert werden soll
$optimizeCss = isset($_GET['css']) && ($_GET['css'] === 'true' || $_GET['css'] === '1');

// Database connection
$dbConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'alex01d01',
    'username' => 'alex01d01',
    'password' => 'XL2fiPeVH3'
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}

// Check if project exists
$stmtProject = $pdo->prepare('SELECT * FROM control_center_web_builder_projects WHERE id = ?');
$stmtProject->execute([$projectId]);
$project = $stmtProject->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die('Error: Project not found.');
}

// Create output directory for the project
$outputDir = "published/{$projectId}/";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Copy assets (CSS) to the published directory
function copyAssets($outputDir) {
    // Create assets directory in published folder if it doesn't exist
    if (!is_dir($outputDir . 'assets/')) {
        mkdir($outputDir . 'assets/', 0755, true);
    }
    
    // Copy styles.css to published directory
    $sourceStylesCss = 'assets/styles.css';
    $destStylesCss = $outputDir . 'styles.css';
    
    if (file_exists($sourceStylesCss)) {
        copy($sourceStylesCss, $destStylesCss);
        echo "Copied styles.css to published directory<br>";
    } else {
        echo "Warning: Could not find styles.css in assets directory<br>";
    }
    
    // Copy form_submit.php to published directory for form handling
    $sourceFormSubmit = __DIR__ . '/form_submit.php';
    $destFormSubmit = $outputDir . 'form_submit.php';
    
    if (file_exists($sourceFormSubmit)) {
        copy($sourceFormSubmit, $destFormSubmit);
        echo "Copied form_submit.php to published directory<br>";
    } else {
        echo "Warning: Could not find form_submit.php in site-builder directory<br>";
    }
}

// Process form data in HTML content
function processFormData($htmlContent, $projectName, $pdo) {
    // Process foreach form data tags
    $htmlContent = processForEachFormData($htmlContent, $projectName, $pdo);
    
    // Process single entry form data tags
    $htmlContent = processSingleFormEntry($htmlContent, $projectName, $pdo);
    
    return $htmlContent;
}

// Process text transformations in values
function applyTextTransformation($text, $transformation) {
    if (!$text || !is_string($text)) return $text;
    
    // Debug: Log the transformation being applied
    error_log("Applying transformation '$transformation' to text: '$text'");
    
    switch ($transformation) {
        case 'capitalize':
            // Special handling for acronyms and model designations
            if (preg_match('/^[A-Z0-9]+$/', $text)) {
                // If text is all caps or a model designation, preserve it
                $result = $text;
            } else {
                // Check for common car model patterns (like A4, Q7)
                if (preg_match('/^[A-Z][0-9]$/', $text)) {
                    $result = $text;
                } else {
                    // For normal text: capitalize first letter of each word
                    $words = explode(' ', strtolower($text));
                    foreach ($words as &$word) {
                        // Check if word is an acronym before lowercase was applied
                        if (preg_match('/^[A-Z]{2,}$/', $word)) {
                            $word = strtoupper($word);
                        } else {
                            $word = ucfirst($word);
                        }
                    }
                    $result = implode(' ', $words);
                }
            }
            error_log("After capitalize: '$result'");
            return $result;
            
        case 'uppercase':
            // Convert to all uppercase
            $result = strtoupper($text);
            error_log("After uppercase: '$result'");
            return $result;
            
        case 'lowercase':
            // Convert to all lowercase
            $result = strtolower($text);
            error_log("After lowercase: '$result'");
            return $result;
            
        default:
            // No transformation or unknown transformation type
            return $text;
    }
}

// Process foreach form data tags
function processForEachFormData($htmlContent, $projectName, $pdo) {
    // Find all form data tags using regex
    $pattern = '/<!-- foreach:([^>]+) -->([\s\S]*?)<!-- endforeach:\\1 -->/';
    
    if (preg_match_all($pattern, $htmlContent, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $formName = trim($match[1]);
            $template = $match[2];
            $replacement = '';
            
            // Get form data from database
            $formData = getFormData($formName, $projectName, $pdo);
            
            if (!empty($formData)) {
                // Build HTML for each form entry
                foreach ($formData as $item) {
                    // Get a copy of the template for this item
                    $itemHtml = $template;
                    
                    // Create a complete list of fields to process
                    $processedFields = [];
                    
                    // Process field value transformations first (like capitalize, uppercase)
                    $transformPattern = '/\{\{\s*item\.([a-zA-Z0-9_]+)\|([a-z]+)\s*\}\}/';
                    if (preg_match_all($transformPattern, $itemHtml, $transformMatches, PREG_SET_ORDER)) {
                        foreach ($transformMatches as $transformMatch) {
                            $fieldName = $transformMatch[1];
                            $transformation = $transformMatch[2];
                            
                            if (isset($item[$fieldName])) {
                                // Apply the transformation
                                $transformedValue = applyTextTransformation($item[$fieldName], $transformation);
                                
                                // Replace in template and mark as processed
                                $itemHtml = str_replace($transformMatch[0], htmlspecialchars($transformedValue), $itemHtml);
                                $processedFields[$fieldName] = true;
                            }
                        }
                    }
                    
                    // Process standard field value replacements (for fields not already processed)
                    $standardPattern = '/\{\{\s*item\.([a-zA-Z0-9_]+)\s*\}\}/';
                    if (preg_match_all($standardPattern, $itemHtml, $standardMatches, PREG_SET_ORDER)) {
                        foreach ($standardMatches as $standardMatch) {
                            $fieldName = $standardMatch[1];
                            
                            if (isset($item[$fieldName]) && !isset($processedFields[$fieldName])) {
                                $itemHtml = str_replace($standardMatch[0], htmlspecialchars($item[$fieldName]), $itemHtml);
                            }
                        }
                    }
                    
                    $replacement .= $itemHtml;
                }
            }
            
            // Replace the foreach tag with the generated content
            $htmlContent = str_replace($match[0], $replacement, $htmlContent);
        }
    }
    
    return $htmlContent;
}

// Process single form entry tags
function processSingleFormEntry($htmlContent, $projectName, $pdo) {
    // Pattern to match single form entry tags
    $pattern = '/<!-- single:([^:]+):(\d+) -->([\s\S]*?)<!-- endsingle:\\1 -->/';
    
    if (preg_match_all($pattern, $htmlContent, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $formName = trim($match[1]);
            $entryId = intval($match[2]);
            $template = $match[3];
            $replacement = '';
            
            // Get single form entry from database
            $formEntry = getSingleFormEntry($formName, $entryId, $projectName, $pdo);
            
            if (!empty($formEntry)) {
                // Process the template with actual data from the single entry
                $itemHtml = $template;
                
                // Create a complete list of fields to process
                $processedFields = [];
                
                // Process field value transformations first (like capitalize, uppercase)
                $transformPattern = '/\{\{\s*item\.([a-zA-Z0-9_]+)\|([a-z]+)\s*\}\}/';
                if (preg_match_all($transformPattern, $itemHtml, $transformMatches, PREG_SET_ORDER)) {
                    foreach ($transformMatches as $transformMatch) {
                        $fieldName = $transformMatch[1];
                        $transformation = $transformMatch[2];
                        
                        if (isset($formEntry[$fieldName])) {
                            // Apply the transformation
                            $transformedValue = applyTextTransformation($formEntry[$fieldName], $transformation);
                            
                            // Replace in template and mark as processed
                            $itemHtml = str_replace($transformMatch[0], htmlspecialchars($transformedValue), $itemHtml);
                            $processedFields[$fieldName] = true;
                        }
                    }
                }
                
                // Process standard field value replacements
                $standardPattern = '/\{\{\s*item\.([a-zA-Z0-9_]+)\s*\}\}/';
                if (preg_match_all($standardPattern, $itemHtml, $standardMatches, PREG_SET_ORDER)) {
                    foreach ($standardMatches as $standardMatch) {
                        $fieldName = $standardMatch[1];
                        
                        if (isset($formEntry[$fieldName]) && !isset($processedFields[$fieldName])) {
                            $itemHtml = str_replace($standardMatch[0], htmlspecialchars($formEntry[$fieldName]), $itemHtml);
                        }
                    }
                }
                
                $replacement = $itemHtml;
            }
            
            // Replace the single tag with the generated content
            $htmlContent = str_replace($match[0], $replacement, $htmlContent);
        }
    }
    
    return $htmlContent;
}

// Process embedded forms in HTML content
function processFormTags($htmlContent, $projectName, $outputDir, $pdo) {
    // Pattern to match form tags
    $pattern = '/<!-- form:([^>]+) -->/';
    
    if (preg_match_all($pattern, $htmlContent, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $formName = trim($match[1]);
            
            // Generate form HTML
            $formHtml = generateFormHtml($formName, $projectName, $pdo);
            
            // Replace the form tag with the generated form HTML
            $htmlContent = str_replace($match[0], $formHtml, $htmlContent);
        }
    }
    
    return $htmlContent;
}

// Generate HTML for a form
function generateFormHtml($formName, $projectName, $pdo) {
    try {
        // Get form settings
        $stmt = $pdo->prepare("SELECT * FROM form_settings WHERE form_name = :form_name AND project = :project");
        $stmt->execute(['form_name' => $formName, 'project' => $projectName]);
        $formSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$formSettings) {
            return "<div class='error-message'>Form '$formName' not found</div>";
        }
        
        $formData = json_decode($formSettings['form_json'], true);
        
        if (!$formData || !isset($formData['inputs'])) {
            return "<div class='error-message'>Invalid form configuration</div>";
        }
        
        // Start building the form HTML
        $formId = "form_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", " "], ["_", "a", "a", "u", "u", "o", "o", "_"], strtolower($formName));
        $formHtml = "<div class='web-form-container'>\n";
        
        // Add form title if available
        if (isset($formData['title']) && !empty($formData['title'])) {
            $formHtml .= "  <h3 class='form-title'>" . htmlspecialchars($formData['title']) . "</h3>\n";
        }
        
        // Add form description if available
        if (isset($formData['description']) && !empty($formData['description'])) {
            $formHtml .= "  <p class='form-description'>" . htmlspecialchars($formData['description']) . "</p>\n";
        }
        
        // Start form tag with submission endpoint
        $formHtml .= "  <form id='{$formId}' class='web-form' action='form_submit.php' method='post'>\n";
        $formHtml .= "    <input type='hidden' name='form_name' value='" . htmlspecialchars($formName) . "'>\n";
        $formHtml .= "    <input type='hidden' name='project' value='" . htmlspecialchars($projectName) . "'>\n";
        
        // Add form fields
        foreach ($formData['inputs'] as $input) {
            $formHtml .= "    <div class='form-group'>\n";
            
            if ($input['type'] === 'select' || $input['type'] === 'select2') {
                // Select dropdown
                $formHtml .= "      <label for='{$input['name']}'>" . htmlspecialchars($input['label']) . ($input['required'] ? " *" : "") . "</label>\n";
                $formHtml .= "      <select name='{$input['name']}' id='{$input['name']}'" . ($input['required'] ? " required" : "") . ">\n";
                $formHtml .= "        <option value=''>" . ($input['placeholder'] ? htmlspecialchars($input['placeholder']) : "Please select") . "</option>\n";
                
                if (isset($input['options']) && is_array($input['options'])) {
                    foreach ($input['options'] as $option) {
                        if (isset($option['value'], $option['label'])) {
                            $formHtml .= "        <option value='" . htmlspecialchars($option['value']) . "'>" . htmlspecialchars($option['label']) . "</option>\n";
                        }
                    }
                }
                
                $formHtml .= "      </select>\n";
            } elseif ($input['type'] === 'checkbox') {
                // Checkbox
                $formHtml .= "      <div class='checkbox-group'>\n";
                $formHtml .= "        <input type='checkbox' name='{$input['name']}' id='{$input['name']}' value='1'>\n";
                $formHtml .= "        <label for='{$input['name']}'>" . htmlspecialchars($input['label']) . "</label>\n";
                $formHtml .= "      </div>\n";
            } else {
                // Standard inputs (text, email, number, etc.)
                $inputType = in_array($input['type'], ['text', 'email', 'number', 'tel', 'date', 'password']) ? $input['type'] : 'text';
                $formHtml .= "      <label for='{$input['name']}'>" . htmlspecialchars($input['label']) . ($input['required'] ? " *" : "") . "</label>\n";
                $formHtml .= "      <input type='{$inputType}' name='{$input['name']}' id='{$input['name']}'" . 
                             ($input['placeholder'] ? " placeholder='" . htmlspecialchars($input['placeholder']) . "'" : "") .
                             ($input['required'] ? " required" : "") . ">\n";
            }
            
            $formHtml .= "    </div>\n";
        }
        
        // Add submit button
        $formHtml .= "    <div class='form-group'>\n";
        $formHtml .= "      <button type='submit' class='submit-btn'>Submit</button>\n";
        $formHtml .= "    </div>\n";
        
        // Add form status message container
        $formHtml .= "    <div class='form-status' id='{$formId}_status'></div>\n";
        $formHtml .= "  </form>\n";
        
        // Add JavaScript for form submission handling
        $formHtml .= "  <script>\n";
        $formHtml .= "    document.getElementById('{$formId}').addEventListener('submit', function(e) {\n";
        $formHtml .= "      e.preventDefault();\n";
        $formHtml .= "      var form = this;\n";
        $formHtml .= "      var statusDiv = document.getElementById('{$formId}_status');\n";
        $formHtml .= "      \n";
        $formHtml .= "      statusDiv.innerHTML = '<div class=\"loading\">Submitting...</div>';\n";
        $formHtml .= "      \n";
        $formHtml .= "      // Collect form data\n";
        $formHtml .= "      var formData = new FormData(form);\n";
        $formHtml .= "      \n";
        $formHtml .= "      // Send form data via fetch API\n";
        $formHtml .= "      fetch(form.action, {\n";
        $formHtml .= "        method: 'POST',\n";
        $formHtml .= "        body: formData\n";
        $formHtml .= "      })\n";
        $formHtml .= "      .then(response => response.json())\n";
        $formHtml .= "      .then(data => {\n";
        $formHtml .= "        if (data.success) {\n";
        $formHtml .= "          statusDiv.innerHTML = '<div class=\"success\">' + data.message + '</div>';\n";
        $formHtml .= "          form.reset();\n";
        $formHtml .= "        } else {\n";
        $formHtml .= "          statusDiv.innerHTML = '<div class=\"error\">' + (data.message || 'An error occurred') + '</div>';\n";
        $formHtml .= "        }\n";
        $formHtml .= "      })\n";
        $formHtml .= "      .catch(error => {\n";
        $formHtml .= "        statusDiv.innerHTML = '<div class=\"error\">Network error. Please try again.</div>';\n";
        $formHtml .= "        console.error('Error:', error);\n";
        $formHtml .= "      });\n";
        $formHtml .= "    });\n";
        $formHtml .= "  </script>\n";
        
        $formHtml .= "</div>\n";
        
        return $formHtml;
    } catch (PDOException $e) {
        return "<div class='error-message'>Error generating form: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Get single form entry from database by ID
function getSingleFormEntry($formName, $entryId, $projectName, $pdo) {
    try {
        // Format table name
        $projectNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($projectName));
        $formNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
        $tableName = "{$projectNameFormatted}_{$formNameFormatted}";
        
        // Check if table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute(['tableName' => $tableName]);
        
        if ($stmt->rowCount() === 0) {
            echo "<div style='color:red'>Warning: Form table {$tableName} not found</div><br>";
            return [];
        }
        
        // Get data from the table where id matches
        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $entryId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    } catch (PDOException $e) {
        echo "<div style='color:red'>Error fetching form entry: " . $e->getMessage() . "</div><br>";
        return [];
    }
}

// Get form data from database
function getFormData($formName, $projectName, $pdo) {
    try {
        // Format table name
        $projectNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($projectName));
        $formNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
        $tableName = "{$projectNameFormatted}_{$formNameFormatted}";
        
        // Check if table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute(['tableName' => $tableName]);
        
        if ($stmt->rowCount() === 0) {
            echo "<div style='color:red'>Warning: Form table {$tableName} not found</div><br>";
            return [];
        }
        
        // Get data from the table
        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` ORDER BY id DESC LIMIT 100");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div style='color:red'>Error fetching form data: " . $e->getMessage() . "</div><br>";
        return [];
    }
}

// Copy assets before generating HTML files
copyAssets($outputDir);

// Get all pages for the project, ordered by ID
$stmtPages = $pdo->prepare('SELECT * FROM control_center_web_builder_pages WHERE project_id = ? ORDER BY id ASC');
$stmtPages->execute([$projectId]);
$pages = $stmtPages->fetchAll(PDO::FETCH_ASSOC);

if (count($pages) === 0) {
    die('Error: No pages found for this project.');
}

// For each page, get components ordered by position
$stmtComponents = $pdo->prepare('
    SELECT * FROM control_center_web_builder_components 
    WHERE page_id = ? 
    ORDER BY position ASC
');

// Keep track of the first page to set as index.html
$firstPage = null;

// Process each page
foreach ($pages as $page) {
    // For the first page in the results (lowest ID)
    if ($firstPage === null) {
        $firstPage = $page;
    }
    
    $pageId = $page['id'];
    $pageSlug = $page['slug'];
    $pageTitle = $page['title'];
    $pageMetaDescription = $page['meta_description'];
    
    // Get components for this page
    $stmtComponents->execute([$pageId]);
    $components = $stmtComponents->fetchAll(PDO::FETCH_ASSOC);
    
    // Start output buffering to capture the rendered PHP templates
    ob_start();
    
    // Include header template
    include 'header_template.php';
    
    // Add all components in order
    foreach ($components as $component) {
        // Process form data in HTML code before showing
        $processedHtml = processFormData($component['html_code'], $project['name'], $pdo);
        
        // Process form tags
        $processedHtml = processFormTags($processedHtml, $project['name'], $outputDir, $pdo);
        
        echo $processedHtml . "\n";
    }
    
    // Include footer template
    include 'footer_template.php';
    
    // Get the complete HTML content
    $htmlContent = ob_get_clean();
    
    // Determine filename (index.html for first page, {slug}.html for others)
    $filename = ($page['id'] === $firstPage['id']) ? "index.html" : "{$pageSlug}.html";
    $filePath = $outputDir . $filename;
    
    // Write the HTML file
    if (file_put_contents($filePath, $htmlContent) === false) {
        echo "Failed to write file: {$filename}<br>";
    } else {
        echo "Successfully created: {$filename}<br>";
    }
}

// Nach dem Generieren aller HTML-Seiten: CSS optimieren, wenn gewünscht
if ($optimizeCss) {
    echo "<h3>Optimiere CSS für Projekt...</h3>";
    echo "<pre>";
    // Output-Buffer für bessere Lesbarkeit der Konsole
    ob_start();
    $cssOptimized = optimizeCssForProject($projectId);
    $cssOutput = ob_get_clean();
    // Ersetze Newlines mit BR-Tags für HTML-Ausgabe
    echo nl2br(htmlspecialchars($cssOutput));
    echo "</pre>";
    if ($cssOptimized) {
        echo "<p>CSS wurde erfolgreich optimiert und verkleinert!</p>";
    } else {
        echo "<p>CSS konnte nicht optimiert werden. Vollständige CSS-Datei wurde verwendet.</p>";
    }
} else {
    echo "<h3>CSS-Optimierung übersprungen</h3>";
    echo "<p>CSS-Datei wurde unverändert übernommen. Füge '?css=true' zur URL hinzu, um ungenutzte CSS-Klassen zu entfernen.</p>";
}

echo "<h3>Publishing complete for project '{$project['name']}'</h3>";
echo "<p>Files have been saved to: {$outputDir}</p>";
echo "<ul>";
echo "<li>Homepage: <a href='{$outputDir}index.html' target='_blank'>index.html</a></li>";

// List other pages
foreach ($pages as $page) {
    if ($page['id'] !== $firstPage['id']) {
        echo "<li>{$page['name']}: <a href='{$outputDir}{$page['slug']}.html' target='_blank'>{$page['slug']}.html</a></li>";
    }
}
echo "</ul>";
?>