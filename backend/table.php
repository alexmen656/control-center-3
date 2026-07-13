<?php
require_once "head.php";
require_once "triggers.php";

function mapFieldType($type)
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
        case 'image':
            return 'VARCHAR(255)';
        default:
            return 'VARCHAR(255)';
    }
}

if (isset($_POST['create_table']) && isset($_POST['table']) && isset($_POST['name']) && isset($_POST['project'])) {
    $tableJSON = $_POST['table'];
    $tableName = escape_string($_POST['name']);
    $project = escape_string($_POST['project']);
    
    $projectID = getProjectID($project);
    $tablesSectionId = null;
    if ($projectID) {
        $tablesSection = query("SELECT id FROM project_sidebar_sections WHERE projectID='$projectID' AND slug='tables' LIMIT 1");
        if (mysqli_num_rows($tablesSection) > 0) {
            $tablesSectionId = fetch_assoc($tablesSection)['id'];
        }
    }
    
    if (query("INSERT INTO table_settings (table_name, table_json, project, section_id, icon) VALUES ('$tableName', '$tableJSON', '$project', " . ($tablesSectionId ? "'$tablesSectionId'" : "NULL") . ", 'list-outline')")) {

        $data = json_decode($tableJSON, true);

        if ($data && isset($data['title'], $data['inputs'])) {
            $title = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($data['title']));
            $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($project . "_" . $title));
            $fields = $data['inputs'];
            $sql = "CREATE TABLE $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY";

            foreach ($fields as $field) {
                $name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], $field['name']);
                $type = mapFieldType($field['type']);
                $sql .= ", $name $type";
            }
            $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $sql .= ");";

            if (query($sql)) {
                echo $tableName . " Created Successfully!!!";
            }
        } else {
            echo "Ungültiges JSON-Format.";
        }

    }
} elseif (isset($_POST['get_table']) && isset($_POST['project']) && isset($_POST['table'])) {
    $table_name = escape_string($_POST['table']);
    $project = escape_string($_POST['project']);
    $query = query("SELECT * FROM table_settings WHERE table_name='$table_name' AND project='$project'");
    if (mysqli_num_rows($query) > 0) {
        $form = fetch_assoc($query);
        $json['id'] = $form['table_id'];
        $json['table'] = json_decode($form['table_json'], true);
        $json['createdOn'] = $form['created_at'];
        echo echoJson(($json));
    }
} elseif (isset($_POST['get_table_data']) && isset($_POST['project']) && isset($_POST['table'])) {
    $table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['table'])));
    $project_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['project'])));
    $table_name = $project_name . "_" . $table_name;

    $data = query("SELECT * FROM `$table_name` LIMIT 100");
    $json = array();

    if (mysqli_num_rows($data) > 0) {
        $field_names_result = query("SHOW COLUMNS FROM `$table_name`");
        $field_names = array();

        while ($row = mysqli_fetch_assoc($field_names_result)) {
            $field_names[] = $row['Field'];
        }

        while ($row = mysqli_fetch_assoc($data)) {
            $formattedRow = array();

            foreach ($field_names as $field) {
                    $formattedRow[$field] = $row[$field];
            }

            $json[] = $formattedRow;
        }

        echo echoJson($json);
    } else {
        echo json_encode(array());
    }
} elseif (isset($_POST['get_tables']) && isset($_POST['project'])) {
    $json = [];
    $project = escape_string($_POST['project']);
    $forms = query("SELECT * FROM table_settings WHERE project='$project'");
    $i = 0;
    foreach ($forms as $form) {
        $json[$i]['id'] = $form['table_id'];
        $json[$i]['table'] = json_decode($form['table_json'], true);
        $json[$i]['createdOn'] = $form['created_at'];
        $i++;
    }

    echo echoJson($json);

} elseif (isset($_POST['submit_table']) && isset($_POST['table']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $form = json_decode($_POST['table'], true);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    if ($form) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));

        $columns = array();
        $values = array();

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], escape_string($fieldName));
            $fieldValue = escape_string($fieldValue);
            $columns[] = $fieldName;
            $values[] = "'$fieldValue'";
        }

        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);

        $sql = "INSERT INTO $tableName ($columnsStr) VALUES ($valuesStr)";
        if (query($sql)) {
            $newId = mysqli_insert_id($GLOBALS['con']);
            
            $triggerSystem = new TableTriggers();
            $triggerData = $form;
            $triggerData['id'] = $newId;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $table_name, 'insert', $triggerData);
            
            echo "Form data submitted successfully!";
        } else {
            echo "Error submitting form data.";
        }
    } else {
        echo "Invalid form data format.";
    }
} elseif (isset($_POST['delete_entry']) && isset($_POST['entry_id']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $id = escape_string($_POST['entry_id']);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));

    $sql = "DELETE FROM $tableName WHERE id='$id'";
    if (query($sql)) {
        $triggerSystem = new TableTriggers();
        $triggerData = ['id' => $id, 'table' => $tableName];
        $triggerSystem->executeTriggers($project, $table_name, 'delete', $triggerData);
        
        echo "Entry deleted successfully!";
    } else {
        echo "Error deleting entry.";
    }
} elseif (
    isset($_POST['update_entry']) &&
    isset($_POST['entry_id']) &&
    isset($_POST['table']) &&
    isset($_POST['table_name']) &&
    isset($_POST['project'])
) {
    $id = escape_string($_POST['entry_id']);
    $form = json_decode($_POST['table'], true);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);

    if ($form) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));
        $updates = array();

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], escape_string($fieldName));
            $fieldValue = escape_string($fieldValue);
            $updates[] = "$fieldName = '$fieldValue'";
        }

        $updatesStr = implode(', ', $updates);

        $sql = "UPDATE $tableName SET $updatesStr WHERE id='$id'";

        if (query($sql)) {
            $triggerSystem = new TableTriggers();
            $triggerData = $form;
            $triggerData['id'] = $id;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $table_name, 'update', $triggerData);
            
            echo "Entry updated successfully!";
        } else {
            echo "Error updating entry!";
        }
    }
} elseif (isset($_POST['check_table_exists']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    
    $query = query("SELECT * FROM table_settings WHERE table_name='$table_name' AND project='$project'");
    $exists = mysqli_num_rows($query) > 0;
    
    echo json_encode(['exists' => $exists]);
} elseif (isset($_POST['rename_table']) && isset($_POST['old_table_name']) && isset($_POST['new_table_name']) && isset($_POST['project'])) {
    $old_table_name = escape_string($_POST['old_table_name']);
    $new_table_name = escape_string($_POST['new_table_name']);
    $project = escape_string($_POST['project']);
    
    if (!preg_match('/^[a-zA-Z0-9-_]+$/', $new_table_name)) {
        echo json_encode(['success' => false, 'error' => 'Ungültiger Formname. Verwenden Sie nur Buchstaben, Zahlen, Bindestriche und Unterstriche.']);
        exit;
    }
    
    $check_query = query("SELECT * FROM table_settings WHERE table_name='$new_table_name' AND project='$project'");
    if (mysqli_num_rows($check_query) > 0) {
        echo json_encode(['success' => false, 'error' => 'Eine Form mit diesem Namen existiert bereits.']);
        exit;
    }
    
    $old_form_query = query("SELECT * FROM table_settings WHERE table_name='$old_table_name' AND project='$project'");
    if (mysqli_num_rows($old_form_query) == 0) {
        echo json_encode(['success' => false, 'error' => 'Ursprüngliche Form nicht gefunden.']);
        exit;
    }
    
    $old_table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($old_table_name));
    $new_table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($new_table_name));
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        $update_form_query = "UPDATE table_settings SET table_name='$new_table_name' WHERE table_name='$old_table_name' AND project='$project'";
        if (!query($update_form_query)) {
            throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
        }
        
        $table_exists_query = query("SHOW TABLES LIKE '$old_table_name'");
        if (mysqli_num_rows($table_exists_query) > 0) {
            $rename_table_query = "RENAME TABLE `$old_table_name` TO `$new_table_name`";
            if (!query($rename_table_query)) {
                throw new Exception('Fehler beim Umbenennen der Datentabelle');
            }
        }
        
        if (class_exists('TableTriggers')) {
            $triggerSystem = new TableTriggers();
            $triggerSystem->renameTableTriggers($project, $old_table_name, $new_table_name);
        }
        
        mysqli_commit($GLOBALS['con']);
        echo json_encode(['success' => true, 'message' => 'Form erfolgreich umbenannt']);
        
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
} elseif (isset($_POST['update_table_structure']) && isset($_POST['table']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $tableJSON = $_POST['table'];
    $tableName = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    
    $formData = json_decode($tableJSON, true);
    if (!$formData || !isset($formData['title'], $formData['inputs'])) {
        echo json_encode(['success' => false, 'error' => 'Ungültiges JSON-Format']);
        exit;
    }
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        $update_form_query = "UPDATE table_settings SET table_json='$tableJSON' WHERE table_name='$tableName' AND project='$project'";
        if (!query($update_form_query)) {
            throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
        }
        
        $tableName = createTableName($project . "_" . $tableName);
        
        $table_exists_query = query("SHOW TABLES LIKE '$tableName'");
        if (mysqli_num_rows($table_exists_query) > 0) {
            $existing_columns_result = query("SHOW COLUMNS FROM `$tableName`");
            $existing_columns = [];
            while ($column = fetch_assoc($existing_columns_result)) {
                $existing_columns[] = $column['Field'];
            }
            
            foreach ($formData['inputs'] as $field) {
                $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], $field['name']);
                
                if (!in_array($fieldName, $existing_columns)) {
                    $fieldType = mapFieldType($field['type']);
                    $alter_sql = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldType";
                    if (!query($alter_sql)) {
                        throw new Exception("Fehler beim Hinzufügen der Spalte: $fieldName");
                    }
                }
            }
        }
        
        mysqli_commit($GLOBALS['con']);
        echo json_encode(['success' => true, 'message' => 'Form-Struktur erfolgreich aktualisiert']);
        
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
} elseif (isset($_POST['get_table_schema']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    
    $query = query("SELECT table_json FROM table_settings WHERE table_name='$table_name' AND project='$project'");
    if (mysqli_num_rows($query) > 0) {
        $form = fetch_assoc($query);
        $formData = json_decode($form['table_json'], true);
        
        if ($formData && isset($formData['inputs'])) {
            $schema = [];
            foreach ($formData['inputs'] as $input) {
                $schema[] = [
                    'name' => str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], $input['name']),
                    'label' => $input['label'] ?? $input['name'],
                    'type' => $input['type'] ?? 'text',
                    'placeholder' => $input['placeholder'] ?? '',
                    'options' => $input['options'] ?? []
                ];
            }
            echo json_encode(['success' => true, 'schema' => $schema]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid form schema']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Form not found']);
    }
} elseif (isset($_POST['get_entry']) && isset($_POST['entry_id']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $entry_id = escape_string($_POST['entry_id']);
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));
    
    $query = query("SELECT * FROM `$tableName` WHERE id='$entry_id'");
    if (mysqli_num_rows($query) > 0) {
        $entry = fetch_assoc($query);
        echo json_encode(['success' => true, 'entry' => $entry]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Entry not found']);
    }
} elseif (isset($_POST['get_all_tables']) && isset($_POST['project'])) {
    $project = escape_string($_POST['project']);
    
    $forms_query = query("SELECT table_name, table_json, created_at FROM table_settings WHERE project='$project' ORDER BY created_at DESC");
    $tables = [];
    
    while ($form = fetch_assoc($forms_query)) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form['table_name']));
        
        $table_exists_query = query("SHOW TABLES LIKE '$tableName'");
        $exists = mysqli_num_rows($table_exists_query) > 0;
        $row_count = 0;
        
        if ($exists) {
            $count_query = query("SELECT COUNT(*) as count FROM `$tableName`");
            if ($count_query) {
                $count_result = fetch_assoc($count_query);
                $row_count = $count_result['count'];
            }
        }
        
        $formData = json_decode($form['table_json'], true);
        $field_count = isset($formData['inputs']) ? count($formData['inputs']) : 0;
        
        $tables[] = [
            'name' => $form['table_name'],
            'table_name' => $tableName,
            'exists' => $exists,
            'row_count' => $row_count,
            'field_count' => $field_count,
            'created_at' => $form['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'tables' => $tables]);
} elseif (isset($_POST['drop_table']) && isset($_POST['table_name']) && isset($_POST['project'])) {
    $table_name = escape_string($_POST['table_name']);
    $project = escape_string($_POST['project']);
    
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($table_name));
    
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        $drop_query = "DROP TABLE IF EXISTS `$tableName`";
        if (!query($drop_query)) {
            throw new Exception('Fehler beim Löschen der Tabelle');
        }
        
        $delete_form_query = "DELETE FROM table_settings WHERE table_name='$table_name' AND project='$project'";
        if (!query($delete_form_query)) {
            throw new Exception('Fehler beim Löschen der Form-Einstellungen');
        }
        
        mysqli_commit($GLOBALS['con']);
        echo json_encode(['success' => true, 'message' => 'Form und Tabelle erfolgreich gelöscht']);
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
}

if (isset($_POST['get_tables_from_project']) && isset($_POST['source_project'])) {
    $sourceProject = escape_string($_POST['source_project']);
    $excludeProject = escape_string($_POST['exclude_project']);
    
    $tables = [];
    $result = query("SELECT table_name, table_json FROM table_settings WHERE project = '$sourceProject'");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $formData = json_decode($row['table_json'], true);
            $tables[] = [
                'name' => $row['table_name'],
                'display_name' => $formData['title'] ?? $row['table_name'],
                'description' => $formData['description'] ?? ''
            ];
        }
    }
    
    echo json_encode($tables);
    exit;
}

if (isset($_POST['import_table']) && isset($_POST['source_project']) && isset($_POST['source_table']) && isset($_POST['target_project']) && isset($_POST['new_table_name'])) {
    $sourceProject = escape_string($_POST['source_project']);
    $sourceTable = escape_string($_POST['source_table']);
    $targetProject = escape_string($_POST['target_project']);
    $newTableName = escape_string($_POST['new_table_name']);
    
    try {
        mysqli_autocommit($GLOBALS['con'], false);
        $result = query("SELECT table_json FROM table_settings WHERE project = '$sourceProject' AND table_name = '$sourceTable'");
        
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Source table configuration not found");
        }
        
        $row = mysqli_fetch_assoc($result);
        $tableJSON = $row['table_json'];
        $formData = json_decode($tableJSON, true);
        
        if (!$formData) {
            throw new Exception("Invalid form configuration");
        }
        
        $formData['title'] = str_replace('project_', '', $newTableName);
        $updatedTableJSON = json_encode($formData);
        
        if (!query("INSERT INTO table_settings (table_name, table_json, project) VALUES ('$newTableName', '$updatedTableJSON', '$targetProject')")) {
            throw new Exception("Failed to create form configuration");
        }
        
        $title = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formData['title']));
        $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($targetProject . "_" . $title));
        
        $sql = "CREATE TABLE $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY";
        
        foreach ($formData['inputs'] as $field) {
            $name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], $field['name']);
            $type = mapFieldType($field['type']);
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
        echo json_encode(['success' => true, 'message' => 'Table imported successfully']);
    } catch (Exception $e) {
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    mysqli_autocommit($GLOBALS['con'], true);
    exit;
}
?>