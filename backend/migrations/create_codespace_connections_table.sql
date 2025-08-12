-- Tabelle für GitHub Repository Verbindungen zu Codespaces
CREATE TABLE IF NOT EXISTS `codespace_github_repos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codespace_id` int(11) NOT NULL,
  `repo_id` varchar(50) NOT NULL,
  `repo_name` varchar(255) NOT NULL,
  `repo_full_name` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_repo_per_codespace` (`codespace_id`),
  KEY `idx_codespace_id` (`codespace_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`codespace_id`) REFERENCES `project_codespaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Vercel Project Verbindungen zu Codespaces  
CREATE TABLE IF NOT EXISTS `codespace_vercel_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codespace_id` int(11) NOT NULL,
  `vercel_project_id` varchar(100) NOT NULL,
  `vercel_project_name` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vercel_per_codespace` (`codespace_id`),
  KEY `idx_codespace_id` (`codespace_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`codespace_id`) REFERENCES `project_codespaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Domain Verbindungen zu Codespaces
CREATE TABLE IF NOT EXISTS `codespace_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codespace_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_domain` (`domain`),
  UNIQUE KEY `unique_main_per_project` (`codespace_id`, `is_main`),
  KEY `idx_codespace_id` (`codespace_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_main` (`is_main`),
  FOREIGN KEY (`codespace_id`) REFERENCES `project_codespaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
