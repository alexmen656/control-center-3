-- Create service logs table
CREATE TABLE IF NOT EXISTS `control_center_services_logs` (
  `id` CHAR(36) NOT NULL PRIMARY KEY COMMENT 'UUID for the log entry',
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the log was created',
  `project_id` VARCHAR(64) NOT NULL COMMENT 'Project ID the log belongs to',
  `api_key` VARCHAR(128) NULL COMMENT 'API key used to create the log, if any',
  `service` VARCHAR(64) NOT NULL COMMENT 'Service identifier/name',
  `message` TEXT NOT NULL COMMENT 'Log message text',
  `type` ENUM('info', 'warn', 'error', 'success') NOT NULL DEFAULT 'info' COMMENT 'Type of log entry',
  `data` JSON NULL COMMENT 'Additional structured data for the log entry',
  `environment` VARCHAR(32) NULL COMMENT 'Environment (dev, staging, prod, etc)',
  `user_id` VARCHAR(64) NULL COMMENT 'User ID if the log was created by an authenticated user',
  `ip_address` VARCHAR(45) NULL COMMENT 'IP address where the log originated from',
  `meta` JSON NULL COMMENT 'Additional metadata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better query performance
CREATE INDEX IF NOT EXISTS `idx_servicelogs_project` ON `control_center_services_logs` (`project_id`);
CREATE INDEX IF NOT EXISTS `idx_servicelogs_timestamp` ON `control_center_services_logs` (`timestamp`);
CREATE INDEX IF NOT EXISTS `idx_servicelogs_service` ON `control_center_services_logs` (`service`);
CREATE INDEX IF NOT EXISTS `idx_servicelogs_type` ON `control_center_services_logs` (`type`);