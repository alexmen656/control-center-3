<?php
function randomNumber()
{
    $rand = rand(100000, 999999);
    return $rand;
}

function echoJson($json)
{
    return json_encode($json, JSON_PRETTY_PRINT);
}

function getData($project, $form_name, $id)
{
    $tableName = str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($project)) . "_" . str_replace(["-", "ä", "Ä", "ü", "Ü", "ö", "Ö"], ["_", "a", "a", "u", "u", "o", "o"], strtolower($form_name));

    $columns_query = "SHOW COLUMNS FROM $tableName";
    $columns_result = query($columns_query);

    if ($columns_result->num_rows > 0) {
        $columns = [];
        while ($column_row = $columns_result->fetch_assoc()) {
            $columns[] = $column_row["Field"];
        }

        $data_result = query("SELECT * FROM $tableName WHERE id = '$id'");

        if ($data_result->num_rows > 0) {
            $row = $data_result->fetch_assoc();
            foreach ($columns as $column) {
                $json[$column] = $row[$column];
            }
        } else {
            $json['error'] = "Keine Daten gefunden.";
        }

    } else {
        $json['error'] = "Keine Spalten gefunden.";
    }
    return $json;
}

function getTableByName($tbName)
{
    $i = 0;
    $columns = query("SHOW COLUMNS FROM `$tbName`");
    if ($columns) {
        while ($row = fetch_assoc($columns)) {
            $json['labels'][$i] = $row['Field'];
            $columnsArray[] = $row['Field'];
            $i++;
        }
    } else {
        $json['error'] = "Table " . $tbName . " don't exists";
    }

    $sql_limit = "";
    if (isset($_POST['limit'])) {
        $limit = escape_string($_POST['limit']);
        $sql_limit = " LIMIT " . $limit;
    }

    $primary_key = fetch_assoc(query("SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA` = 'alex01d01') AND (`TABLE_NAME` = '$tbName') AND (`COLUMN_KEY` = 'PRI')"))["COLUMN_NAME"];

    if (
        $data = query("SELECT * FROM `$tbName` ORDER BY $primary_key" . $sql_limit)
    ) {
        if (isset($_POST['limit'])) {
            $num_rows = mysqli_num_rows(query("SELECT * FROM `$tbName`"));
            if ($num_rows > $limit) {
                $json['load_more_btn'] = true;
            }
        }
        $gg = 0;
        $columns = query("SHOW COLUMNS FROM `$tbName`");

        while ($d = fetch_assoc($data)) {
            $tr = [];

            for ($i = 0; $i < count($columnsArray); $i++) {
                $tr[] = $d[$columnsArray[$i]];

            }
            $json['data'][$gg] = $tr;
            $gg++;
        }
    }
    return $json;
}


function get_form_data($form_name, $project_name, $table_name)
{
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

        return $json;
    } else {
        return json_encode(array());
    }
}

function delete_entry($id, $form_name, $project, $tableName)
{
    $sql = "DELETE FROM $tableName WHERE id='$id'";
    if (query($sql)) {
        $json['success'] = "Entry deleted successfully!";
    } else {
        $json['error'] = "Error deleting entry.";
    }
    return $json;
}

function update_entry($id, $form, $form_name, $project)
{
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
            $json['success'] = "Entry updated successfully!";
        } else {
            $json['error'] = "Error updating entry!";
        }
    }
    return $json;
}