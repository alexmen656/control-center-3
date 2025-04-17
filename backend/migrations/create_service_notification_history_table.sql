CREATE TABLE IF NOT EXISTS `service_notification_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_id` INT NOT NULL,
  `project_id` VARCHAR(255) NOT NULL,
  `last_notification_time` DATETIME NOT NULL,
  `downtime_minutes` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `service_project_unique` (`service_id`, `project_id`),
  INDEX `idx_service_id` (`service_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_last_notification` (`last_notification_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;