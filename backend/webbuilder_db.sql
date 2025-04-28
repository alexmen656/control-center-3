-- Database creation for Web Builder
CREATE DATABASE IF NOT EXISTS `webbuilder_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `webbuilder_db`;

-- Drop tables if exist
DROP TABLE IF EXISTS `control_center_web_builder_components`;
DROP TABLE IF EXISTS `control_center_web_builder_pages`;
DROP TABLE IF EXISTS `control_center_web_builder_projects`;
DROP TABLE IF EXISTS `control_center_web_builder_users`;
DROP TABLE IF EXISTS `control_center_web_builder_templates`;

-- Users table
CREATE TABLE `control_center_web_builder_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects table
CREATE TABLE `control_center_web_builder_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `control_center_web_builder_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pages table
CREATE TABLE `control_center_web_builder_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_home` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id_slug` (`project_id`, `slug`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `control_center_web_builder_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Templates table (stores original component templates)
CREATE TABLE `control_center_web_builder_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `html_code` mediumtext NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Components table (stores the actual page builder components)
CREATE TABLE `control_center_web_builder_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `component_id` varchar(36) NOT NULL COMMENT 'UUID of the component',
  `original_template_id` int(11) DEFAULT NULL COMMENT 'Reference to the original template',
  `html_code` mediumtext NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `position` (`position`),
  KEY `original_template_id` (`original_template_id`),
  CONSTRAINT `components_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `control_center_web_builder_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `components_ibfk_2` FOREIGN KEY (`original_template_id`) REFERENCES `control_center_web_builder_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default user for testing
INSERT INTO `control_center_web_builder_users` (`username`, `password`, `email`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Insert a default project
INSERT INTO `control_center_web_builder_projects` (`user_id`, `name`, `description`) VALUES 
(1, 'Demo Website', 'A demonstration website created with the web builder');

-- Insert a default home page
INSERT INTO `control_center_web_builder_pages` (`project_id`, `name`, `slug`, `title`, `meta_description`, `is_home`) VALUES 
(1, 'Home', 'home', 'Welcome to My Website', 'This is the homepage of my website created with Web Builder', 1);