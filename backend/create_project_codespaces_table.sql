CREATE TABLE IF NOT EXISTS `project_codespaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'code-outline',
  `language` varchar(20) DEFAULT 'javascript',
  `template` varchar(50) DEFAULT 'default',
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `project_id` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug_per_project` (`project_id`, `slug`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `project_codespaces` (`name`, `slug`, `description`, `icon`, `language`, `template`, `project_id`, `user_id`, `order_index`)
SELECT 
  'Main Editor' as name,
  'main' as slug,
  'Main development environment' as description,
  'code-outline' as icon,
  'javascript' as language,
  'default' as template,
  `projectID` as project_id,
  1 as user_id,
  0 as order_index
FROM `projects`
WHERE NOT EXISTS (
  SELECT 1 FROM `project_codespaces` WHERE `project_id` = `projects`.`projectID`
);

--  CONSTRAINT `fk_codespace_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`projectID`) ON DELETE CASCADE
