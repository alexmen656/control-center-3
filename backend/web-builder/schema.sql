-- =============================================
-- Web Builder Module Database Schema
-- For Control Center Integration
-- =============================================
-- 
-- This schema uses Control Center's existing user system
-- (control_center_users table) for authentication.
-- No separate users table needed.
--
-- Run this on the Control Center database
-- =============================================

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS `control_center_modul_web_builder_components`;
DROP TABLE IF EXISTS `control_center_modul_web_builder_pages`;
DROP TABLE IF EXISTS `control_center_modul_web_builder_projects`;

-- =============================================
-- Projects table
-- =============================================
-- Stores web builder projects linked to Control Center projects
-- MUST be linked to an existing Control Center project (projects.projectID)
CREATE TABLE `control_center_modul_web_builder_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'References control_center_users.userID',
  `project_id` varchar(255) NOT NULL COMMENT 'References projects.projectID - REQUIRED link to Control Center project',
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Pages table
-- =============================================
-- Stores pages within each project
CREATE TABLE `control_center_modul_web_builder_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_home` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_project_slug` (`project_id`, `slug`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_is_home` (`is_home`),
  CONSTRAINT `fk_pages_project` FOREIGN KEY (`project_id`) 
    REFERENCES `control_center_modul_web_builder_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Components table
-- =============================================
-- Stores the actual page builder components (HTML blocks)
CREATE TABLE `control_center_modul_web_builder_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `component_id` varchar(36) NOT NULL COMMENT 'UUID of the component from frontend',
  `html_code` mediumtext NOT NULL COMMENT 'The HTML content of the component',
  `position` int(11) NOT NULL DEFAULT 0 COMMENT 'Order position on the page',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_id` (`page_id`),
  KEY `idx_position` (`position`),
  KEY `idx_component_id` (`component_id`),
  CONSTRAINT `fk_components_page` FOREIGN KEY (`page_id`) 
    REFERENCES `control_center_modul_web_builder_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Migration: Rename existing tables (if needed)
-- =============================================
-- Uncomment and run these if you have existing data to migrate:
--
-- RENAME TABLE `control_center_web_builder_projects` TO `control_center_modul_web_builder_projects`;
-- RENAME TABLE `control_center_web_builder_pages` TO `control_center_modul_web_builder_pages`;
-- RENAME TABLE `control_center_web_builder_components` TO `control_center_modul_web_builder_components`;
