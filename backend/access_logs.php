<?php
require_once 'head.php';

// Get all logs with pagination and filters
if (isset($_GET['getAllLogs']) || isset($_POST['getAllLogs'])) {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;

    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '';
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : '';

    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    $types = '';

    if ($status !== 'all') {
        $whereConditions[] = "action = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (!empty($search)) {
        $whereConditions[] = "(email LIKE ? OR ip LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }

    if (!empty($dateFrom)) {
        $whereConditions[] = "time >= ?";
        $params[] = $dateFrom . ' 00:00:00';
        $types .= 's';
    }

    if (!empty($dateTo)) {
        $whereConditions[] = "time <= ?";
        $params[] = $dateTo . ' 23:59:59';
        $types .= 's';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM control_center_login_log $whereClause";
    if (!empty($params)) {
        $stmt = $con->prepare($countQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $countResult = $stmt->get_result();
    } else {
        $countResult = $con->query($countQuery);
    }

    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get paginated data
    $query = "SELECT id, email, action as status, ip as ip_address, time as timestamp, userID, token, verification_code, login_start
              FROM control_center_login_log 
              $whereClause 
              ORDER BY time DESC 
              LIMIT ? OFFSET ?";

    $stmt = $con->prepare($query);

    if (!empty($params)) {
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        // Add user_agent and error_message as empty strings since they don't exist in the table
        $row['user_agent'] = '';
        $row['error_message'] = '';
        $logs[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit
        ]
    ]);
    exit();
}

// Get statistics
if (isset($_GET['getStats']) || isset($_POST['getStats'])) {
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-d', strtotime('-30 days'));
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');

    $dateFromFull = $dateFrom . ' 00:00:00';
    $dateToFull = $dateTo . ' 23:59:59';

    // Total logs
    $totalQuery = "SELECT COUNT(*) as total FROM control_center_login_log 
                   WHERE login_start BETWEEN ? AND ?";
    $stmt = $con->prepare($totalQuery);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $totalLogs = $stmt->get_result()->fetch_assoc()['total'];

    // Success logs (check what values actually exist in action column)
    $successQuery = "SELECT COUNT(*) as total FROM control_center_login_log 
                     WHERE (action = 'successfull' OR action = 'success' OR action = 'login') AND time BETWEEN ? AND ?";
    $stmt = $con->prepare($successQuery);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $successLogs = $stmt->get_result()->fetch_assoc()['total'];

    // Failed logs
    $failedQuery = "SELECT COUNT(*) as total FROM control_center_login_log 
                    WHERE (action = 'failed' OR action LIKE '%fail%' OR action LIKE '%error%' OR action = 'processing') AND time BETWEEN ? AND ?";
    $stmt = $con->prepare($failedQuery);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $failedLogs = $stmt->get_result()->fetch_assoc()['total'];

    // Unique users
    $uniqueQuery = "SELECT COUNT(DISTINCT email) as total FROM control_center_login_log 
                    WHERE login_start BETWEEN ? AND ?";
    $stmt = $con->prepare($uniqueQuery);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $uniqueUsers = $stmt->get_result()->fetch_assoc()['total'];

    // Unique IPs
    $uniqueIPQuery = "SELECT COUNT(DISTINCT ip) as total FROM control_center_login_log 
                      WHERE login_start BETWEEN ? AND ?";
    $stmt = $con->prepare($uniqueIPQuery);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $uniqueIPs = $stmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total' => $totalLogs,
            'success' => $successLogs,
            'failed' => $failedLogs,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIPs,
            'success_rate' => $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 2) : 0
        ]
    ]);
    exit();
}

// Get chart data - daily login attempts
if (isset($_GET['getChartData']) || isset($_POST['getChartData'])) {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-d', strtotime('-30 days'));
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');
    
    $dateFromFull = $dateFrom . ' 00:00:00';
    $dateToFull = $dateTo . ' 23:59:59';

    $query = "SELECT 
                DATE(time) as date,
                COUNT(*) as total,
                SUM(CASE WHEN action = 'successfull' OR action = 'success' OR action = 'login' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN action = 'failed' OR action LIKE '%fail%' OR action LIKE '%error%' OR action = 'processing' THEN 1 ELSE 0 END) as failed
              FROM control_center_login_log 
              WHERE login_start BETWEEN ? AND ?
              GROUP BY DATE(time)
              ORDER BY date ASC";

    $stmt = $con->prepare($query);
    $stmt->bind_param('ss', $dateFromFull, $dateToFull);
    $stmt->execute();
    $result = $stmt->get_result();

    $chartData = [];
    while ($row = $result->fetch_assoc()) {
        $chartData[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $chartData
    ]);
    exit();
}

// Get top failed attempts by email
if (isset($_GET['getTopFailedAttempts']) || isset($_POST['getTopFailedAttempts'])) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-d', strtotime('-30 days'));
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');
    
    $dateFromFull = $dateFrom . ' 00:00:00';
    $dateToFull = $dateTo . ' 23:59:59';

    $query = "SELECT 
                email,
                COUNT(*) as attempt_count,
                MAX(time) as last_attempt
              FROM control_center_login_log 
              WHERE (action = 'failed' OR action LIKE '%fail%' OR action LIKE '%error%' OR action = 'processing')
                AND time BETWEEN ? AND ?
              GROUP BY email
              ORDER BY attempt_count DESC
              LIMIT ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param('ssi', $dateFromFull, $dateToFull, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $topFailed = [];
    while ($row = $result->fetch_assoc()) {
        $topFailed[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $topFailed
    ]);
    exit();
}

// Get top IPs
if (isset($_GET['getTopIPs']) || isset($_POST['getTopIPs'])) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-d', strtotime('-30 days'));
    $dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');
    
    $dateFromFull = $dateFrom . ' 00:00:00';
    $dateToFull = $dateTo . ' 23:59:59';

    $query = "SELECT 
                ip as ip_address,
                COUNT(*) as attempt_count,
                SUM(CASE WHEN action = 'successfull' OR action = 'success' OR action = 'login' THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN action = 'failed' OR action LIKE '%fail%' OR action LIKE '%error%' OR action = 'processing' THEN 1 ELSE 0 END) as failed_count,
                MAX(time) as last_seen
              FROM control_center_login_log 
              WHERE login_start BETWEEN ? AND ?
              GROUP BY ip
              ORDER BY attempt_count DESC
              LIMIT ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param('ssi', $dateFromFull, $dateToFull, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $topIPs = [];
    while ($row = $result->fetch_assoc()) {
        $topIPs[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $topIPs
    ]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
$con->close();
?>