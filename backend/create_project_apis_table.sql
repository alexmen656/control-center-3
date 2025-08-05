-- Create table for project APIs
CREATE TABLE IF NOT EXISTS `project_apis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `projectID` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text,
    `icon` varchar(100) DEFAULT 'code-outline',
    `type` varchar(50) DEFAULT 'REST', -- REST, GraphQL, WebSocket, etc.
    `base_url` text,
    `auth_type` varchar(50) DEFAULT 'none', -- none, api_key, bearer, oauth2, basic
    `status` varchar(20) DEFAULT 'inactive', -- active, inactive, testing
    `rate_limit` int(11) DEFAULT 100, -- requests per minute
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`projectID`) REFERENCES `projects`(`projectID`) ON DELETE CASCADE,
    UNIQUE KEY `unique_project_api` (`projectID`, `slug`)
);

-- Create table for API authentication keys/settings
CREATE TABLE IF NOT EXISTS `project_api_keys` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_id` int(11) NOT NULL,
    `key_name` varchar(255) NOT NULL,
    `key_value` text NOT NULL,
    `is_encrypted` boolean DEFAULT FALSE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`api_id`) REFERENCES `project_apis`(`id`) ON DELETE CASCADE
);

-- Create table for API endpoints
CREATE TABLE IF NOT EXISTS `project_api_endpoints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `method` varchar(10) NOT NULL DEFAULT 'GET',
    `endpoint` text NOT NULL,
    `description` text,
    `parameters` json,
    `headers` json,
    `response_example` json,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`api_id`) REFERENCES `project_apis`(`id`) ON DELETE CASCADE
);

-- Insert some default APIs for existing projects
INSERT IGNORE INTO `project_apis` (`projectID`, `name`, `slug`, `description`, `icon`, `type`, `status`) 
SELECT `projectID`, 'Weather API', 'weather-api', 'Get weather information for any location', 'cloud-outline', 'REST', 'active' 
FROM `projects` LIMIT 1;
