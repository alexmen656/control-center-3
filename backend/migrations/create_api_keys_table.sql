-- Create API keys table
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `api_key` VARCHAR(128) NOT NULL UNIQUE,
  `name` VARCHAR(64) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,
  `last_used_at` DATETIME NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `project_id` VARCHAR(64) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `permissions` JSON NULL COMMENT 'JSON array of permissions for this key'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better query performance
CREATE INDEX `idx_api_keys_user` ON `api_keys` (`user_id`);
CREATE INDEX `idx_api_keys_project` ON `api_keys` (`project_id`);
CREATE INDEX `idx_api_keys_active` ON `api_keys` (`active`);