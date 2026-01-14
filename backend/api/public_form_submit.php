<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]));
}

function logFormSubmit($message, $data = null)
{
    $logFile = '/var/log/cc_public_form_submit.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if ($data) {
        $logEntry .= " | " . json_encode($data);
    }
    @file_put_contents($logFile, $logEntry . "\n", FILE_APPEND);
    error_log("[PublicFormSubmit] $message");
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = [
        'project' => $_POST['project'] ?? null,
        'form_name' => $_POST['form_name'] ?? null,
        'data' => $_POST['data'] ?? $_POST,
        'source' => $_POST['source'] ?? 'unknown'
    ];

    // Wenn data als String kommt, JSON decodieren
    if (isset($input['data']) && is_string($input['data'])) {
        $input['data'] = json_decode($input['data'], true) ?? [];
    }

    // Filtere system felder aus data
    unset($input['data']['project'], $input['data']['form_name'], $input['data']['source']);
}

logFormSubmit("Received submission", ['project' => $input['project'] ?? 'none', 'form' => $input['form_name'] ?? 'none']);

// Validierung
if (empty($input['project'])) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Missing required field: project'
    ]));
}

if (empty($input['form_name'])) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Missing required field: form_name'
    ]));
}

if (empty($input['data']) || !is_array($input['data'])) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Missing or invalid field: data (must be an object)'
    ]));
}

// Sanitize inputs
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function sanitizeFieldName($name)
{
    // Ersetze Sonderzeichen für DB-Feldnamen
    $replacements = [
        "-" => "_",
        "ä" => "a",
        "Ä" => "a",
        "ü" => "u",
        "Ü" => "u",
        "ö" => "o",
        "Ö" => "o",
        "(" => "",
        ")" => "",
        " " => "_",
        "." => "_",
        "," => "_",
        "!" => "",
        "?" => "",
        "@" => "",
        "#" => "",
        "$" => "",
        "%" => "",
        "^" => "",
        "&" => "",
        "*" => "",
        "+" => "",
        "=" => "",
        "[" => "",
        "]" => "",
        "{" => "",
        "}" => "",
        "|" => "",
        "\\" => "",
        ":" => "",
        ";" => "",
        "\"" => "",
        "'" => "",
        "<" => "",
        ">" => "",
        "/" => ""
    ];
    return strtolower(str_replace(array_keys($replacements), array_values($replacements), $name));
}

$project = sanitizeInput($input['project']);
$formName = sanitizeInput($input['form_name']);
$formData = $input['data'];
$source = sanitizeInput($input['source'] ?? 'web-builder');

// Rate Limiting (einfache Version basierend auf IP)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = "/tmp/form_ratelimit_" . md5($clientIP . $project . $formName);
$rateLimitWindow = 60;
$rateLimitMax = 10;

if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true);
    if ($rateLimitData && time() - $rateLimitData['start'] < $rateLimitWindow) {
        if ($rateLimitData['count'] >= $rateLimitMax) {
            http_response_code(429);
            logFormSubmit("Rate limit exceeded", ['ip' => $clientIP]);
            die(json_encode([
                'success' => false,
                'error' => 'Too many requests. Please wait a moment.'
            ]));
        }
        $rateLimitData['count']++;
    } else {
        $rateLimitData = ['start' => time(), 'count' => 1];
    }
} else {
    $rateLimitData = ['start' => time(), 'count' => 1];
}
file_put_contents($rateLimitFile, json_encode($rateLimitData));

try {
    require_once '../jwt_helper.php';
    require_once 'config.php';

    $origin_url = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
    $allowed_origins = ['alexsblog.de', 'localhost:8100', 'polan.sk', 'http://localhost:8100/login', 'http://localhost:8100', 'localhost'];
    $request_host = parse_url($origin_url, PHP_URL_HOST);
    $host_domain = implode('.', array_slice(explode('.', $request_host), -2));
    ini_set('display_errors', true);
    session_start();

    require_once "../use_template_function.php";
    require_once "../db_connection.php";
    require_once "../functions.php";

    $projectClean = mysqli_real_escape_string($GLOBALS['con'], $project);
    $formNameClean = mysqli_real_escape_string($GLOBALS['con'], $formName);
    $formCheck = query("SELECT * FROM form_settings WHERE project='$projectClean' AND form_name='$formNameClean'");

    if (mysqli_num_rows($formCheck) === 0) {
        http_response_code(404);
        logFormSubmit("Form not found", ['project' => $project, 'form' => $formName]);
        die(json_encode([
            'success' => false,
            'error' => "Form '$formName' not found in project '$project'"
        ]));
    }

    $formSettings = mysqli_fetch_assoc($formCheck);
    $formJson = json_decode($formSettings['form_json'], true);

    // Generiere Tabellennamen (gleiche Logik wie form.php)
    $tableProject = sanitizeFieldName($project);
    $tableForm = sanitizeFieldName($formName);
    $tableName = "{$tableProject}_{$tableForm}";

    // Bereite Daten für Insert vor
    $columns = [];
    $values = [];
    $placeholders = [];

    // Validiere Felder gegen Form-Definition (optional)
    $allowedFields = [];
    if (isset($formJson['inputs']) && is_array($formJson['inputs'])) {
        foreach ($formJson['inputs'] as $input) {
            $allowedFields[] = sanitizeFieldName($input['name']);
        }
    }

    foreach ($formData as $fieldName => $fieldValue) {
        $cleanFieldName = sanitizeFieldName($fieldName);

        // Skip wenn Feld nicht in Form definiert (Sicherheit)
        if (!empty($allowedFields) && !in_array($cleanFieldName, $allowedFields)) {
            logFormSubmit("Skipping unknown field: $cleanFieldName");
            continue;
        }

        $columns[] = $cleanFieldName;
        $cleanValue = escape_string(sanitizeInput($fieldValue));
        $values[] = "'$cleanValue'";
    }

    // Füge Metadaten hinzu
    // $columns[] = '_submitted_from';
    // $values[] = "'" . mysqli_real_escape_string($GLOBALS['con'], $source) . "'";

    // $columns[] = '_submitted_ip';
    // $values[] = "'" . mysqli_real_escape_string($GLOBALS['con'], $clientIP) . "'";

    if (empty($columns)) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'error' => 'No valid form fields provided'
        ]));
    }

    // Insert ausführen
    $columnsStr = implode(', ', $columns);
    $valuesStr = implode(', ', $values);
    $sql = "INSERT INTO `$tableName` ($columnsStr) VALUES ($valuesStr)";

    if (query($sql)) {
        $newId = mysqli_insert_id($GLOBALS['con']);

        logFormSubmit("Form submitted successfully", [
            'project' => $project,
            'form' => $formName,
            'id' => $newId,
            'source' => $source
        ]);

        // Trigger System aufrufen (wenn vorhanden)
        $triggerFile = __DIR__ . '/../form_triggers.php';
        if (file_exists($triggerFile)) {
            require_once $triggerFile;
            if (class_exists('FormTriggers')) {
                $triggerSystem = new FormTriggers();
                $triggerData = $formData;
                $triggerData['id'] = $newId;
                $triggerData['table'] = $tableName;
                $triggerData['_source'] = $source;
                $triggerSystem->executeTriggers($project, $formName, 'insert', $triggerData);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Form submitted successfully',
            'id' => $newId
        ]);

    } else {
        $error = mysqli_error($GLOBALS['con']);
        logFormSubmit("Database error", ['error' => $error, 'sql' => $sql]);

        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Failed to save form data'
        ]));
    }

} catch (Exception $e) {
    logFormSubmit("Exception", ['error' => $e->getMessage()]);
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]));
}
?>