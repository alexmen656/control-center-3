-- CMS APIs - Die APIs die das CMS bereitstellt
CREATE TABLE IF NOT EXISTS `cms_apis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL UNIQUE,
    `description` text,
    `icon` varchar(100) DEFAULT 'code-outline',
    `category` varchar(100) DEFAULT 'general', -- user, file, database, auth, notification, etc.
    `version` varchar(20) DEFAULT '1.0',
    `endpoint_base` varchar(255) NOT NULL, -- z.B. '/api/v1/users'
    `auth_required` boolean DEFAULT TRUE,
    `rate_limit_default` int(11) DEFAULT 100, -- default requests per minute
    `documentation_url` text,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

-- API Endpoints für jede CMS API
CREATE TABLE IF NOT EXISTS `cms_api_endpoints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `api_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `method` varchar(10) NOT NULL DEFAULT 'GET',
    `endpoint` varchar(255) NOT NULL, -- z.B. '/list', '/create', '/{id}'
    `description` text,
    `parameters` json, -- Input parameter schema
    `response_schema` json, -- Expected response schema
    `example_request` json,
    `example_response` json,
    `requires_auth` boolean DEFAULT TRUE,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`api_id`) REFERENCES `cms_apis`(`id`) ON DELETE CASCADE
);

-- Projekt API Subscriptions - Welche APIs ein Projekt nutzt
CREATE TABLE IF NOT EXISTS `project_api_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `projectID` int(11) NOT NULL,
    `api_id` int(11) NOT NULL,
    `api_key` varchar(255) NOT NULL UNIQUE, -- Unique key for this project-api combination
    `rate_limit` int(11) DEFAULT 100, -- requests per minute for this project
    `is_enabled` boolean DEFAULT TRUE,
    `usage_count` int(11) DEFAULT 0, -- Total usage counter
    `last_used` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`projectID`) REFERENCES `projects`(`projectID`) ON DELETE CASCADE,
    FOREIGN KEY (`api_id`) REFERENCES `cms_apis`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_project_api` (`projectID`, `api_id`)
);

-- API Usage Logs für Monitoring und Analytics
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subscription_id` int(11) NOT NULL,
    `endpoint_id` int(11),
    `request_method` varchar(10),
    `request_path` varchar(255),
    `request_ip` varchar(45),
    `response_status` int(11),
    `response_time_ms` int(11),
    `error_message` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`subscription_id`) REFERENCES `project_api_subscriptions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`endpoint_id`) REFERENCES `cms_api_endpoints`(`id`) ON DELETE SET NULL,
    INDEX `idx_subscription_date` (`subscription_id`, `created_at`),
    INDEX `idx_created_at` (`created_at`)
);

-- Beispiel-APIs einfügen
INSERT IGNORE INTO `cms_apis` (`name`, `slug`, `description`, `icon`, `category`, `endpoint_base`, `documentation_url`) VALUES
('User Management API', 'user-management', 'Create, read, update and delete users in your project', 'people-outline', 'user', '/api/v1/users', '/docs/apis/user-management'),
('File Storage API', 'file-storage', 'Upload, manage and serve files for your project', 'folder-outline', 'file', '/api/v1/files', '/docs/apis/file-storage'),
('Database API', 'database', 'Direct database operations for your project data', 'server-outline', 'database', '/api/v1/database', '/docs/apis/database'),
('Authentication API', 'authentication', 'Handle user login, registration and sessions', 'lock-closed-outline', 'auth', '/api/v1/auth', '/docs/apis/authentication'),
('Notification API', 'notifications', 'Send push notifications and emails', 'notifications-outline', 'notification', '/api/v1/notifications', '/docs/apis/notifications'),
('Analytics API', 'analytics', 'Track events and generate reports', 'analytics-outline', 'analytics', '/api/v1/analytics', '/docs/apis/analytics');

-- Beispiel-Endpoints für User Management API
INSERT IGNORE INTO `cms_api_endpoints` (`api_id`, `name`, `method`, `endpoint`, `description`, `parameters`, `response_schema`, `example_request`, `example_response`) VALUES
(1, 'List Users', 'GET', '/list', 'Get all users with optional filtering', 
 '{"limit": {"type": "integer", "default": 10}, "offset": {"type": "integer", "default": 0}, "search": {"type": "string", "optional": true}}',
 '{"users": {"type": "array"}, "total": {"type": "integer"}, "pagination": {"type": "object"}}',
 '{"limit": 10, "search": "john"}',
 '{"users": [{"id": 1, "name": "John Doe", "email": "john@example.com"}], "total": 1, "pagination": {"limit": 10, "offset": 0}}'),
(1, 'Create User', 'POST', '/create', 'Create a new user', 
 '{"name": {"type": "string", "required": true}, "email": {"type": "string", "required": true}, "password": {"type": "string", "required": true}}',
 '{"user": {"type": "object"}, "success": {"type": "boolean"}}',
 '{"name": "Jane Doe", "email": "jane@example.com", "password": "secret123"}',
 '{"user": {"id": 2, "name": "Jane Doe", "email": "jane@example.com"}, "success": true}'),
(1, 'Get User', 'GET', '/{id}', 'Get user by ID', 
 '{"id": {"type": "integer", "required": true}}',
 '{"user": {"type": "object"}}',
 '{}',
 '{"user": {"id": 1, "name": "John Doe", "email": "john@example.com", "created_at": "2025-01-01T00:00:00Z"}}'),
(1, 'Update User', 'PUT', '/{id}', 'Update user information', 
 '{"id": {"type": "integer", "required": true}, "name": {"type": "string"}, "email": {"type": "string"}}',
 '{"user": {"type": "object"}, "success": {"type": "boolean"}}',
 '{"name": "John Smith", "email": "johnsmith@example.com"}',
 '{"user": {"id": 1, "name": "John Smith", "email": "johnsmith@example.com"}, "success": true}'),
(1, 'Delete User', 'DELETE', '/{id}', 'Delete user by ID', 
 '{"id": {"type": "integer", "required": true}}',
 '{"success": {"type": "boolean"}, "message": {"type": "string"}}',
 '{}',
 '{"success": true, "message": "User deleted successfully"}');

-- Beispiel-Endpoints für File Storage API
INSERT IGNORE INTO `cms_api_endpoints` (`api_id`, `name`, `method`, `endpoint`, `description`, `parameters`, `response_schema`, `example_request`, `example_response`) VALUES
(2, 'Upload File', 'POST', '/upload', 'Upload a file to the project storage', 
 '{"file": {"type": "file", "required": true}, "folder": {"type": "string", "optional": true}}',
 '{"file": {"type": "object"}, "success": {"type": "boolean"}}',
 '{"folder": "documents"}',
 '{"file": {"id": "file123", "name": "document.pdf", "url": "/files/document.pdf", "size": 1024}, "success": true}'),
(2, 'List Files', 'GET', '/list', 'Get all files in the project', 
 '{"folder": {"type": "string", "optional": true}, "limit": {"type": "integer", "default": 50}}',
 '{"files": {"type": "array"}, "total": {"type": "integer"}}',
 '{"folder": "images", "limit": 10}',
 '{"files": [{"id": "img1", "name": "photo.jpg", "url": "/files/photo.jpg"}], "total": 1}'),
(2, 'Delete File', 'DELETE', '/{fileId}', 'Delete a file by ID', 
 '{"fileId": {"type": "string", "required": true}}',
 '{"success": {"type": "boolean"}, "message": {"type": "string"}}',
 '{}',
 '{"success": true, "message": "File deleted successfully"}');
