-- Marketing Campaigns Database Schema
-- This script creates the base table structure for marketing campaigns
-- Note: The actual tables will be created per project with the naming convention: marketing_campaigns_{project_name}

-- Example table structure (replace {project} with actual project name)
-- CREATE TABLE `marketing_campaigns_{project}` (

CREATE TABLE IF NOT EXISTS `marketing_campaigns_example` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'Campaign name',
    `description` text COMMENT 'Campaign description',
    `status` enum('draft','scheduled','active','paused','completed') DEFAULT 'draft' COMMENT 'Campaign status',
    `channel` enum('email','social','ppc','display','content') NOT NULL COMMENT 'Marketing channel',
    `target_audience` varchar(500) COMMENT 'Target audience description',
    `start_date` date COMMENT 'Campaign start date',
    `end_date` date COMMENT 'Campaign end date',
    `budget` decimal(10,2) DEFAULT 0.00 COMMENT 'Total campaign budget',
    `spent` decimal(10,2) DEFAULT 0.00 COMMENT 'Amount spent so far',
    `campaign_url` varchar(500) COMMENT 'Landing page URL',
    `utm_parameters` varchar(255) COMMENT 'UTM tracking parameters',
    `goals` text COMMENT 'Campaign goals and objectives',
    
    -- Performance Metrics
    `impressions` int(11) DEFAULT 0 COMMENT 'Total impressions',
    `clicks` int(11) DEFAULT 0 COMMENT 'Total clicks',
    `conversions` int(11) DEFAULT 0 COMMENT 'Total conversions',
    `click_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Click-through rate (%)',
    `conversion_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Conversion rate (%)',
    `cost_per_click` decimal(8,2) DEFAULT 0.00 COMMENT 'Cost per click',
    `cost_per_conversion` decimal(8,2) DEFAULT 0.00 COMMENT 'Cost per conversion',
    
    -- Timestamps
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
    
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_channel` (`channel`),
    KEY `idx_start_date` (`start_date`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_status_channel` (`status`, `channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Marketing campaigns management table';

-- Insert sample data for demonstration
INSERT INTO `marketing_campaigns_example` (
    `name`, `description`, `status`, `channel`, `target_audience`, 
    `start_date`, `end_date`, `budget`, `spent`, `campaign_url`, 
    `utm_parameters`, `goals`, `impressions`, `clicks`, `conversions`
) VALUES 
(
    'Summer Sale 2024', 
    'Promote summer collection with 25% discount', 
    'active', 
    'email', 
    'Existing customers, age 25-45, fashion interested',
    '2024-06-01', 
    '2024-08-31', 
    5000.00, 
    2350.50, 
    'https://example.com/summer-sale',
    'utm_campaign=summer2024&utm_medium=email&utm_source=newsletter',
    'Generate 1000+ sales, achieve 15% CTR, increase brand awareness',
    125000,
    15630,
    892
),
(
    'Brand Awareness Q3', 
    'Increase brand visibility through social media', 
    'active', 
    'social', 
    'Young adults 18-35, tech enthusiasts',
    '2024-07-01', 
    '2024-09-30', 
    8000.00, 
    3200.00, 
    'https://example.com/brand-campaign',
    'utm_campaign=brandq3&utm_medium=social&utm_source=instagram',
    'Reach 500K people, gain 5K followers, improve engagement rate',
    450000,
    22500,
    1125
),
(
    'Product Launch - App v2.0', 
    'Launch new app version with enhanced features', 
    'scheduled', 
    'ppc', 
    'Mobile app users, technology early adopters',
    '2024-09-15', 
    '2024-11-15', 
    12000.00, 
    0.00, 
    'https://example.com/app-v2-launch',
    'utm_campaign=appv2launch&utm_medium=ppc&utm_source=google',
    'Drive 10K app downloads, achieve <$2 CPA, increase app store rating',
    0,
    0,
    0
),
(
    'Holiday Campaign 2024', 
    'Christmas and New Year promotional campaign', 
    'draft', 
    'display', 
    'Holiday shoppers, families, gift buyers',
    '2024-12-01', 
    '2024-12-31', 
    15000.00, 
    0.00, 
    'https://example.com/holiday-deals',
    'utm_campaign=holiday2024&utm_medium=display&utm_source=retail',
    'Boost Q4 sales by 30%, increase website traffic, improve ROI',
    0,
    0,
    0
),
(
    'Content Marketing Series', 
    'Educational blog posts and whitepapers', 
    'active', 
    'content', 
    'B2B decision makers, industry professionals',
    '2024-05-01', 
    '2024-12-31', 
    3000.00, 
    1200.00, 
    'https://example.com/resources',
    'utm_campaign=contentmarketing&utm_medium=organic&utm_source=blog',
    'Generate 500 qualified leads, establish thought leadership',
    75000,
    5250,
    315
);

-- Create analytics summary view (optional)
CREATE OR REPLACE VIEW `marketing_campaigns_summary_example` AS
SELECT 
    channel,
    status,
    COUNT(*) as campaign_count,
    SUM(budget) as total_budget,
    SUM(spent) as total_spent,
    SUM(impressions) as total_impressions,
    SUM(clicks) as total_clicks,
    SUM(conversions) as total_conversions,
    ROUND(AVG(click_rate), 2) as avg_click_rate,
    ROUND(AVG(conversion_rate), 2) as avg_conversion_rate,
    ROUND(SUM(spent) / NULLIF(SUM(clicks), 0), 2) as avg_cost_per_click,
    ROUND(SUM(spent) / NULLIF(SUM(conversions), 0), 2) as avg_cost_per_conversion
FROM `marketing_campaigns_example`
GROUP BY channel, status
ORDER BY channel, status;

-- Additional useful queries for campaign management

-- Query to find campaigns that need attention (high spend, low performance)
-- SELECT * FROM marketing_campaigns_example 
-- WHERE status = 'active' 
-- AND spent > budget * 0.8 
-- AND (click_rate < 2.0 OR conversion_rate < 1.0);

-- Query for monthly performance report
-- SELECT 
--     DATE_FORMAT(start_date, '%Y-%m') as month,
--     channel,
--     COUNT(*) as campaigns,
--     SUM(budget) as monthly_budget,
--     SUM(spent) as monthly_spent,
--     SUM(conversions) as monthly_conversions
-- FROM marketing_campaigns_example 
-- WHERE start_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
-- GROUP BY DATE_FORMAT(start_date, '%Y-%m'), channel
-- ORDER BY month DESC, channel;

-- Query for campaign ROI analysis
-- SELECT 
--     name,
--     channel,
--     budget,
--     spent,
--     conversions,
--     ROUND((conversions * 50 - spent) / NULLIF(spent, 0) * 100, 2) as roi_percentage
-- FROM marketing_campaigns_example 
-- WHERE status IN ('active', 'completed') 
-- AND spent > 0
-- ORDER BY roi_percentage DESC;

-- Instructions for creating project-specific tables:
-- 1. Replace 'example' with the actual project name (use underscores instead of hyphens)
-- 2. The PHP API will automatically create tables with the correct naming convention
-- 3. Each project will have its own isolated campaign data

-- Table naming convention: marketing_campaigns_{project_name}
-- Examples:
-- - marketing_campaigns_my_ecommerce_site
-- - marketing_campaigns_mobile_app_project
-- - marketing_campaigns_saas_platform
