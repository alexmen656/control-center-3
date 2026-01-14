<?php
require_once "head.php";
$json = [];

if (isset($_POST['getTables']) && $_POST['getTables']) {
    $tables = query("SHOW TABLES");
    $i = 0;
    foreach ($tables as $t) {
        $json[$i][0] = $t['Tables_in_alex01d01'];
        $i++;
    }
} elseif (isset($_POST['getDataById']) && isset($_POST['id']) && isset($_POST['project']) && isset($_POST['form'])) {
    $project = escape_string($_POST['project']);
    $form_name = escape_string($_POST['form']);
    $id = escape_string($_POST['id']);

    $tableName = createTableName($project . "_" . $form_name);
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
            echo "Keine Daten gefunden.";
        }
    } else {
        echo "Keine Spalten gefunden.";
    }

} elseif (isset($_POST['getTableByName']) && $_POST['getTableByName']) {
    $i = 0;
    $tbName = strtolower(escape_string($_POST['getTableByName']));
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
} elseif (isset($_POST['load_more']) && isset($_POST['current_limit']) && isset($_POST['table'])) {
    $tbName = escape_string($_POST['table']);
    $current_limit = escape_string($_POST['current_limit']);
    $new_limit = ($current_limit + 1) * 30;
    $offset = $current_limit * 30;
    $json['load_more_btn'] = false;

    if (mysqli_num_rows(query("SELECT * FROM `$tbName`")) > $new_limit) {
        $json['load_more_btn'] = true;
    }

    $i = 0;
    $columns = query("SHOW COLUMNS FROM `$tbName`");

    if ($columns) {
        while ($row = fetch_assoc($columns)) {
            $columnsArray[] = $row['Field'];
            $i++;
        }
    } else {
        $json['error'] = "Table " . $tbName . " don't exists";
    }

    if ($data = query("SELECT * FROM `$tbName` ORDER BY id LIMIT 30 OFFSET " . $offset)) {

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
} elseif (isset($_POST['updateField']) && isset($_POST['tableName']) && isset($_POST['fieldName']) && isset($_POST['newValue']) && isset($_POST['rowIndex'])) {
    $tableName = escape_string($_POST['tableName']);
    $fieldName = escape_string($_POST['fieldName']);
    $newValue = escape_string($_POST['newValue']);
    $rowIndex = (int) $_POST['rowIndex'];

    $primaryKey = fetch_assoc(query("SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE (`TABLE_SCHEMA` = 'alex01d01') AND (`TABLE_NAME` = '$tableName') AND (`COLUMN_KEY` = 'PRI')"))["COLUMN_NAME"];
    $primaryKeyValue = fetch_assoc(query("SELECT `$primaryKey` FROM `$tableName` LIMIT $rowIndex, 1"))[$primaryKey];

    if (query("UPDATE `$tableName` SET `$fieldName` = '$newValue' WHERE `$primaryKey` = '$primaryKeyValue'")) {
        $json['success'] = true;
    } else {
        $json['error'] = "Failed to update the field.";
    }
}

echo echoJson($json);
?>