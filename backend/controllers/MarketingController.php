<?php

class MarketingController
{
    private function getTableName($project)
    {
        return 'marketing_campaigns_' . str_replace('-', '_', $project);
    }

    private function createTableIfNotExists($project)
    {
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

    private function getCampaigns($project, $filters = [])
    {
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

    private function getCampaignById($project, $id)
    {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);

        $result = query("SELECT * FROM `$tableName` WHERE id = '$id'");

        if (mysqli_num_rows($result) === 1) {
            return fetch_assoc($result);
        }

        return null;
    }

    private function createCampaign($project, $data)
    {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);

        if (empty($data['name']) || empty($data['channel'])) {
            throw new Exception('Name and channel are required fields');
        }

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

    private function updateCampaign($project, $id, $data)
    {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);

        $existing = $this->getCampaignById($project, $id);
        if (!$existing) {
            throw new Exception('Campaign not found');
        }

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
            return $this->getCampaignById($project, $id);
        }

        throw new Exception('Failed to update campaign');
    }

    private function deleteCampaignById($project, $id)
    {
        $this->createTableIfNotExists($project);
        $tableName = $this->getTableName($project);
        $id = escape_string($id);

        $existing = $this->getCampaignById($project, $id);
        if (!$existing) {
            throw new Exception('Campaign not found');
        }

        $sql = "DELETE FROM `$tableName` WHERE id = '$id'";

        if (query($sql)) {
            return true;
        }

        throw new Exception('Failed to delete campaign');
    }

    private function getStatsData($project)
    {
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

            if ($stats['total_impressions'] > 0) {
                $stats['avg_click_rate'] = round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2);
            }

            if ($stats['total_clicks'] > 0) {
                $stats['avg_conversion_rate'] = round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2);
            }
        }

        $result = query("SELECT status, COUNT(*) as count FROM `$tableName` GROUP BY status");
        while ($row = fetch_assoc($result)) {
            $stats['campaigns_by_status'][$row['status']] = $row['count'];
        }

        $result = query("SELECT channel, COUNT(*) as count, SUM(budget) as total_budget, SUM(spent) as total_spent
                        FROM `$tableName` GROUP BY channel");
        while ($row = fetch_assoc($result)) {
            $stats['campaigns_by_channel'][$row['channel']] = $row;
        }

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

    private function importCampaignsFromCSV($project, $csvData)
    {
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

    private function duplicateCampaign($project, $id)
    {
        $campaign = $this->getCampaignById($project, $id);

        if (!$campaign) {
            throw new Exception('Campaign not found');
        }

        unset($campaign['id'], $campaign['created_at'], $campaign['updated_at']);
        $campaign['name'] = $campaign['name'] . ' (Copy)';
        $campaign['status'] = 'draft';

        return $this->createCampaign($project, $campaign);
    }

    private function resolveProject(Request $request, Response $response): ?string
    {
        $project = $request->input('project', '');
        if (empty($project)) {
            $response->error('Project parameter is required', 400);
            return null;
        }

        try {
            $projectID = getProjectID($project);
        } catch (Exception $e) {
            $response->error('Project not found', 404);
            return null;
        }

        if (!checkUserProjectPermission($request->userID, $projectID)) {
            $response->error('Access denied', 403);
            return null;
        }

        return $project;
    }

    private function collectData(Request $request): array
    {
        $fields = [
            'name', 'description', 'status', 'channel', 'target_audience',
            'start_date', 'end_date', 'budget', 'spent', 'campaign_url',
            'utm_parameters', 'goals', 'impressions', 'clicks', 'conversions'
        ];

        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        return $data;
    }

    public function list(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $filters = [
            'status' => $request->input('status', ''),
            'channel' => $request->input('channel', ''),
            'search' => $request->input('search', '')
        ];

        try {
            $campaigns = $this->getCampaigns($project, $filters);
            $response->json(['success' => true, 'campaigns' => $campaigns]);
        } catch (Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function stats(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        try {
            $stats = $this->getStatsData($project);
            $response->json(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function get(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $id = $request->params['id'];
        $campaign = $this->getCampaignById($project, $id);

        if ($campaign) {
            $response->json(['success' => true, 'campaign' => $campaign]);
        } else {
            $response->error('Campaign not found', 404);
        }
    }

    public function create(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        try {
            $campaignId = $this->createCampaign($project, $this->collectData($request));
            $campaign = $this->getCampaignById($project, $campaignId);
            $response->json(['success' => true, 'campaign' => $campaign, 'id' => $campaignId]);
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    public function update(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $id = $request->params['id'];
        if (empty($id)) {
            $response->error('Campaign ID is required for update', 400);
            return;
        }

        try {
            $campaign = $this->updateCampaign($project, $id, $this->collectData($request));
            $response->json(['success' => true, 'campaign' => $campaign]);
        } catch (Exception $e) {
            $status = $e->getMessage() === 'Campaign not found' ? 404 : 400;
            $response->error($e->getMessage(), $status);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $id = $request->params['id'];
        if (empty($id)) {
            $response->error('Campaign ID is required for delete', 400);
            return;
        }

        try {
            $this->deleteCampaignById($project, $id);
            $response->json(['success' => true, 'message' => 'Campaign deleted successfully']);
        } catch (Exception $e) {
            $status = $e->getMessage() === 'Campaign not found' ? 404 : 400;
            $response->error($e->getMessage(), $status);
        }
    }

    public function updateStatus(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $id = $request->params['id'];
        $status = $request->input('status', '');

        if (empty($id) || empty($status)) {
            $response->error('Campaign ID and status are required', 400);
            return;
        }

        try {
            $campaign = $this->updateCampaign($project, $id, ['status' => $status]);
            $response->json(['success' => true, 'campaign' => $campaign]);
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Campaign not found' ? 404 : 400;
            $response->error($e->getMessage(), $statusCode);
        }
    }

    public function duplicate(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $id = $request->params['id'];
        if (empty($id)) {
            $response->error('Campaign ID is required for duplication', 400);
            return;
        }

        try {
            $newCampaignId = $this->duplicateCampaign($project, $id);
            $campaign = $this->getCampaignById($project, $newCampaignId);
            $response->json(['success' => true, 'campaign' => $campaign, 'id' => $newCampaignId]);
        } catch (Exception $e) {
            $status = $e->getMessage() === 'Campaign not found' ? 404 : 400;
            $response->error($e->getMessage(), $status);
        }
    }

    public function importCsv(Request $request, Response $response): void
    {
        $project = $this->resolveProject($request, $response);
        if (!$project) {
            return;
        }

        $csvData = $request->input('csv_data');

        if (empty($csvData)) {
            $response->error('CSV data is required', 400);
            return;
        }

        if (!is_array($csvData)) {
            $csvData = json_decode($csvData, true);
        }

        if (!is_array($csvData)) {
            $response->error('CSV data is required', 400);
            return;
        }

        try {
            $result = $this->importCampaignsFromCSV($project, $csvData);
            $response->json(['success' => true, 'result' => $result]);
        } catch (Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }
}
