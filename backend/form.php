<?php
include "head.php";

function mapFieldType($type)
{
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

if (isset($_POST['create_form']) && isset($_POST['form']) && isset($_POST['name']) && isset($_POST['project'])) {
    $formJSON = $_POST['form']; //escape_string();
    $formName = escape_string($_POST['name']);
    $project = escape_string($_POST['project']);
    if (query("INSERT INTO form_settings (form_name, form_json, project) VALUES ('$formName', '$formJSON', '$project')")) {

        $data = json_decode($formJSON, true);

        if ($data && isset($data['title'], $data['inputs'])) {
            $title = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($data['title']));
            $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . $title;
            $fields = $data['inputs'];
            $sql = "CREATE TABLE $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY";

            foreach ($fields as $field) {
                $name = $field['name'];
                $type = mapFieldType($field['type']);
                $sql .= ", $name $type";
            }
            $sql .= ", created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $sql .= ");";

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
            $fieldName = escape_string($fieldName);
            $fieldValue = escape_string($fieldValue);
            $columns[] = $fieldName;
            $values[] = "'$fieldValue'";
        }

        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);

        $sql = "INSERT INTO $tableName ($columnsStr) VALUES ($valuesStr)";
        //echo $sql;
        if (query($sql)) {
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
            $fieldName = escape_string($fieldName);
            $fieldValue = escape_string($fieldValue);
            $updates[] = "$fieldName = '$fieldValue'";
        }

        $updatesStr = implode(', ', $updates);

        $sql = "UPDATE $tableName SET $updatesStr WHERE id='$id'";

        if (query($sql)) {
            echo "Entry updated successfully!";
        } else {
            echo "Error updating entry!";
        }
    }
}

?>