-- Create table for available CMS APIs (provided by the system)
CREATE TABLE IF NOT EXISTS `cms_apis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL UNIQUE,
    `description` text,
    `icon` varchar(100) DEFAULT 'code-outline',
    `category` varchar(100) DEFAULT 'general', -- user, file, database, auth, etc.
    `version` varchar(20) DEFAULT '1.0.0',
    `endpoint_base` varchar(255) NOT NULL, -- e.g., '/api/v1/users'
    `auth_required` boolean DEFAULT TRUE,
    `rate_limit_default` int(11) DEFAULT 100,
    `is_active` boolean DEFAULT TRUE,
    `documentation_url` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

-- Create table for API endpoints (what each API provides)
CREATE TABLE IF NOT EXISTS `cms_api_endpoints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `method` varchar(10) NOT NULL DEFAULT 'GET',
    `endpoint` varchar(255) NOT NULL,
    `description` text,
    `parameters` json,
    `response_schema` json,
    `example_request` json,
    `example_response` json,
    `requires_auth` boolean DEFAULT TRUE,
    `is_active` boolean DEFAULT TRUE,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`api_id`) REFERENCES `cms_apis`(`id`) ON DELETE CASCADE
);

-- Create table for project API subscriptions (which APIs a project uses)
CREATE TABLE IF NOT EXISTS `project_api_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `projectID` varchar(255) NOT NULL,
    `api_id` int(11) NOT NULL,
    `api_key` varchar(255) NOT NULL, -- unique API key for this project
    `rate_limit` int(11) DEFAULT 100,
    `is_enabled` boolean DEFAULT TRUE,
    `usage_count` int(11) DEFAULT 0,
    `last_used` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`projectID`) REFERENCES `projects`(`projectID`) ON DELETE CASCADE,
    FOREIGN KEY (`api_id`) REFERENCES `cms_apis`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_project_api` (`projectID`, `api_id`)
);

-- Create table for API usage logs
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subscription_id` int(11) NOT NULL,
    `endpoint_id` int(11) NOT NULL,
    `request_method` varchar(10),
    `request_path` varchar(500),
    `response_status` int(3),
    `response_time_ms` int(11),
    `ip_address` varchar(45),
    `user_agent` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`subscription_id`) REFERENCES `project_api_subscriptions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`endpoint_id`) REFERENCES `cms_api_endpoints`(`id`) ON DELETE CASCADE,
    INDEX `idx_subscription_created` (`subscription_id`, `created_at`),
    INDEX `idx_created_at` (`created_at`)
);

-- Insert sample CMS APIs that your system provides
INSERT INTO `cms_apis` (`name`, `slug`, `description`, `icon`, `category`, `endpoint_base`, `documentation_url`) VALUES
('User Management API', 'user-management', 'Manage users, authentication, and permissions', 'people-outline', 'auth', '/api/v1/users', '/docs/apis/user-management'),
('File Storage API', 'file-storage', 'Upload, download, and manage files', 'folder-outline', 'storage', '/api/v1/files', '/docs/apis/file-storage'),
('Database API', 'database', 'Perform database operations and queries', 'server-outline', 'data', '/api/v1/database', '/docs/apis/database'),
('Notification API', 'notifications', 'Send push notifications and emails', 'notifications-outline', 'communication', '/api/v1/notifications', '/docs/apis/notifications'),
('Analytics API', 'analytics', 'Track events and generate reports', 'analytics-outline', 'analytics', '/api/v1/analytics', '/docs/apis/analytics'),
-- External API SDKs (no PHP backend required)
('OpenAI API', 'openai', 'AI text generation, chat completions, and embeddings', 'brain-outline', 'ai', '/sdk/openai', '/docs/apis/openai'),
('Gemini API', 'gemini', 'Google AI text and vision generation', 'sparkles-outline', 'ai', '/sdk/gemini', '/docs/apis/gemini'),
('GitHub API', 'github', 'Repository management, commits, and code operations', 'logo-github', 'development', '/sdk/github', '/docs/apis/github'),
('Telegram Bot API', 'telegram', 'Send messages, manage bots, and webhooks', 'paper-plane-outline', 'communication', '/sdk/telegram', '/docs/apis/telegram'),
('Discord API', 'discord', 'Discord bot and webhook messaging', 'logo-discord', 'communication', '/sdk/discord', '/docs/apis/discord'),
('SendGrid API', 'sendgrid', 'Email delivery, templates, and marketing', 'mail-outline', 'communication', '/sdk/sendgrid', '/docs/apis/sendgrid'),
('Stripe API', 'stripe', 'Payment processing, subscriptions, and billing', 'card-outline', 'payment', '/sdk/stripe', '/docs/apis/stripe'),
('Weather API', 'weather', 'Current weather, forecasts, and climate data', 'partly-sunny-outline', 'data', '/sdk/weather', '/docs/apis/weather'),
('News API', 'news', 'Latest news, headlines, and article search', 'newspaper-outline', 'data', '/sdk/news', '/docs/apis/news'),
('Currency API', 'currency', 'Exchange rates, currency conversion, and crypto prices', 'cash-outline', 'data', '/sdk/currency', '/docs/apis/currency'),
('QR Code API', 'qrcode', 'Generate QR codes for various data types', 'qr-code-outline', 'utility', '/sdk/qrcode', '/docs/apis/qrcode'),
('Geolocation API', 'geolocation', 'Location services, geocoding, and maps', 'location-outline', 'data', '/sdk/geolocation', '/docs/apis/geolocation');

-- Insert sample endpoints for User Management API
INSERT INTO `cms_api_endpoints` (`api_id`, `name`, `method`, `endpoint`, `description`, `parameters`, `response_schema`) VALUES
(1, 'Get All Users', 'GET', '/users', 'Retrieve all users with pagination', 
 JSON_OBJECT('page', JSON_OBJECT('type', 'integer', 'default', 1), 'limit', JSON_OBJECT('type', 'integer', 'default', 10)),
 JSON_OBJECT('users', 'array', 'total', 'integer', 'page', 'integer')),
(1, 'Get User by ID', 'GET', '/users/{id}', 'Retrieve a specific user by ID',
 JSON_OBJECT('id', JSON_OBJECT('type', 'integer', 'required', true)),
 JSON_OBJECT('user', 'object')),
(1, 'Create User', 'POST', '/users', 'Create a new user',
 JSON_OBJECT('email', JSON_OBJECT('type', 'string', 'required', true), 'name', JSON_OBJECT('type', 'string', 'required', true)),
 JSON_OBJECT('user', 'object', 'message', 'string')),
(1, 'Update User', 'PUT', '/users/{id}', 'Update an existing user',
 JSON_OBJECT('id', JSON_OBJECT('type', 'integer', 'required', true), 'email', JSON_OBJECT('type', 'string'), 'name', JSON_OBJECT('type', 'string')),
 JSON_OBJECT('user', 'object', 'message', 'string')),
(1, 'Delete User', 'DELETE', '/users/{id}', 'Delete a user',
 JSON_OBJECT('id', JSON_OBJECT('type', 'integer', 'required', true)),
 JSON_OBJECT('message', 'string'));

-- Insert sample endpoints for File Storage API  
INSERT INTO `cms_api_endpoints` (`api_id`, `name`, `method`, `endpoint`, `description`, `parameters`, `response_schema`) VALUES
(2, 'Upload File', 'POST', '/files/upload', 'Upload a new file',
 JSON_OBJECT('file', JSON_OBJECT('type', 'file', 'required', true), 'folder', JSON_OBJECT('type', 'string')),
 JSON_OBJECT('file', 'object', 'url', 'string')),
(2, 'Get File Info', 'GET', '/files/{id}', 'Get file information',
 JSON_OBJECT('id', JSON_OBJECT('type', 'string', 'required', true)),
 JSON_OBJECT('file', 'object')),
(2, 'Delete File', 'DELETE', '/files/{id}', 'Delete a file',
 JSON_OBJECT('id', JSON_OBJECT('type', 'string', 'required', true)),
 JSON_OBJECT('message', 'string'));
