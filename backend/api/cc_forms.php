<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../web-builder/api_base.php';

$userId = authenticateUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$wbProjectId = $_GET['wb_project_id'] ?? $_POST['wb_project_id'] ?? '';

// Validate WB project ID
if (empty($wbProjectId)) {
    // Try to get from POST body for generate action
    $inputData = json_decode(file_get_contents('php://input'), true);
    if ($inputData && !empty($inputData['wb_project_id'])) {
        $wbProjectId = $inputData['wb_project_id'];
    }
}

if (empty($wbProjectId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Web Builder project ID is required']);
    exit;
}

// Get CC project link from WB project
$ccProjectLink = null;
if (!empty($wbProjectId)) {
    $wbProjectId = intval($wbProjectId);

    // Get WB project with CC project reference
    $projectResult = query("SELECT wb.id, wb.project_id as cc_project_id, p.link as cc_project_link 
                           FROM control_center_modul_web_builder_projects wb
                           INNER JOIN projects p ON wb.project_id = p.projectID
                           WHERE wb.id = $wbProjectId");

    if (!$projectResult || mysqli_num_rows($projectResult) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Web Builder project not found']);
        exit;
    }

    $projectData = fetch_assoc($projectResult);
    $ccProjectLink = $projectData['cc_project_link'];
    $ccProjectId = $projectData['cc_project_id'];

    // Check if user has access to this CC project
    if (!userHasProjectAccess($userId, $ccProjectId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied to this project']);
        exit;
    }
}

switch ($action) {
    case 'list':
        listForms($ccProjectLink);
        break;
    case 'get':
        $formName = $_GET['form'] ?? $_POST['form'] ?? '';
        getForm($ccProjectLink, $formName);
        break;
    case 'generate':
        generateFormHtml($ccProjectLink);
        break;
    default:
        echo json_encode(['error' => 'Invalid action. Use: list, get, or generate']);
}

/**
 * Liste aller Forms eines Projekts
 */
function listForms($project)
{
    if (empty($project)) {
        echo json_encode(['error' => 'Project is required']);
        return;
    }

    $projectClean = mysqli_real_escape_string($GLOBALS['con'], $project);
    $result = query("SELECT form_id, form_name, form_json, created_at FROM form_settings WHERE project='$projectClean' ORDER BY form_name ASC");

    $forms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $formData = json_decode($row['form_json'], true);
        $fields = [];

        if (isset($formData['inputs']) && is_array($formData['inputs'])) {
            foreach ($formData['inputs'] as $input) {
                $fields[] = [
                    'name' => $input['name'] ?? '',
                    'label' => $input['label'] ?? $input['name'] ?? '',
                    'type' => $input['type'] ?? 'text',
                    'required' => $input['required'] ?? false,
                    'options' => $input['options'] ?? []
                ];
            }
        }

        // Generate table name (same logic as form.php)
        $formTitle = $formData['title'] ?? $row['form_name'];
        $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($project . "_" . $formTitle));

        // Get entry count from the form's data table
        $entryCount = 0;
        $tableExists = query("SHOW TABLES LIKE '$tableName'");
        if ($tableExists && mysqli_num_rows($tableExists) > 0) {
            $countResult = query("SELECT COUNT(*) as count FROM `$tableName`");
            if ($countResult) {
                $countRow = mysqli_fetch_assoc($countResult);
                $entryCount = intval($countRow['count']);
            }
        }

        $forms[] = [
            'id' => $row['form_id'],
            'name' => $row['form_name'],
            'title' => $formTitle,
            'table_name' => $tableName,
            'entry_count' => $entryCount,
            'description' => $formData['description'] ?? '',
            'fields' => $fields,
            'fieldCount' => count($fields),
            'createdAt' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'project' => $project,
        'forms' => $forms,
        'count' => count($forms)
    ]);
}

/**
 * Details einer einzelnen Form
 */
function getForm($project, $formName)
{
    if (empty($project) || empty($formName)) {
        echo json_encode(['error' => 'Project and form name are required']);
        return;
    }

    $projectClean = mysqli_real_escape_string($GLOBALS['con'], $project);
    $formNameClean = mysqli_real_escape_string($GLOBALS['con'], $formName);

    $result = query("SELECT * FROM form_settings WHERE project='$projectClean' AND form_name='$formNameClean'");

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['error' => 'Form not found']);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $formData = json_decode($row['form_json'], true);

    $fields = [];
    if (isset($formData['inputs']) && is_array($formData['inputs'])) {
        foreach ($formData['inputs'] as $input) {
            $fields[] = [
                'name' => $input['name'] ?? '',
                'label' => $input['label'] ?? $input['name'] ?? '',
                'type' => $input['type'] ?? 'text',
                'required' => $input['required'] ?? false,
                'placeholder' => $input['placeholder'] ?? '',
                'options' => $input['options'] ?? [],
                'defaultValue' => $input['defaultValue'] ?? ''
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'form' => [
            'id' => $row['form_id'],
            'name' => $row['form_name'],
            'title' => $formData['title'] ?? $row['form_name'],
            'description' => $formData['description'] ?? '',
            'fields' => $fields,
            'createdAt' => $row['created_at']
        ]
    ]);
}

function generateFormHtml($ccProjectLink)
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        // Fallback auf POST
        $input = $_POST;
    }

    $project = $ccProjectLink; // Use the CC project link determined from wb_project_id
    $formName = $input['form'] ?? '';
    $selectedFields = $input['fields'] ?? null; // null = alle Felder
    $style = $input['style'] ?? 'modern';
    $submitText = $input['submitText'] ?? 'Absenden';
    $successMessage = $input['successMessage'] ?? 'Erfolgreich gesendet!';
    $errorMessage = $input['errorMessage'] ?? 'Fehler beim Senden.';
    $showTitle = $input['showTitle'] ?? true;
    $showDescription = $input['showDescription'] ?? true;

    if (empty($project) || empty($formName)) {
        echo json_encode(['error' => 'Project and form are required']);
        return;
    }

    // Get form from DB
    $projectClean = mysqli_real_escape_string($GLOBALS['con'], $project);
    $formNameClean = mysqli_real_escape_string($GLOBALS['con'], $formName);

    $result = query("SELECT * FROM form_settings WHERE project='$projectClean' AND form_name='$formNameClean'");

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['error' => 'Form not found']);
        return;
    }

    $row = mysqli_fetch_assoc($result);
    $formData = json_decode($row['form_json'], true);

    // Filter fields if specified
    $fields = $formData['inputs'] ?? [];
    if ($selectedFields !== null && is_array($selectedFields)) {
        $fields = array_filter($fields, function ($field) use ($selectedFields) {
            return in_array($field['name'], $selectedFields);
        });
        // Reorder fields based on selectedFields order
        $orderedFields = [];
        foreach ($selectedFields as $fieldName) {
            foreach ($fields as $field) {
                if ($field['name'] === $fieldName) {
                    $orderedFields[] = $field;
                    break;
                }
            }
        }
        $fields = $orderedFields;
    }

    // Generate HTML based on style
    $html = generateStyledForm(
        $formName,
        $project,
        $formData['title'] ?? $formName,
        $formData['description'] ?? '',
        $fields,
        $style,
        $submitText,
        $successMessage,
        $errorMessage,
        $showTitle,
        $showDescription
    );

    echo json_encode([
        'success' => true,
        'html' => $html,
        'formName' => $formName,
        'project' => $project,
        'fieldCount' => count($fields)
    ]);
}

/**
 * Generiert gestyltes HTML für ein Formular
 */
function generateStyledForm($formName, $project, $title, $description, $fields, $style, $submitText, $successMsg, $errorMsg, $showTitle, $showDesc)
{
    $formId = 'cc-form-' . preg_replace('/[^a-z0-9]/', '-', strtolower($formName));
    $escapedFormName = htmlspecialchars($formName, ENT_QUOTES);
    $escapedProject = htmlspecialchars($project, ENT_QUOTES);
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES);
    $escapedDesc = htmlspecialchars($description, ENT_QUOTES);
    $escapedSuccessMsg = htmlspecialchars($successMsg, ENT_QUOTES);
    $escapedErrorMsg = htmlspecialchars($errorMsg, ENT_QUOTES);
    $escapedSubmitText = htmlspecialchars($submitText, ENT_QUOTES);

    // Style classes
    $styles = getStyleClasses($style);

    $html = "<section class=\"{$styles['section']}\">\n";
    $html .= "  <div class=\"{$styles['container']}\">\n";

    // Title & Description
    if ($showTitle || $showDesc) {
        $html .= "    <div class=\"{$styles['header']}\">\n";
        if ($showTitle) {
            $html .= "      <h2 class=\"{$styles['title']}\">{$escapedTitle}</h2>\n";
        }
        if ($showDesc && !empty($description)) {
            $html .= "      <p class=\"{$styles['description']}\">{$escapedDesc}</p>\n";
        }
        $html .= "    </div>\n";
    }

    // Form
    $html .= "    <form id=\"{$formId}\" data-cc-form=\"{$escapedFormName}\" data-cc-project=\"{$escapedProject}\" ";
    $html .= "data-cc-success=\"{$escapedSuccessMsg}\" data-cc-error=\"{$escapedErrorMsg}\" ";
    $html .= "class=\"{$styles['form']}\">\n";

    // Fields
    foreach ($fields as $field) {
        $html .= generateFieldHtml($field, $styles);
    }

    // Submit Button
    $html .= "      <div class=\"{$styles['buttonWrapper']}\">\n";
    $html .= "        <button type=\"submit\" class=\"{$styles['button']}\">{$escapedSubmitText}</button>\n";
    $html .= "      </div>\n";

    $html .= "    </form>\n";
    $html .= "  </div>\n";
    $html .= "</section>";

    return $html;
}

/**
 * Style Klassen für verschiedene Themes
 */
function getStyleClasses($style)
{
    $styles = [
        'modern' => [
            'section' => 'bg-gray-50 py-16 sm:py-24',
            'container' => 'mx-auto max-w-xl px-6 lg:px-8',
            'header' => 'text-center mb-10',
            'title' => 'text-3xl font-bold tracking-tight text-gray-900',
            'description' => 'mt-4 text-lg text-gray-600',
            'form' => 'space-y-6',
            'fieldWrapper' => 'space-y-2',
            'label' => 'block text-sm font-semibold text-gray-900',
            'input' => 'block w-full rounded-lg border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm',
            'textarea' => 'block w-full rounded-lg border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm',
            'select' => 'block w-full rounded-lg border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm',
            'checkbox' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600',
            'checkboxLabel' => 'ml-3 text-sm text-gray-600',
            'buttonWrapper' => 'pt-4',
            'button' => 'w-full rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors'
        ],
        'minimal' => [
            'section' => 'py-12',
            'container' => 'mx-auto max-w-lg px-4',
            'header' => 'mb-8',
            'title' => 'text-2xl font-semibold text-gray-900',
            'description' => 'mt-2 text-gray-600',
            'form' => 'space-y-4',
            'fieldWrapper' => 'space-y-1',
            'label' => 'block text-sm font-medium text-gray-700',
            'input' => 'block w-full rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm',
            'textarea' => 'block w-full rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm',
            'select' => 'block w-full rounded border border-gray-300 px-3 py-2 text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:text-sm',
            'checkbox' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500',
            'checkboxLabel' => 'ml-2 text-sm text-gray-600',
            'buttonWrapper' => 'pt-2',
            'button' => 'rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
        ],
        'card' => [
            'section' => 'bg-white py-16',
            'container' => 'mx-auto max-w-md px-6',
            'header' => 'text-center mb-8',
            'title' => 'text-2xl font-bold text-gray-900',
            'description' => 'mt-3 text-gray-500',
            'form' => 'bg-white rounded-2xl shadow-xl p-8 space-y-6 ring-1 ring-gray-100',
            'fieldWrapper' => 'space-y-2',
            'label' => 'block text-sm font-medium text-gray-700',
            'input' => 'block w-full rounded-xl border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm',
            'textarea' => 'block w-full rounded-xl border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm',
            'select' => 'block w-full rounded-xl border-0 px-4 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm',
            'checkbox' => 'h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500',
            'checkboxLabel' => 'ml-3 text-sm text-gray-600',
            'buttonWrapper' => 'pt-4',
            'button' => 'w-full rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg hover:from-indigo-500 hover:to-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all'
        ]
    ];

    return $styles[$style] ?? $styles['modern'];
}

/**
 * Generiert HTML für ein einzelnes Feld
 */
function generateFieldHtml($field, $styles)
{
    $name = htmlspecialchars($field['name'], ENT_QUOTES);
    $label = htmlspecialchars($field['label'] ?? $field['name'], ENT_QUOTES);
    $type = $field['type'] ?? 'text';
    $required = !empty($field['required']) ? 'required' : '';
    $placeholder = htmlspecialchars($field['placeholder'] ?? '', ENT_QUOTES);
    $fieldId = 'field-' . preg_replace('/[^a-z0-9]/', '-', strtolower($name));

    $html = "      <div class=\"{$styles['fieldWrapper']}\">\n";

    switch ($type) {
        case 'textarea':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <textarea name=\"{$name}\" id=\"{$fieldId}\" rows=\"4\" placeholder=\"{$placeholder}\" {$required} class=\"{$styles['textarea']}\"></textarea>\n";
            break;

        case 'select':
        case 'select2':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <select name=\"{$name}\" id=\"{$fieldId}\" {$required} class=\"{$styles['select']}\">\n";
            $html .= "          <option value=\"\">Bitte wählen...</option>\n";
            if (!empty($field['options'])) {
                foreach ($field['options'] as $option) {
                    $optVal = htmlspecialchars(is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option, ENT_QUOTES);
                    $optLabel = htmlspecialchars(is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option, ENT_QUOTES);
                    $html .= "          <option value=\"{$optVal}\">{$optLabel}</option>\n";
                }
            }
            $html .= "        </select>\n";
            break;

        case 'checkbox':
            $html .= "        <div class=\"flex items-start\">\n";
            $html .= "          <input type=\"checkbox\" name=\"{$name}\" id=\"{$fieldId}\" value=\"1\" {$required} class=\"{$styles['checkbox']} mt-1\">\n";
            $html .= "          <label for=\"{$fieldId}\" class=\"{$styles['checkboxLabel']}\">{$label}</label>\n";
            $html .= "        </div>\n";
            break;

        case 'email':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <input type=\"email\" name=\"{$name}\" id=\"{$fieldId}\" placeholder=\"{$placeholder}\" {$required} autocomplete=\"email\" class=\"{$styles['input']}\">\n";
            break;

        case 'number':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <input type=\"number\" name=\"{$name}\" id=\"{$fieldId}\" placeholder=\"{$placeholder}\" {$required} class=\"{$styles['input']}\">\n";
            break;

        case 'date':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <input type=\"date\" name=\"{$name}\" id=\"{$fieldId}\" {$required} class=\"{$styles['input']}\">\n";
            break;

        case 'time':
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <input type=\"time\" name=\"{$name}\" id=\"{$fieldId}\" {$required} class=\"{$styles['input']}\">\n";
            break;

        default: // text
            $html .= "        <label for=\"{$fieldId}\" class=\"{$styles['label']}\">{$label}</label>\n";
            $html .= "        <input type=\"text\" name=\"{$name}\" id=\"{$fieldId}\" placeholder=\"{$placeholder}\" {$required} class=\"{$styles['input']}\">\n";
    }

    $html .= "      </div>\n";
    return $html;
}
?>