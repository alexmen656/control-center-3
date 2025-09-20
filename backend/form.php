<?php
include "triggers.php";

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
        default:
            return 'VARCHAR(255)';
    }
}

if (isset($_POST['create_form']) && isset($_POST['form']) && isset($_POST['name']) && isset($_POST['project'])) {
    $formJSON = $_POST['form']; //escape_string();
    $formName = escape_string($_POST['name']);
    $project = escape_string($_POST['project']);
    if (query("INSERT INTO form_settings (form_name, form_json, project) VALUES ('$formName', '$formJSON', '$project')")) {

        $data = json_decode($formJSON, true);

        if ($data && isset($data['title'], $data['inputs'])) {
            $title = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($data['title']));
            $tableName = str_replace(["-", " ", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "_", "a", "a", "u", "u", "o", "o"], strtolower($project . "_" . $title));
            $fields = $data['inputs'];
            $sql = "CREATE TABLE $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY";

            foreach ($fields as $field) {
                $name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], strtolower($field['name']));
                $type = mapFieldType($field['type']);
                $sql .= ", $name $type";
            }
            $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $sql .= ");";

            //echo $sql;
            if (query($sql)) {
                echo $formName . " Created Successfully!!!";
            }
        } else {
            echo "Ungültiges JSON-Format.";
        }

    }
} elseif (isset($_POST['get_form']) && isset($_POST['project']) && isset($_POST['form'])) {
    $form_name = escape_string($_POST['form']);
    $project = escape_string($_POST['project']);
    $query = query("SELECT * FROM form_settings WHERE form_name='$form_name' AND project='$project'");
    if (mysqli_num_rows($query) > 0) {
        //echo 1;
        $form = fetch_assoc($query);
        //print_r($form);
        $json['id'] = $form['form_id'];
        $json['form'] = json_decode($form['form_json'], true);
        $json['createdOn'] = $form['created_at'];
        echo echoJson(($json));
    }
} elseif (isset($_POST['get_form_data']) && isset($_POST['project']) && isset($_POST['form'])) {
    $form_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['form'])));
    $project_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower(escape_string($_POST['project'])));
    $table_name = $project_name . "_" . $form_name;
    //echo $table_name;

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
             //   if ($field != 'created_at') {
                    $formattedRow[$field] = $row[$field];
               // }
            }

            $json[] = $formattedRow;
        }

        echo echoJson($json);
    } else {
        echo json_encode(array());
    }
} elseif (isset($_POST['get_forms']) && isset($_POST['project'])) {
    $json = [];
    // $form_name = escape_string($_POST['form']);
    $project = escape_string($_POST['project']);
    $forms = query("SELECT * FROM form_settings WHERE project='$project'");
    $i = 0;
    foreach ($forms as $form) {
        $json[$i]['id'] = $form['form_id'];
        $json[$i]['form'] = json_decode($form['form_json'], true);
        $json[$i]['createdOn'] = $form['created_at'];
        $i++;
    }

    echo echoJson($json);

} elseif (isset($_POST['submit_form']) && isset($_POST['form']) && isset($_POST['form_name']) && isset($_POST['project'])) {
    $form = json_decode($_POST['form'], true);
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    if ($form) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));

        $columns = array();
        $values = array();

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], strtolower(escape_string($fieldName)));
            $fieldValue = escape_string($fieldValue);
            $columns[] = $fieldName;
            $values[] = "'$fieldValue'";
        }

        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);

        $sql = "INSERT INTO $tableName ($columnsStr) VALUES ($valuesStr)";
        //echo $sql;
        if (query($sql)) {
            $newId = mysqli_insert_id($GLOBALS['con']);
            
            // Trigger execution für INSERT
            $triggerSystem = new FormTriggers();
            $triggerData = $form;
            $triggerData['id'] = $newId;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $form_name, 'insert', $triggerData);
            
            echo "Form data submitted successfully!";
        } else {
            echo "Error submitting form data.";
        }
    } else {
        echo "Invalid form data format.";
    }
} elseif (isset($_POST['delete_entry']) && isset($_POST['entry_id']) && isset($_POST['form_name']) && isset($_POST['project'])) {
    $id = escape_string($_POST['entry_id']);
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));

    $sql = "DELETE FROM $tableName WHERE id='$id'";
    if (query($sql)) {
        // Trigger execution für DELETE
        $triggerSystem = new FormTriggers();
        $triggerData = ['id' => $id, 'table' => $tableName];
        $triggerSystem->executeTriggers($project, $form_name, 'delete', $triggerData);
        
        echo "Entry deleted successfully!";
    } else {
        echo "Error deleting entry.";
    }
} elseif (
    isset($_POST['update_entry']) &&
    isset($_POST['entry_id']) &&
    isset($_POST['form']) &&
    isset($_POST['form_name']) &&
    isset($_POST['project'])
) {
    $id = escape_string($_POST['entry_id']);
    $form = json_decode($_POST['form'], true);
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);

    if ($form) {
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));
        $updates = array();

        foreach ($form as $fieldName => $fieldValue) {
            $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], strtolower(escape_string($fieldName)));
            $fieldValue = escape_string($fieldValue);
            $updates[] = "$fieldName = '$fieldValue'";
        }

        $updatesStr = implode(', ', $updates);

        $sql = "UPDATE $tableName SET $updatesStr WHERE id='$id'";

        if (query($sql)) {
            // Trigger execution für UPDATE
            $triggerSystem = new FormTriggers();
            $triggerData = $form;
            $triggerData['id'] = $id;
            $triggerData['table'] = $tableName;
            $triggerSystem->executeTriggers($project, $form_name, 'update', $triggerData);
            
            echo "Entry updated successfully!";
        } else {
            echo "Error updating entry!";
        }
    }
} elseif (isset($_POST['check_form_exists']) && isset($_POST['form_name']) && isset($_POST['project'])) {
    $form_name = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    
    $query = query("SELECT * FROM form_settings WHERE form_name='$form_name' AND project='$project'");
    $exists = mysqli_num_rows($query) > 0;
    
    echo json_encode(['exists' => $exists]);
} elseif (isset($_POST['rename_form']) && isset($_POST['old_form_name']) && isset($_POST['new_form_name']) && isset($_POST['project'])) {
    $old_form_name = escape_string($_POST['old_form_name']);
    $new_form_name = escape_string($_POST['new_form_name']);
    $project = escape_string($_POST['project']);
    
    // Validate new form name
    if (!preg_match('/^[a-zA-Z0-9-_]+$/', $new_form_name)) {
        echo json_encode(['success' => false, 'error' => 'Ungültiger Formname. Verwenden Sie nur Buchstaben, Zahlen, Bindestriche und Unterstriche.']);
        exit;
    }
    
    // Check if new form name already exists
    $check_query = query("SELECT * FROM form_settings WHERE form_name='$new_form_name' AND project='$project'");
    if (mysqli_num_rows($check_query) > 0) {
        echo json_encode(['success' => false, 'error' => 'Eine Form mit diesem Namen existiert bereits.']);
        exit;
    }
    
    // Check if old form exists
    $old_form_query = query("SELECT * FROM form_settings WHERE form_name='$old_form_name' AND project='$project'");
    if (mysqli_num_rows($old_form_query) == 0) {
        echo json_encode(['success' => false, 'error' => 'Ursprüngliche Form nicht gefunden.']);
        exit;
    }
    
    // Generate old and new table names
    $old_table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($old_form_name));
    $new_table_name = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($new_form_name));
    
    // Start transaction
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        // Update form_settings table
        $update_form_query = "UPDATE form_settings SET form_name='$new_form_name' WHERE form_name='$old_form_name' AND project='$project'";
        if (!query($update_form_query)) {
            throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
        }
        
        // Check if data table exists and rename it
        $table_exists_query = query("SHOW TABLES LIKE '$old_table_name'");
        if (mysqli_num_rows($table_exists_query) > 0) {
            $rename_table_query = "RENAME TABLE `$old_table_name` TO `$new_table_name`";
            if (!query($rename_table_query)) {
                throw new Exception('Fehler beim Umbenennen der Datentabelle');
            }
        }
        
        // Update triggers if they exist
        if (class_exists('FormTriggers')) {
            $triggerSystem = new FormTriggers();
            $triggerSystem->renameFormTriggers($project, $old_form_name, $new_form_name);
        }
        
        // Update project_tools table for sidebar display
        $project_data = fetch_assoc(query("SELECT projectID FROM projects WHERE link='$project'"));
        if ($project_data) {
            $project_id = $project_data['projectID'];
            $update_tools_query = "UPDATE project_tools SET name='$new_form_name', link='" . strtolower(str_replace([' ', 'ä', 'ö', 'ü', 'ß'], ['_', 'a', 'o', 'u', 'ss'], $new_form_name)) . "' WHERE name='$old_form_name' AND projectID='$project_id'";
            query($update_tools_query);
        }
        
        // Commit transaction
        mysqli_commit($GLOBALS['con']);
        echo json_encode(['success' => true, 'message' => 'Form erfolgreich umbenannt']);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    // Re-enable autocommit
    mysqli_autocommit($GLOBALS['con'], true);
} elseif (isset($_POST['update_form_structure']) && isset($_POST['form']) && isset($_POST['form_name']) && isset($_POST['project'])) {
    $formJSON = $_POST['form'];
    $formName = escape_string($_POST['form_name']);
    $project = escape_string($_POST['project']);
    
    // Validate JSON
    $formData = json_decode($formJSON, true);
    if (!$formData || !isset($formData['title'], $formData['inputs'])) {
        echo json_encode(['success' => false, 'error' => 'Ungültiges JSON-Format']);
        exit;
    }
    
    // Start transaction
    mysqli_autocommit($GLOBALS['con'], false);
    
    try {
        // Update form_settings table
        $update_form_query = "UPDATE form_settings SET form_json='$formJSON' WHERE form_name='$formName' AND project='$project'";
        if (!query($update_form_query)) {
            throw new Exception('Fehler beim Aktualisieren der Form-Einstellungen');
        }
        
        // Get current table structure
        $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($formName));
        
        // Check if table exists
        $table_exists_query = query("SHOW TABLES LIKE '$tableName'");
        if (mysqli_num_rows($table_exists_query) > 0) {
            // Get existing columns
            $existing_columns_result = query("SHOW COLUMNS FROM `$tableName`");
            $existing_columns = [];
            while ($column = fetch_assoc($existing_columns_result)) {
                $existing_columns[] = $column['Field'];
            }
            
            // Add new columns for new fields
            foreach ($formData['inputs'] as $field) {
                $fieldName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö", "(", ")", " ", ".", ",", "!", "?", "@", "#", "$", "%", "^", "&", "*", "+", "=", "[", "]", "{", "}", "|", "\\", ":", ";", "\"", "'", "<", ">", "/"], ["_", "a", "a", "u", "u", "o", "o", "", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""], strtolower($field['name']));
                
                // Check if column already exists
                if (!in_array($fieldName, $existing_columns)) {
                    $fieldType = mapFieldType($field['type']);
                    $alter_sql = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldType";
                    if (!query($alter_sql)) {
                        throw new Exception("Fehler beim Hinzufügen der Spalte: $fieldName");
                    }
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($GLOBALS['con']);
        echo json_encode(['success' => true, 'message' => 'Form-Struktur erfolgreich aktualisiert']);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($GLOBALS['con']);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    // Re-enable autocommit
    mysqli_autocommit($GLOBALS['con'], true);
}

?>