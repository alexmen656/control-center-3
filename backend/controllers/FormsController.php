<?php

require_once __DIR__ . '/../triggers.php';

class FormsController
{
    private static function sanitizeName(string $name): string
    {
        return str_replace(
            ["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"],
            ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""],
            strtolower($name)
        );
    }

    private static function buildTableName(string $project, string $formName): string
    {
        return self::sanitizeName($project) . '_' . self::sanitizeName($formName);
    }

    private static function mapFieldType(string $type): string
    {
        switch ($type) {
            case 'text':
            case 'email':
            case 'select':
            case 'select2':
            case 'time':
            case 'date':
                return 'VARCHAR(255)';
            case 'textarea':
                return 'TEXT';
            case 'number':
            case 'operation':
                return 'INT';
            case 'checkbox':
                return 'BOOLEAN';
            default:
                return 'VARCHAR(255)';
        }
    }

    /**
     * POST /v2/forms
     * Create a new form
     */
    public function create(Request $request, Response $response): void
    {
        $formJSON = $request->input('form');
        $formName = escape_string($request->input('name'));
        $project = escape_string($request->input('project'));

        if (!$formJSON || !$formName || !$project) {
            $response->error('form, name and project are required', 400);
            return;
        }

        $projectID = getProjectID($project);
        $tablesSectionId = null;
        if ($projectID) {
            $tablesSection = query("SELECT id FROM project_sidebar_sections WHERE projectID='$projectID' AND slug='tables' LIMIT 1");
            if (mysqli_num_rows($tablesSection) > 0) {
                $tablesSectionId = fetch_assoc($tablesSection)['id'];
            }
        }

        if (!query("INSERT INTO form_settings (form_name, form_json, project, section_id, icon) VALUES ('$formName', '$formJSON', '$project', " . ($tablesSectionId ? "'$tablesSectionId'" : "NULL") . ", 'list-outline')")) {
            $response->error('Failed to create form', 500);
            return;
        }

        $data = json_decode($formJSON, true);

        if ($data && isset($data['title'], $data['inputs'])) {
            $title = self::sanitizeName($data['title']);
            $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($project . "_" . $title));
            $fields = $data['inputs'];
            $sql = "CREATE TABLE $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY";

            foreach ($fields as $field) {
                $name = self::sanitizeName($field['name']);
                $type = self::mapFieldType($field['type']);
                $sql .= ", $name $type";
            }
            $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $sql .= ");";

            if (query($sql)) {
                $response->json(['success' => true, 'message' => $formName . ' Created Successfully']);
                return;
            }
        }

        $response->json(['success' => true, 'message' => 'Form settings saved']);
    }

    /**
     * GET /v2/forms/single
     * Get a single form definition
     */
    public function get(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form and project are required', 400);
            return;
        }

        $query = query("SELECT * FROM form_settings WHERE form_name='$formName' AND project='$project'");
        if (mysqli_num_rows($query) > 0) {
            $form = fetch_assoc($query);
            $response->json([
                'id' => $form['form_id'],
                'form' => json_decode($form['form_json'], true),
                'createdOn' => $form['created_at'],
            ]);
        } else {
            $response->json([]);
        }
    }

    /**
     * GET /v2/forms/data
     * Get form table data
     */
    public function getData(Request $request, Response $response): void
    {
        $formName = self::sanitizeName(escape_string($request->input('form')));
        $projectName = self::sanitizeName(escape_string($request->input('project')));
        $tableName = $projectName . '_' . $formName;

        $data = query("SELECT * FROM `$tableName` LIMIT 100");
        $json = [];

        if (mysqli_num_rows($data) > 0) {
            $fieldNamesResult = query("SHOW COLUMNS FROM `$tableName`");
            $fieldNames = [];
            while ($row = mysqli_fetch_assoc($fieldNamesResult)) {
                $fieldNames[] = $row['Field'];
            }

            while ($row = mysqli_fetch_assoc($data)) {
                $formattedRow = [];
                foreach ($fieldNames as $field) {
                    $formattedRow[$field] = $row[$field];
                }
                $json[] = $formattedRow;
            }
        }

        $response->json($json);
    }

    /**
     * GET /v2/forms/list
     * Get all forms for a project
     */
    public function list(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project'));

        if (!$project) {
            $response->error('project is required', 400);
            return;
        }

        $forms = query("SELECT * FROM form_settings WHERE project='$project'");
        $json = [];
        $i = 0;
        foreach ($forms as $form) {
            $json[$i]['id'] = $form['form_id'];
            $json[$i]['form'] = json_decode($form['form_json'], true);
            $json[$i]['createdOn'] = $form['created_at'];
            $i++;
        }

        $response->json($json);
    }

    /**
     * POST /v2/forms/submit
     * Submit form data
     */
    public function submit(Request $request, Response $response): void
    {
        $form = json_decode($request->input('form'), true);
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$form || !$formName || !$project) {
            $response->error('form, form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        $columns = [];
        $values = [];

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = self::sanitizeName(escape_string($fieldName));
            $fieldValue = escape_string($fieldValue);
            $columns[] = $fieldName;
            $values[] = "'$fieldValue'";
        }

        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);

        $sql = "INSERT INTO $tableName ($columnsStr) VALUES ($valuesStr)";
        if (query($sql)) {
            $newId = mysqli_insert_id($GLOBALS['con']);

            $triggerSystem = new FormTriggers();
            $triggerData = $form;
            $triggerData['id'] = $newId;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $formName, 'insert', $triggerData);

            $response->json(['success' => true, 'message' => 'Form data submitted successfully']);
        } else {
            $response->error('Error submitting form data', 500);
        }
    }

    /**
     * DELETE /v2/forms/entry/{id}
     * Delete a form entry
     */
    public function deleteEntry(Request $request, Response $response): void
    {
        $id = escape_string($request->params['id']);
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$id || !$formName || !$project) {
            $response->error('id, form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        $sql = "DELETE FROM $tableName WHERE id='$id'";
        if (query($sql)) {
            $triggerSystem = new FormTriggers();
            $triggerData = ['id' => $id, 'table' => $tableName];
            $triggerSystem->executeTriggers($project, $formName, 'delete', $triggerData);

            $response->json(['success' => true, 'message' => 'Entry deleted successfully']);
        } else {
            $response->error('Error deleting entry', 500);
        }
    }

    /**
     * PUT /v2/forms/entry/{id}
     * Update a form entry
     */
    public function updateEntry(Request $request, Response $response): void
    {
        $id = escape_string($request->params['id']);
        $form = json_decode($request->input('form'), true);
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$id || !$form || !$formName || !$project) {
            $response->error('id, form, form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);
        $updates = [];

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = self::sanitizeName(escape_string($fieldName));
            $fieldValue = escape_string($fieldValue);
            $updates[] = "$fieldName = '$fieldValue'";
        }

        $updatesStr = implode(', ', $updates);
        $sql = "UPDATE $tableName SET $updatesStr WHERE id='$id'";

        if (query($sql)) {
            $triggerSystem = new FormTriggers();
            $triggerData = $form;
            $triggerData['id'] = $id;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $formName, 'update', $triggerData);

            $response->json(['success' => true, 'message' => 'Entry updated successfully']);
        } else {
            $response->error('Error updating entry', 500);
        }
    }

    /**
     * GET /v2/forms/exists
     * Check if a form exists
     */
    public function exists(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $query = query("SELECT * FROM form_settings WHERE form_name='$formName' AND project='$project'");
        $exists = mysqli_num_rows($query) > 0;

        $response->json(['exists' => $exists]);
    }

    /**
     * POST /v2/forms/rename
     * Rename a form
     */
    public function rename(Request $request, Response $response): void
    {
        $oldFormName = escape_string($request->input('old_form_name'));
        $newFormName = escape_string($request->input('new_form_name'));
        $project = escape_string($request->input('project'));

        if (!$oldFormName || !$newFormName || !$project) {
            $response->error('old_form_name, new_form_name and project are required', 400);
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9-_]+$/', $newFormName)) {
            $response->json(['success' => false, 'error' => 'Ungültiger Formname. Verwenden Sie nur Buchstaben, Zahlen, Bindestriche und Unterstriche.']);
            return;
        }

        $checkQuery = query("SELECT * FROM form_settings WHERE form_name='$newFormName' AND project='$project'");
        if (mysqli_num_rows($checkQuery) > 0) {
            $response->json(['success' => false, 'error' => 'Eine Form mit diesem Namen existiert bereits.']);
            return;
        }

        $oldFormQuery = query("SELECT * FROM form_settings WHERE form_name='$oldFormName' AND project='$project'");
        if (mysqli_num_rows($oldFormQuery) == 0) {
            $response->json(['success' => false, 'error' => 'Ursprüngliche Form nicht gefunden.']);
            return;
        }

        $oldTableName = self::buildTableName($project, $oldFormName);
        $newTableName = self::buildTableName($project, $newFormName);

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            $updateFormQuery = "UPDATE form_settings SET form_name='$newFormName' WHERE form_name='$oldFormName' AND project='$project'";
            if (!query($updateFormQuery)) {
                throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
            }

            $tableExistsQuery = query("SHOW TABLES LIKE '$oldTableName'");
            if (mysqli_num_rows($tableExistsQuery) > 0) {
                $renameTableQuery = "RENAME TABLE `$oldTableName` TO `$newTableName`";
                if (!query($renameTableQuery)) {
                    throw new Exception('Fehler beim Umbenennen der Datentabelle');
                }
            }

            if (class_exists('FormTriggers')) {
                $triggerSystem = new FormTriggers();
                $triggerSystem->renameFormTriggers($project, $oldFormName, $newFormName);
            }

            mysqli_commit($GLOBALS['con']);
            $response->json(['success' => true, 'message' => 'Form erfolgreich umbenannt']);
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->json(['success' => false, 'error' => $e->getMessage()]);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * PUT /v2/forms/structure
     * Update form structure
     */
    public function updateStructure(Request $request, Response $response): void
    {
        $formJSON = $request->input('form');
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formJSON || !$formName || !$project) {
            $response->error('form, form_name and project are required', 400);
            return;
        }

        $formData = json_decode($formJSON, true);
        if (!$formData || !isset($formData['title'], $formData['inputs'])) {
            $response->json(['success' => false, 'error' => 'Ungültiges JSON-Format']);
            return;
        }

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            $updateFormQuery = "UPDATE form_settings SET form_json='$formJSON' WHERE form_name='$formName' AND project='$project'";
            if (!query($updateFormQuery)) {
                throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
            }

            $tableName = createTableName($project . "_" . $formName);

            $tableExistsQuery = query("SHOW TABLES LIKE '$tableName'");
            if (mysqli_num_rows($tableExistsQuery) > 0) {
                $existingColumnsResult = query("SHOW COLUMNS FROM `$tableName`");
                $existingColumns = [];
                while ($column = fetch_assoc($existingColumnsResult)) {
                    $existingColumns[] = $column['Field'];
                }

                foreach ($formData['inputs'] as $field) {
                    $fieldName = self::sanitizeName($field['name']);

                    if (!in_array($fieldName, $existingColumns)) {
                        $fieldType = self::mapFieldType($field['type']);
                        $alterSql = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldType";
                        if (!query($alterSql)) {
                            throw new Exception("Fehler beim Hinzufügen der Spalte: $fieldName");
                        }
                    }
                }
            }

            mysqli_commit($GLOBALS['con']);
            $response->json(['success' => true, 'message' => 'Form-Struktur erfolgreich aktualisiert']);
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->json(['success' => false, 'error' => $e->getMessage()]);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * GET /v2/forms/schema
     * Get form schema
     */
    public function getSchema(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $query = query("SELECT form_json FROM form_settings WHERE form_name='$formName' AND project='$project'");
        if (mysqli_num_rows($query) > 0) {
            $form = fetch_assoc($query);
            $formData = json_decode($form['form_json'], true);

            if ($formData && isset($formData['inputs'])) {
                $schema = [];
                foreach ($formData['inputs'] as $input) {
                    $schema[] = [
                        'name' => self::sanitizeName($input['name']),
                        'label' => $input['label'] ?? $input['name'],
                        'type' => $input['type'] ?? 'text',
                        'placeholder' => $input['placeholder'] ?? '',
                        'options' => $input['options'] ?? []
                    ];
                }
                $response->json(['success' => true, 'schema' => $schema]);
            } else {
                $response->json(['success' => false, 'error' => 'Invalid form schema']);
            }
        } else {
            $response->json(['success' => false, 'error' => 'Form not found']);
        }
    }

    /**
     * GET /v2/forms/entry/{id}
     * Get a single form entry
     */
    public function getEntry(Request $request, Response $response): void
    {
        $entryId = escape_string($request->params['id']);
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$entryId || !$formName || !$project) {
            $response->error('id, form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        $query = query("SELECT * FROM `$tableName` WHERE id='$entryId'");
        if (mysqli_num_rows($query) > 0) {
            $entry = fetch_assoc($query);
            $response->json(['success' => true, 'entry' => $entry]);
        } else {
            $response->json(['success' => false, 'error' => 'Entry not found']);
        }
    }

    /**
     * GET /v2/forms/tables
     * Get all tables for a project
     */
    public function getTables(Request $request, Response $response): void
    {
        $project = escape_string($request->input('project'));

        if (!$project) {
            $response->error('project is required', 400);
            return;
        }

        $formsQuery = query("SELECT form_name, form_json, created_at FROM form_settings WHERE project='$project' ORDER BY created_at DESC");
        $tables = [];

        while ($form = fetch_assoc($formsQuery)) {
            $tableName = self::buildTableName($project, $form['form_name']);

            $tableExistsQuery = query("SHOW TABLES LIKE '$tableName'");
            $exists = mysqli_num_rows($tableExistsQuery) > 0;
            $rowCount = 0;

            if ($exists) {
                $countQuery = query("SELECT COUNT(*) as count FROM `$tableName`");
                if ($countQuery) {
                    $countResult = fetch_assoc($countQuery);
                    $rowCount = $countResult['count'];
                }
            }

            $formData = json_decode($form['form_json'], true);
            $fieldCount = isset($formData['inputs']) ? count($formData['inputs']) : 0;

            $tables[] = [
                'name' => $form['form_name'],
                'table_name' => $tableName,
                'exists' => $exists,
                'row_count' => $rowCount,
                'field_count' => $fieldCount,
                'created_at' => $form['created_at']
            ];
        }

        $response->json(['success' => true, 'tables' => $tables]);
    }

    /**
     * DELETE /v2/forms/table
     * Drop a form table
     */
    public function dropTable(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        mysqli_autocommit($GLOBALS['con'], false);

        try {
            $dropQuery = "DROP TABLE IF EXISTS `$tableName`";
            if (!query($dropQuery)) {
                throw new Exception('Fehler beim Löschen der Tabelle');
            }

            $deleteFormQuery = "DELETE FROM form_settings WHERE form_name='$formName' AND project='$project'";
            if (!query($deleteFormQuery)) {
                throw new Exception('Fehler beim Löschen der Form-Einstellungen');
            }

            mysqli_commit($GLOBALS['con']);
            $response->json(['success' => true, 'message' => 'Form und Tabelle erfolgreich gelöscht']);
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->json(['success' => false, 'error' => $e->getMessage()]);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    /**
     * GET /v2/forms/tables-from-project
     * Get tables from a source project for import
     */
    public function getTablesFromProject(Request $request, Response $response): void
    {
        $sourceProject = escape_string($request->input('source_project'));

        if (!$sourceProject) {
            $response->error('source_project is required', 400);
            return;
        }

        $tables = [];
        $result = query("SELECT form_name, form_json FROM form_settings WHERE project = '$sourceProject'");

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $formData = json_decode($row['form_json'], true);
                $tables[] = [
                    'name' => $row['form_name'],
                    'display_name' => $formData['title'] ?? $row['form_name'],
                    'description' => $formData['description'] ?? ''
                ];
            }
        }

        $response->json($tables);
    }

    /**
     * POST /v2/forms/import
     * Import table from another project
     */
    public function importTable(Request $request, Response $response): void
    {
        $sourceProject = escape_string($request->input('source_project'));
        $sourceTable = escape_string($request->input('source_table'));
        $targetProject = escape_string($request->input('target_project'));
        $newTableName = escape_string($request->input('new_table_name'));

        if (!$sourceProject || !$sourceTable || !$targetProject || !$newTableName) {
            $response->error('source_project, source_table, target_project and new_table_name are required', 400);
            return;
        }

        try {
            mysqli_autocommit($GLOBALS['con'], false);
            $result = query("SELECT form_json FROM form_settings WHERE project = '$sourceProject' AND form_name = '$sourceTable'");

            if (!$result || mysqli_num_rows($result) == 0) {
                throw new Exception("Source table configuration not found");
            }

            $row = mysqli_fetch_assoc($result);
            $formJSON = $row['form_json'];
            $formData = json_decode($formJSON, true);

            if (!$formData) {
                throw new Exception("Invalid form configuration");
            }

            $formData['title'] = str_replace('project_', '', $newTableName);
            $updatedFormJSON = json_encode($formData);

            if (!query("INSERT INTO form_settings (form_name, form_json, project) VALUES ('$newTableName', '$updatedFormJSON', '$targetProject')")) {
                throw new Exception("Failed to create form configuration");
            }

            $title = self::sanitizeName($formData['title']);
            $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($targetProject . "_" . $title));

            $sql = "CREATE TABLE $tableName (
                id INT AUTO_INCREMENT PRIMARY KEY";

            foreach ($formData['inputs'] as $field) {
                $name = self::sanitizeName($field['name']);
                $type = self::mapFieldType($field['type']);
                $sql .= ", $name $type";
            }
            $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $sql .= ");";

            if (!query($sql)) {
                throw new Exception("Failed to create table structure");
            }

            $sourceTableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($sourceProject . "_" . $sourceTable));
            $checkTable = query("SHOW TABLES LIKE '$sourceTableName'");

            if ($checkTable && mysqli_num_rows($checkTable) > 0) {
                $columns = [];
                $columnsResult = query("SHOW COLUMNS FROM $sourceTableName");
                while ($col = mysqli_fetch_assoc($columnsResult)) {
                    if ($col['Field'] != 'id' && $col['Field'] != 'created_at') {
                        $columns[] = $col['Field'];
                    }
                }

                if (!empty($columns)) {
                    $columnsList = implode(', ', $columns);
                    if (!query("INSERT INTO $tableName ($columnsList) SELECT $columnsList FROM $sourceTableName")) {
                        throw new Exception("Failed to copy table data");
                    }
                }
            }

            mysqli_commit($GLOBALS['con']);
            $response->json(['success' => true, 'message' => 'Table imported successfully']);
        } catch (Exception $e) {
            mysqli_rollback($GLOBALS['con']);
            $response->json(['success' => false, 'message' => $e->getMessage()]);
        }

        mysqli_autocommit($GLOBALS['con'], true);
    }

    public function getInfo(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $query = query("SELECT * FROM form_settings WHERE form_name='$formName' AND project='$project'");
        if (mysqli_num_rows($query) > 0) {
            $form = fetch_assoc($query);
            $response->json([
                'id' => $form['form_id'],
                'title' => json_decode($form['form_json'], true)['title'] ?? '',
                'createdOn' => $form['created_at'],
            ]);
        } else {
            $response->json([]);
        }
    }

    /**
     * GET /v2/forms/export/csv
     * Export form data as CSV
     */
    public function exportCSV(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        $tableCheck = query("SHOW TABLES LIKE '$tableName'");
        if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
            $response->error('Form table not found', 404);
            return;
        }

        $data = query("SELECT * FROM `$tableName`");

        if (!$data) {
            $response->error('Error fetching data', 500);
            return;
        }

        $fields = query("SHOW COLUMNS FROM `$tableName`");
        $columns = [];
        while ($field = mysqli_fetch_assoc($fields)) {
            $columns[] = $field['Field'];
        }

        ob_start();
        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $columns);

        while ($row = mysqli_fetch_assoc($data)) {
            $rowData = [];
            foreach ($columns as $column) {
                $rowData[] = $row[$column];
            }
            fputcsv($output, $rowData);
        }

        fclose($output);
        $content = ob_get_clean();

        $response->download($content, $formName . '_export_' . date('Y-m-d') . '.csv', 'text/csv; charset=utf-8');
    }

    /**
     * GET /v2/forms/export/excel
     * Export form data as Excel (XLSX)
     */
    public function exportExcel(Request $request, Response $response): void
    {
        $formName = escape_string($request->input('form_name'));
        $project = escape_string($request->input('project'));

        if (!$formName || !$project) {
            $response->error('form_name and project are required', 400);
            return;
        }

        $tableName = self::buildTableName($project, $formName);

        $tableCheck = query("SHOW TABLES LIKE '$tableName'");
        if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
            $response->error('Form table not found', 404);
            return;
        }

        $data = query("SELECT * FROM `$tableName`");

        if (!$data) {
            $response->error('Error fetching data', 500);
            return;
        }

        $fields = query("SHOW COLUMNS FROM `$tableName`");
        $columns = [];
        while ($field = mysqli_fetch_assoc($fields)) {
            $columns[] = $field['Field'];
        }

        $rows = [];
        while ($row = mysqli_fetch_assoc($data)) {
            $rowData = [];
            foreach ($columns as $column) {
                $rowData[] = $row[$column];
            }
            $rows[] = $rowData;
        }

        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';

            try {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $col = 'A';
                foreach ($columns as $column) {
                    $sheet->setCellValue($col . '1', $column);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($rows as $row) {
                    $col = 'A';
                    foreach ($row as $cell) {
                        $sheet->setCellValue($col . $rowNum, $cell);
                        $col++;
                    }
                    $rowNum++;
                }

                foreach (range('A', $col) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

                while (ob_get_level()) {
                    ob_end_clean();
                }

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $formName . '_export_' . date('Y-m-d') . '.xlsx"');
                header('Cache-Control: max-age=0');
                header('Pragma: public');

                $writer->save('php://output');
                exit;
            } catch (\Exception $e) {
                error_log('Excel export error: ' . $e->getMessage());
            }
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $formName . '_export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $columns);

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
