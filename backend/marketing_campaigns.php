<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once 'config.php';
include_once 'head.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Marketing Campaigns Management API
 * Handles CRUD operations for marketing campaigns
 */
class MarketingCampaignsAPI {
    
    private function getTableName($project) {
        return 'marketing_campaigns_' . str_replace('-', '_', $project);
    }
    
    private function createTableIfNotExists($project) {
        $tableName = $this->getTableName($project);
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `status` enum('draft','scheduled','active','paused','completed') DEFAULT 'draft',
            `channel` enum('email','social','ppc','display','content') NOT NULL,
            `target_audience` varchar(500),
            `start_date` date,
            `end_date` date,
            `budget` decimal(10,2) DEFAULT 0.00,
            `spent` decimal(10,2) DEFAULT 0.00,
            `campaign_url` varchar(500),
            `utm_parameters` varchar(255),
            `goals` text,
            `impressions` int(11) DEFAULT 0,
            `clicks` int(11) DEFAULT 0,
            `conversions` int(11) DEFAULT 0,
            `click_rate` decimal(5,2) DEFAULT 0.00,
            `conversion_rate` decimal(5,2) DEFAULT 0.00,
            `cost_per_click` decimal(8,2) DEFAULT 0.00,
            `cost_per_conversion` decimal(8,2) DEFAULT 0.00,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            KEY `channel` (`channel`),
            KEY `start_date` (`start_date`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return query($sql);
    }
    
    public function getCampaigns($project, $filters = []) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $sql = "SELECT * FROM `$tableName`";
        $conditions = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $status = escape_string($filters['status']);
            $conditions[] = "status = '$status'";
        }
        
        if (isset($filters['channel']) && !empty($filters['channel'])) {
            $channel = escape_string($filters['channel']);
            $conditions[] = "channel = '$channel'";
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = escape_string($filters['search']);
            $conditions[] = "(name LIKE '%$search%' OR description LIKE '%$search%' OR target_audience LIKE '%$search%')";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $result = query($sql);
        $campaigns = [];
        
        while ($row = fetch_assoc($result)) {
            // Calculate additional metrics
            if ($row['impressions'] > 0 && $row['clicks'] > 0) {
                $row['click_rate'] = round(($row['clicks'] / $row['impressions']) * 100, 2);
            }
            
            if ($row['clicks'] > 0 && $row['conversions'] > 0) {
                $row['conversion_rate'] = round(($row['conversions'] / $row['clicks']) * 100, 2);
            }
            
            if ($row['clicks'] > 0 && $row['spent'] > 0) {
                $row['cost_per_click'] = round($row['spent'] / $row['clicks'], 2);
            }
            
            if ($row['conversions'] > 0 && $row['spent'] > 0) {
                $row['cost_per_conversion'] = round($row['spent'] / $row['conversions'], 2);
            }
            
            $campaigns[] = $row;
        }
        
        return $campaigns;
    }
    
    public function getCampaign($project, $id) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);
        
        $result = query("SELECT * FROM `$tableName` WHERE id = '$id'");
        
        if (mysqli_num_rows($result) === 1) {
            return fetch_assoc($result);
        }
        
        return null;
    }
    
    public function createCampaign($project, $data) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['channel'])) {
            throw new Exception('Name and channel are required fields');
        }
        
        // Prepare data
        $name = escape_string($data['name']);
        $description = escape_string($data['description'] ?? '');
        $status = escape_string($data['status'] ?? 'draft');
        $channel = escape_string($data['channel']);
        $target_audience = escape_string($data['target_audience'] ?? '');
        $start_date = !empty($data['start_date']) ? "'" . escape_string($data['start_date']) . "'" : 'NULL';
        $end_date = !empty($data['end_date']) ? "'" . escape_string($data['end_date']) . "'" : 'NULL';
        $budget = floatval($data['budget'] ?? 0);
        $spent = floatval($data['spent'] ?? 0);
        $campaign_url = escape_string($data['campaign_url'] ?? '');
        $utm_parameters = escape_string($data['utm_parameters'] ?? '');
        $goals = escape_string($data['goals'] ?? '');
        $impressions = intval($data['impressions'] ?? 0);
        $clicks = intval($data['clicks'] ?? 0);
        $conversions = intval($data['conversions'] ?? 0);
        
        $sql = "INSERT INTO `$tableName` (
            name, description, status, channel, target_audience, 
            start_date, end_date, budget, spent, campaign_url, 
            utm_parameters, goals, impressions, clicks, conversions
        ) VALUES (
            '$name', '$description', '$status', '$channel', '$target_audience',
            $start_date, $end_date, $budget, $spent, '$campaign_url',
            '$utm_parameters', '$goals', $impressions, $clicks, $conversions
        )";
        
        if (query($sql)) {
            return mysqli_insert_id($GLOBALS['con']);
        }
        
        throw new Exception('Failed to create campaign');
    }
    
    public function updateCampaign($project, $id, $data) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);
        
        // Check if campaign exists
        $existing = $this->getCampaign($project, $id);
        if (!$existing) {
            throw new Exception('Campaign not found');
        }
        
        // Build update query
        $updates = [];
        
        if (isset($data['name'])) {
            $updates[] = "name = '" . escape_string($data['name']) . "'";
        }
        
        if (isset($data['description'])) {
            $updates[] = "description = '" . escape_string($data['description']) . "'";
        }
        
        if (isset($data['status'])) {
            $updates[] = "status = '" . escape_string($data['status']) . "'";
        }
        
        if (isset($data['channel'])) {
            $updates[] = "channel = '" . escape_string($data['channel']) . "'";
        }
        
        if (isset($data['target_audience'])) {
            $updates[] = "target_audience = '" . escape_string($data['target_audience']) . "'";
        }
        
        if (isset($data['start_date'])) {
            if (!empty($data['start_date'])) {
                $updates[] = "start_date = '" . escape_string($data['start_date']) . "'";
            } else {
                $updates[] = "start_date = NULL";
            }
        }
        
        if (isset($data['end_date'])) {
            if (!empty($data['end_date'])) {
                $updates[] = "end_date = '" . escape_string($data['end_date']) . "'";
            } else {
                $updates[] = "end_date = NULL";
            }
        }
        
        if (isset($data['budget'])) {
            $updates[] = "budget = " . floatval($data['budget']);
        }
        
        if (isset($data['spent'])) {
            $updates[] = "spent = " . floatval($data['spent']);
        }
        
        if (isset($data['campaign_url'])) {
            $updates[] = "campaign_url = '" . escape_string($data['campaign_url']) . "'";
        }
        
        if (isset($data['utm_parameters'])) {
            $updates[] = "utm_parameters = '" . escape_string($data['utm_parameters']) . "'";
        }
        
        if (isset($data['goals'])) {
            $updates[] = "goals = '" . escape_string($data['goals']) . "'";
        }
        
        if (isset($data['impressions'])) {
            $updates[] = "impressions = " . intval($data['impressions']);
        }
        
        if (isset($data['clicks'])) {
            $updates[] = "clicks = " . intval($data['clicks']);
        }
        
        if (isset($data['conversions'])) {
            $updates[] = "conversions = " . intval($data['conversions']);
        }
        
        if (empty($updates)) {
            throw new Exception('No data to update');
        }
        
        $sql = "UPDATE `$tableName` SET " . implode(', ', $updates) . " WHERE id = '$id'";
        
        if (query($sql)) {
            return $this->getCampaign($project, $id);
        }
        
        throw new Exception('Failed to update campaign');
    }
    
    public function deleteCampaign($project, $id) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);
        
        // Check if campaign exists
        $existing = $this->getCampaign($project, $id);
        if (!$existing) {
            throw new Exception('Campaign not found');
        }
        
        $sql = "DELETE FROM `$tableName` WHERE id = '$id'";
        
        if (query($sql)) {
            return true;
        }
        
        throw new Exception('Failed to delete campaign');
    }
    
    public function updateStatus($project, $id, $status) {
        return $this->updateCampaign($project, $id, ['status' => $status]);
    }
    
    public function getStats($project) {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        
        $stats = [
            'total_campaigns' => 0,
            'active_campaigns' => 0,
            'scheduled_campaigns' => 0,
            'total_budget' => 0,
            'total_spent' => 0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_conversions' => 0,
            'avg_click_rate' => 0,
            'avg_conversion_rate' => 0,
            'campaigns_by_status' => [],
            'campaigns_by_channel' => [],
            'monthly_performance' => []
        ];
        
        // Get basic counts and sums
        $result = query("SELECT 
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_campaigns,
            SUM(budget) as total_budget,
            SUM(spent) as total_spent,
            SUM(impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(conversions) as total_conversions
            FROM `$tableName`");
        
        if ($row = fetch_assoc($result)) {
            $stats = array_merge($stats, $row);
            
            // Calculate averages
            if ($stats['total_impressions'] > 0) {
                $stats['avg_click_rate'] = round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2);
            }
            
            if ($stats['total_clicks'] > 0) {
                $stats['avg_conversion_rate'] = round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2);
            }
        }
        
        // Get campaigns by status
        $result = query("SELECT status, COUNT(*) as count FROM `$tableName` GROUP BY status");
        while ($row = fetch_assoc($result)) {
            $stats['campaigns_by_status'][$row['status']] = $row['count'];
        }
        
        // Get campaigns by channel
        $result = query("SELECT channel, COUNT(*) as count, SUM(budget) as total_budget, SUM(spent) as total_spent 
                        FROM `$tableName` GROUP BY channel");
        while ($row = fetch_assoc($result)) {
            $stats['campaigns_by_channel'][$row['channel']] = $row;
        }
        
        // Get monthly performance for the last 12 months
        $result = query("SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as campaigns_created,
            SUM(budget) as monthly_budget,
            SUM(spent) as monthly_spent,
            SUM(impressions) as monthly_impressions,
            SUM(clicks) as monthly_clicks,
            SUM(conversions) as monthly_conversions
            FROM `$tableName` 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC");
        
        while ($row = fetch_assoc($result)) {
            $stats['monthly_performance'][] = $row;
        }
        
        return $stats;
    }
    
    public function importCampaignsFromCSV($project, $csvData) {
        $this->createTableIfNotExists($project);
        
        $imported = 0;
        $errors = [];
        
        foreach ($csvData as $index => $row) {
            try {
                $this->createCampaign($project, $row);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
    
    public function duplicateCampaign($project, $id) {
        $campaign = $this->getCampaign($project, $id);
        
        if (!$campaign) {
            throw new Exception('Campaign not found');
        }
        
        // Remove ID and timestamps, modify name
        unset($campaign['id'], $campaign['created_at'], $campaign['updated_at']);
        $campaign['name'] = $campaign['name'] . ' (Copy)';
        $campaign['status'] = 'draft';
        
        return $this->createCampaign($project, $campaign);
    }
}

// Main execution
try {
    $api = new MarketingCampaignsAPI();
    $project = $_GET['project'] ?? $_POST['project'] ?? '';
    
    if (empty($project)) {
        throw new Exception('Project parameter is required');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_POST['action'] ?? $_GET['action'] ?? 'list';
    
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
                $stats = $api->getStats($project);
                echo json_encode(['success' => true, 'stats' => $stats]);
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $campaign = $api->getCampaign($project, $_GET['id']);
                if ($campaign) {
                    echo json_encode(['success' => true, 'campaign' => $campaign]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Campaign not found']);
                }
            } else {
                // List campaigns with optional filters
                $filters = [
                    'status' => $_GET['status'] ?? '',
                    'channel' => $_GET['channel'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];
                
                $campaigns = $api->getCampaigns($project, $filters);
                echo json_encode(['success' => true, 'campaigns' => $campaigns]);
            }
            break;
            
        case 'POST':
            $input = $_POST;
            
            switch ($action) {
                case 'create':
                    $campaignId = $api->createCampaign($project, $input);
                    $campaign = $api->getCampaign($project, $campaignId);
                    echo json_encode(['success' => true, 'campaign' => $campaign, 'id' => $campaignId]);
                    break;
                    
                case 'update':
                    if (empty($input['id'])) {
                        throw new Exception('Campaign ID is required for update');
                    }
                    $campaign = $api->updateCampaign($project, $input['id'], $input);
                    echo json_encode(['success' => true, 'campaign' => $campaign]);
                    break;
                    
                case 'delete':
                    if (empty($input['id'])) {
                        throw new Exception('Campaign ID is required for delete');
                    }
                    $api->deleteCampaign($project, $input['id']);
                    echo json_encode(['success' => true, 'message' => 'Campaign deleted successfully']);
                    break;
                    
                case 'update_status':
                    if (empty($input['id']) || empty($input['status'])) {
                        throw new Exception('Campaign ID and status are required');
                    }
                    $campaign = $api->updateStatus($project, $input['id'], $input['status']);
                    echo json_encode(['success' => true, 'campaign' => $campaign]);
                    break;
                    
                case 'duplicate':
                    if (empty($input['id'])) {
                        throw new Exception('Campaign ID is required for duplication');
                    }
                    $newCampaignId = $api->duplicateCampaign($project, $input['id']);
                    $campaign = $api->getCampaign($project, $newCampaignId);
                    echo json_encode(['success' => true, 'campaign' => $campaign, 'id' => $newCampaignId]);
                    break;
                    
                case 'import_csv':
                    // Handle CSV import
                    if (empty($input['csv_data'])) {
                        throw new Exception('CSV data is required');
                    }
                    $csvData = json_decode($input['csv_data'], true);
                    $result = $api->importCampaignsFromCSV($project, $csvData);
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;
                    
                default:
                    throw new Exception('Invalid action: ' . $action);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
