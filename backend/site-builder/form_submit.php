<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');

// Configuration
$dbConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'alex01d01',
    'username' => 'alex01d01',
    'password' => 'XL2fiPeVH3'
];

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function mapFieldType($type) {
    switch ($type) {
        case 'text':
        case 'email':
        case 'select':
        case 'select2':
            return 'VARCHAR(255)';
        case 'number':
        case 'operation':
            return 'INT';
        case 'checkbox':
            return 'BOOLEAN';
        default:
            return 'VARCHAR(255)';
    }
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is supported.'
    ]);
    exit;
}

// Check required fields
if (!isset($_POST['form_name']) || !isset($_POST['project'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: form_name and project.'
    ]);
    exit;
}

// Get form name and project from POST data
$formName = sanitizeInput($_POST['form_name']);
$projectName = sanitizeInput($_POST['project']);

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Step 1: Get form settings
    $stmt = $pdo->prepare("SELECT * FROM form_settings WHERE form_name = ? AND project = ?");
    $stmt->execute([$formName, $projectName]);
    $formSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formSettings) {
        echo json_encode([
            'success' => false,
            'message' => "Form '$formName' not found for project '$projectName'."
        ]);
        exit;
    }
    
    // Step 2: Parse form structure
    $formData = json_decode($formSettings['form_json'], true);
    if (!$formData || !isset($formData['inputs'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid form configuration.'
        ]);
        exit;
    }
    
    // Step 3: Format table name
    $projectNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($projectName));
    $formNameFormatted = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
    $tableName = "{$projectNameFormatted}_{$formNameFormatted}";
    
    // Step 4: Prepare data for insertion
    $columns = [];
    $placeholders = [];
    $values = [];
    
    foreach ($formData['inputs'] as $input) {
        $fieldName = $input['name'];
        $fieldType = $input['type'];
        
        // Skip if this field is not in the submitted form data
        if (!isset($_POST[$fieldName]) && $fieldType !== 'checkbox') {
            continue;
        }
        
        $columns[] = $fieldName;
        $placeholders[] = "?";
        
        // Special handling for different field types
        if ($fieldType === 'checkbox') {
            $values[] = isset($_POST[$fieldName]) ? 1 : 0;
        } elseif ($fieldType === 'number' || $fieldType === 'operation') {
            $values[] = isset($_POST[$fieldName]) ? intval($_POST[$fieldName]) : 0;
        } else {
            $values[] = isset($_POST[$fieldName]) ? sanitizeInput($_POST[$fieldName]) : '';
        }
    }
    
    // Step 5: Execute database insertion
    if (count($columns) > 0) {
        $sql = "INSERT INTO `{$tableName}` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Form submitted successfully!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save form data.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No valid form fields provided.'
        ]);
    }
    
} catch (PDOException $e) {
    // Log the error (server-side only)
    error_log("Form submission error: " . $e->getMessage());
    
    // Return a generic error message to the user
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.'
    ]);
}
?>