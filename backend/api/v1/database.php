<?php

require_once 'helper/BaseAPI.php';

class DatabaseAPI extends BaseAPI
{
    private $allowedTables = [];
    private $projectName = '';
    private $columnCache = [];

    public function handleRequest()
    {
        try {
            $this->authenticate('3');
            $this->checkRateLimit();
            $this->loadAllowedTables();

            $method = $_SERVER['REQUEST_METHOD'];
            $action = $_GET['action'] ?? 'tables';

            if ($method === 'GET' && $action === 'tables') {
                $this->listTables();
            } elseif ($action === 'query') {
                $this->queryTable();
            } elseif ($action === 'count') {
                $this->countTable();
            } elseif ($method === 'POST' && $action === 'insert') {
                $this->insertRecord();
            } elseif ($method === 'PUT' && $action === 'update') {
                $this->updateRecord();
            } elseif ($method === 'DELETE' && $action === 'delete') {
                $this->deleteRecord();
            } else {
                $this->sendError('Unsupported action "' . $action . '" for method ' . $method, 400);
            }
        } catch (Throwable $e) {
            $this->sendError('Database API error: ' . $e->getMessage(), 500);
        }
    }

    private function normalizeName($value)
    {
        $from = ['-', ' ', 'ä', 'Ä', 'ü', 'Ü', 'ö', 'Ö'];
        $to = ['_', '_', 'a', 'a', 'u', 'u', 'o', 'o'];
        return str_replace($from, $to, strtolower($value));
    }

    private function loadAllowedTables()
    {
        $projectID = escape_string($this->projectID);

        $projectRow = query("SELECT link FROM projects WHERE projectID = '$projectID' LIMIT 1");
        if (!$projectRow || mysqli_num_rows($projectRow) === 0) {
            $this->sendError('Project not found', 404);
        }
        $projectLink = fetch_assoc($projectRow)['link'];
        $this->projectName = $this->normalizeName($projectLink);

        $link = escape_string($projectLink);
        $result = query("SELECT table_name FROM table_settings WHERE project = '$link'");
        if ($result) {
            while ($row = fetch_assoc($result)) {
                $tableSlug = $this->normalizeName($row['table_name']);
                $this->allowedTables[] = $this->projectName . '_' . $tableSlug;
            }
        }

        if (empty($this->allowedTables)) {
            $this->sendError('No database tables found for this project', 403);
        }
    }

    private function resolveTable($requested)
    {
        $requested = strtolower(trim((string) $requested));

        foreach ($this->allowedTables as $allowedTable) {
            $shortName = str_replace($this->projectName . '_', '', $allowedTable);
            if ($requested === $allowedTable || $requested === $shortName) {
                return $allowedTable;
            }
        }

        $this->sendError('Access denied to table: ' . $requested, 403);
    }

    private function getColumns($fullTableName)
    {
        if (isset($this->columnCache[$fullTableName])) {
            return $this->columnCache[$fullTableName];
        }

        $columns = [];
        $result = query("SHOW COLUMNS FROM `$fullTableName`");
        if ($result) {
            while ($row = fetch_assoc($result)) {
                $columns[] = $row['Field'];
            }
        }

        if (empty($columns)) {
            $this->sendError('Table does not exist: ' . $fullTableName, 404);
        }

        $this->columnCache[$fullTableName] = $columns;
        return $columns;
    }

    private function assertColumn($field, $columns)
    {
        if (!in_array($field, $columns, true)) {
            $this->sendError('Unknown column: ' . $field, 400);
        }
    }

    private function isList($value)
    {
        if (!is_array($value)) {
            return false;
        }
        if (empty($value)) {
            return true;
        }
        return array_keys($value) === range(0, count($value) - 1);
    }

    private function readBody()
    {
        $input = file_get_contents('php://input');
        if ($input === '' || $input === false) {
            return [];
        }
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }
        return is_array($data) ? $data : [];
    }

    private function buildWhere($where, $columns, &$params)
    {
        if (empty($where) || !is_array($where)) {
            return '';
        }

        $parts = [];
        foreach ($where as $field => $spec) {
            $this->assertColumn($field, $columns);
            $col = '`' . $field . '`';

            if (is_array($spec) && $this->isList($spec)) {
                if (empty($spec)) {
                    $parts[] = '1=0';
                    continue;
                }
                $placeholders = implode(',', array_fill(0, count($spec), '?'));
                $parts[] = "$col IN ($placeholders)";
                foreach ($spec as $value) {
                    $params[] = $value;
                }
            } elseif (is_array($spec)) {
                foreach ($spec as $op => $value) {
                    $parts[] = $this->operatorClause($col, $op, $value, $params);
                }
            } elseif ($spec === null) {
                $parts[] = "$col IS NULL";
            } else {
                $parts[] = "$col = ?";
                $params[] = $spec;
            }
        }

        return $parts ? (' WHERE ' . implode(' AND ', $parts)) : '';
    }

    private function operatorClause($col, $op, $value, &$params)
    {
        switch (strtolower((string) $op)) {
            case 'eq':
            case '=':
                $params[] = $value;
                return "$col = ?";
            case 'ne':
            case '!=':
            case '<>':
                $params[] = $value;
                return "$col <> ?";
            case 'gt':
            case '>':
                $params[] = $value;
                return "$col > ?";
            case 'gte':
            case '>=':
                $params[] = $value;
                return "$col >= ?";
            case 'lt':
            case '<':
                $params[] = $value;
                return "$col < ?";
            case 'lte':
            case '<=':
                $params[] = $value;
                return "$col <= ?";
            case 'like':
                $params[] = $value;
                return "$col LIKE ?";
            case 'nlike':
                $params[] = $value;
                return "$col NOT LIKE ?";
            case 'in':
                $value = is_array($value) ? $value : [$value];
                if (empty($value)) {
                    return '1=0';
                }
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                foreach ($value as $item) {
                    $params[] = $item;
                }
                return "$col IN ($placeholders)";
            case 'nin':
                $value = is_array($value) ? $value : [$value];
                if (empty($value)) {
                    return '1=1';
                }
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                foreach ($value as $item) {
                    $params[] = $item;
                }
                return "$col NOT IN ($placeholders)";
            case 'between':
                if (!is_array($value) || count($value) !== 2) {
                    $this->sendError('Operator "between" expects [min, max]', 400);
                }
                $params[] = $value[0];
                $params[] = $value[1];
                return "$col BETWEEN ? AND ?";
            case 'isnull':
                return $value ? "$col IS NULL" : "$col IS NOT NULL";
            default:
                $this->sendError('Unsupported operator: ' . $op, 400);
        }
    }

    private function buildSelect($options, $columns)
    {
        if (empty($options['select']) || !is_array($options['select'])) {
            return '*';
        }
        $parts = [];
        foreach ($options['select'] as $field) {
            $this->assertColumn($field, $columns);
            $parts[] = '`' . $field . '`';
        }
        return $parts ? implode(', ', $parts) : '*';
    }

    private function buildOrder($options, $columns)
    {
        if (empty($options['orderBy'])) {
            return '';
        }

        $items = is_array($options['orderBy']) ? $options['orderBy'] : [$options['orderBy']];
        $parts = [];
        foreach ($items as $item) {
            $field = trim((string) $item);
            $direction = 'ASC';
            if (strpos($field, ' ') !== false) {
                list($field, $dir) = explode(' ', $field, 2);
                if (strtoupper(trim($dir)) === 'DESC') {
                    $direction = 'DESC';
                }
            }
            $field = trim($field);
            $this->assertColumn($field, $columns);
            $parts[] = "`$field` $direction";
        }

        if (count($parts) === 1 && isset($options['direction']) && strtoupper($options['direction']) === 'DESC' && strpos($parts[0], ' DESC') === false) {
            $parts[0] = preg_replace('/ ASC$/', '', $parts[0]) . ' DESC';
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }

    private function buildLimit($options)
    {
        if (!isset($options['limit'])) {
            return '';
        }
        $sql = ' LIMIT ' . (int) $options['limit'];
        if (isset($options['offset'])) {
            $sql .= ' OFFSET ' . (int) $options['offset'];
        }
        return $sql;
    }

    private function bindParams($stmt, &$params)
    {
        if (empty($params)) {
            return;
        }
        $types = str_repeat('s', count($params));
        $args = [$stmt, $types];
        foreach ($params as $key => $value) {
            $args[] = &$params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $args);
    }

    private function fetchAll($sql, $params)
    {
        global $con;
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            $this->sendError('Query prepare failed: ' . mysqli_error($con), 500);
        }
        $this->bindParams($stmt, $params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    private function execWrite($sql, $params)
    {
        global $con;
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            $this->sendError('Statement prepare failed: ' . mysqli_error($con), 500);
        }
        $this->bindParams($stmt, $params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        $insertId = mysqli_insert_id($con);
        mysqli_stmt_close($stmt);
        return [$affected, $insertId];
    }

    private function listTables()
    {
        $tables = [];
        foreach ($this->allowedTables as $fullTableName) {
            $shortName = str_replace($this->projectName . '_', '', $fullTableName);
            $checkResult = query("SHOW TABLES LIKE '$fullTableName'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                $tables[] = [
                    'name' => $shortName,
                    'full_name' => $fullTableName,
                    'project' => $this->projectName
                ];
            }
        }

        $this->sendSuccess([
            'tables' => $tables,
            'project' => $this->projectName,
            'count' => count($tables)
        ]);
    }

    private function queryTable()
    {
        $data = $this->readBody();
        $requested = $data['table'] ?? ($_GET['table'] ?? '');
        if ($requested === '') {
            $this->sendError('Missing required field: table', 400);
        }

        $fullTableName = $this->resolveTable($requested);
        $columns = $this->getColumns($fullTableName);

        $where = $data['where'] ?? ($data['conditions'] ?? []);
        $options = $data['options'] ?? [];

        $params = [];
        $sql = 'SELECT ' . $this->buildSelect($options, $columns) . " FROM `$fullTableName`";
        $sql .= $this->buildWhere($where, $columns, $params);
        $sql .= $this->buildOrder($options, $columns);
        $sql .= $this->buildLimit($options);

        $records = $this->fetchAll($sql, $params);

        $this->sendSuccess([
            'records' => $records,
            'count' => count($records),
            'table' => $requested
        ]);
    }

    private function countTable()
    {
        $data = $this->readBody();
        $requested = $data['table'] ?? ($_GET['table'] ?? '');
        if ($requested === '') {
            $this->sendError('Missing required field: table', 400);
        }

        $fullTableName = $this->resolveTable($requested);
        $columns = $this->getColumns($fullTableName);

        $where = $data['where'] ?? ($data['conditions'] ?? []);
        $params = [];
        $sql = "SELECT COUNT(*) AS cnt FROM `$fullTableName`" . $this->buildWhere($where, $columns, $params);

        $rows = $this->fetchAll($sql, $params);
        $count = isset($rows[0]['cnt']) ? (int) $rows[0]['cnt'] : 0;

        $this->sendSuccess([
            'count' => $count,
            'table' => $requested
        ]);
    }

    private function insertRecord()
    {
        $data = $this->readBody();
        $this->validateRequired($data, ['table', 'data']);

        $fullTableName = $this->resolveTable($data['table']);
        $columns = $this->getColumns($fullTableName);

        $records = $data['data'];
        if (!is_array($records)) {
            $this->sendError('Field "data" must be an object or an array of objects', 400);
        }

        $isBatch = $this->isList($records);
        if (!$isBatch) {
            $records = [$records];
        }

        if (empty($records)) {
            $this->sendError('No records to insert', 400);
        }

        $fields = array_keys($records[0]);
        if (empty($fields)) {
            $this->sendError('Record has no fields', 400);
        }
        foreach ($fields as $field) {
            $this->assertColumn($field, $columns);
        }

        $columnSql = implode(', ', array_map(function ($f) {
            return '`' . $f . '`';
        }, $fields));

        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
        $valueGroups = [];
        $params = [];
        foreach ($records as $record) {
            $valueGroups[] = $rowPlaceholder;
            foreach ($fields as $field) {
                $params[] = array_key_exists($field, $record) ? $record[$field] : null;
            }
        }

        $sql = "INSERT INTO `$fullTableName` ($columnSql) VALUES " . implode(', ', $valueGroups);
        list($affected, $insertId) = $this->execWrite($sql, $params);

        $this->sendSuccess([
            'inserted' => $affected,
            'id' => $insertId,
            'batch' => $isBatch,
            'table' => $data['table']
        ], 'Record(s) inserted successfully');
    }

    private function updateRecord()
    {
        $data = $this->readBody();
        $this->validateRequired($data, ['table', 'data']);

        $fullTableName = $this->resolveTable($data['table']);
        $columns = $this->getColumns($fullTableName);

        $updateData = $data['data'];
        if (!is_array($updateData) || $this->isList($updateData) || empty($updateData)) {
            $this->sendError('Field "data" must be a non-empty object', 400);
        }

        $where = $this->resolveTarget($data, $columns, 'update');

        $setParts = [];
        $params = [];
        foreach ($updateData as $field => $value) {
            $this->assertColumn($field, $columns);
            $setParts[] = '`' . $field . '` = ?';
            $params[] = $value;
        }

        $whereSql = $this->buildWhere($where, $columns, $params);
        if ($whereSql === '') {
            $this->sendError('Refusing to update without conditions', 400);
        }

        $sql = "UPDATE `$fullTableName` SET " . implode(', ', $setParts) . $whereSql;
        list($affected) = $this->execWrite($sql, $params);

        $this->sendSuccess([
            'updated' => $affected,
            'table' => $data['table']
        ], 'Record(s) updated successfully');
    }

    private function deleteRecord()
    {
        $data = $this->readBody();
        $this->validateRequired($data, ['table']);

        $fullTableName = $this->resolveTable($data['table']);
        $columns = $this->getColumns($fullTableName);

        $where = $this->resolveTarget($data, $columns, 'delete');

        $params = [];
        $whereSql = $this->buildWhere($where, $columns, $params);
        if ($whereSql === '') {
            $this->sendError('Refusing to delete without conditions', 400);
        }

        $sql = "DELETE FROM `$fullTableName`" . $whereSql;
        list($affected) = $this->execWrite($sql, $params);

        $this->sendSuccess([
            'deleted' => $affected,
            'table' => $data['table']
        ], 'Record(s) deleted successfully');
    }

    private function resolveTarget($data, $columns, $operation)
    {
        if (isset($data['id'])) {
            $this->assertColumn('id', $columns);
            return ['id' => $data['id']];
        }
        if (isset($data['where']) && is_array($data['where']) && !empty($data['where'])) {
            return $data['where'];
        }
        $this->sendError('Operation "' . $operation . '" requires "id" or "where"', 400);
    }
}

$api = new DatabaseAPI();
$api->handleRequest();
