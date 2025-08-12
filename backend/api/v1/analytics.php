<?php

/**
 * Analytics API - Analytics und Tracking für CMS Projekte
 * Handles analytics data collection and reporting
 */

require_once 'BaseAPI.php';

class AnalyticsAPI extends BaseAPI {

    public function __construct() {
        parent::__construct();
        $this->initDatabase();
    }

    private function initDatabase() {
        // Include mysql.php für query() Funktionen
        if (file_exists('../../mysql.php')) {
            require_once '../../mysql.php';
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Log API call
        $this->logApiCall('analytics', $method);

        switch ($method) {
            case 'GET':
                if (isset($pathParts[3])) {
                    switch ($pathParts[3]) {
                        case 'events':
                            $this->getEvents();
                            break;
                        case 'report':
                            $this->getReport();
                            break;
                        case 'stats':
                            $this->getStats();
                            break;
                        default:
                            $this->sendError('Invalid endpoint', 404);
                    }
                } else {
                    $this->getOverview();
                }
                break;
            case 'POST':
                if (isset($pathParts[3]) && $pathParts[3] === 'track') {
                    $this->trackEvent();
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function trackEvent() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['event_name']);
        
        $eventName = $this->sanitize($data['event_name']);
        $eventData = isset($data['data']) ? json_encode($data['data']) : '{}';
        $sessionId = $this->sanitize($data['session_id'] ?? uniqid());
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Event in analytics_events Tabelle speichern
        $sql = "INSERT INTO analytics_events 
                (project_id, user_id, event_name, event_data, session_id, user_agent, ip_address, referer, created_at) 
                VALUES ('{$this->projectID}', {$this->userID}, '$eventName', '$eventData', '$sessionId', 
                        '" . $this->sanitize($userAgent) . "', '" . $this->sanitize($ipAddress) . "', 
                        '" . $this->sanitize($referer) . "', NOW())";
        
        $result = query($sql);
        
        if ($result) {
            $this->sendSuccess([
                'tracked' => true,
                'event_name' => $eventName,
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Event tracked successfully');
        } else {
            $this->sendError('Failed to track event', 500);
        }
    }

    private function getEvents() {
        $params = $_GET;
        
        $sql = "SELECT id, event_name, event_data, session_id, user_agent, ip_address, referer, created_at 
                FROM analytics_events 
                WHERE project_id = '{$this->projectID}'";
        
        // Filter anwenden
        if (isset($params['event_name'])) {
            $eventName = $this->sanitize($params['event_name']);
            $sql .= " AND event_name = '$eventName'";
        }
        
        if (isset($params['from_date'])) {
            $fromDate = $this->sanitize($params['from_date']);
            $sql .= " AND created_at >= '$fromDate'";
        }
        
        if (isset($params['to_date'])) {
            $toDate = $this->sanitize($params['to_date']);
            $sql .= " AND created_at <= '$toDate'";
        }
        
        // Sortierung
        $sql .= " ORDER BY created_at DESC";
        
        // Paginierung
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $limit = isset($params['limit']) ? max(1, min(1000, (int)$params['limit'])) : 100;
        $offset = ($page - 1) * $limit;
        
        $sql .= " LIMIT $limit OFFSET $offset";
        
        $result = query($sql);
        
        $events = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['event_data'] = json_decode($row['event_data'], true);
                $events[] = $row;
            }
        }
        
        $this->sendSuccess([
            'events' => $events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    private function getReport() {
        $params = $_GET;
        $reportType = $params['type'] ?? 'daily';
        
        switch ($reportType) {
            case 'daily':
                $this->getDailyReport();
                break;
            case 'weekly':
                $this->getWeeklyReport();
                break;
            case 'monthly':
                $this->getMonthlyReport();
                break;
            case 'events':
                $this->getEventReport();
                break;
            default:
                $this->sendError('Invalid report type', 400);
        }
    }

    private function getDailyReport() {
        $days = isset($_GET['days']) ? min(365, max(1, (int)$_GET['days'])) : 30;
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT user_id) as unique_users
                FROM analytics_events 
                WHERE project_id = '{$this->projectID}' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $result = query($sql);
        
        $data = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        $this->sendSuccess([
            'report_type' => 'daily',
            'period' => "$days days",
            'data' => $data
        ]);
    }

    private function getWeeklyReport() {
        $weeks = isset($_GET['weeks']) ? min(52, max(1, (int)$_GET['weeks'])) : 12;
        
        $sql = "SELECT 
                    YEARWEEK(created_at) as week,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT user_id) as unique_users
                FROM analytics_events 
                WHERE project_id = '{$this->projectID}' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL $weeks WEEK)
                GROUP BY YEARWEEK(created_at)
                ORDER BY week DESC";
        
        $result = query($sql);
        
        $data = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        $this->sendSuccess([
            'report_type' => 'weekly',
            'period' => "$weeks weeks",
            'data' => $data
        ]);
    }

    private function getMonthlyReport() {
        $months = isset($_GET['months']) ? min(24, max(1, (int)$_GET['months'])) : 12;
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    COUNT(DISTINCT user_id) as unique_users
                FROM analytics_events 
                WHERE project_id = '{$this->projectID}' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC";
        
        $result = query($sql);
        
        $data = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        $this->sendSuccess([
            'report_type' => 'monthly',
            'period' => "$months months",
            'data' => $data
        ]);
    }

    private function getEventReport() {
        $days = isset($_GET['days']) ? min(365, max(1, (int)$_GET['days'])) : 30;
        
        $sql = "SELECT 
                    event_name,
                    COUNT(*) as count,
                    COUNT(DISTINCT session_id) as unique_sessions
                FROM analytics_events 
                WHERE project_id = '{$this->projectID}' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY event_name
                ORDER BY count DESC";
        
        $result = query($sql);
        
        $data = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        
        $this->sendSuccess([
            'report_type' => 'events',
            'period' => "$days days",
            'data' => $data
        ]);
    }

    private function getStats() {
        // Verschiedene Statistiken abrufen
        $stats = [];
        
        // Heute
        $todayResult = query("SELECT COUNT(*) as count FROM analytics_events WHERE project_id = '{$this->projectID}' AND DATE(created_at) = CURDATE()");
        $stats['today'] = $todayResult ? (int)mysqli_fetch_assoc($todayResult)['count'] : 0;
        
        // Diese Woche
        $weekResult = query("SELECT COUNT(*) as count FROM analytics_events WHERE project_id = '{$this->projectID}' AND YEARWEEK(created_at) = YEARWEEK(NOW())");
        $stats['this_week'] = $weekResult ? (int)mysqli_fetch_assoc($weekResult)['count'] : 0;
        
        // Diesen Monat
        $monthResult = query("SELECT COUNT(*) as count FROM analytics_events WHERE project_id = '{$this->projectID}' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stats['this_month'] = $monthResult ? (int)mysqli_fetch_assoc($monthResult)['count'] : 0;
        
        // Insgesamt
        $totalResult = query("SELECT COUNT(*) as count FROM analytics_events WHERE project_id = '{$this->projectID}'");
        $stats['total'] = $totalResult ? (int)mysqli_fetch_assoc($totalResult)['count'] : 0;
        
        // Top Events (letzte 30 Tage)
        $topEventsResult = query("SELECT event_name, COUNT(*) as count FROM analytics_events WHERE project_id = '{$this->projectID}' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY event_name ORDER BY count DESC LIMIT 5");
        $topEvents = [];
        if ($topEventsResult && mysqli_num_rows($topEventsResult) > 0) {
            while ($row = mysqli_fetch_assoc($topEventsResult)) {
                $topEvents[] = $row;
            }
        }
        $stats['top_events'] = $topEvents;
        
        $this->sendSuccess($stats);
    }

    private function getOverview() {
        // Allgemeine Übersicht
        $this->getStats();
    }
}

// Handle the request
$api = new AnalyticsAPI();
$api->handleRequest();
