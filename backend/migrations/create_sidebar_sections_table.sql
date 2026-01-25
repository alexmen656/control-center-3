-- Migration: Create sidebar sections system
-- This allows users to create custom sections and assign tools to them

-- Create sidebar sections table
CREATE TABLE IF NOT EXISTS `project_sidebar_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `projectID` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `icon` VARCHAR(100) DEFAULT 'folder-outline',
    `order_index` INT DEFAULT 0,
    `is_default` BOOLEAN DEFAULT FALSE,
    `is_collapsible` BOOLEAN DEFAULT TRUE,
    `show_add_button` BOOLEAN DEFAULT TRUE,
    `add_button_route` VARCHAR(255) DEFAULT NULL,
    `info_route` VARCHAR(255) DEFAULT NULL,
    `manage_route` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_project_sections` (`projectID`),
    INDEX `idx_section_order` (`projectID`, `order_index`),
    UNIQUE KEY `unique_section_per_project` (`projectID`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add section_id column to project_tools
ALTER TABLE `project_tools` 
ADD COLUMN `section_id` INT DEFAULT NULL AFTER `projectID`,
ADD INDEX `idx_tool_section` (`section_id`),
ADD FOREIGN KEY (`section_id`) REFERENCES `project_sidebar_sections`(`id`) ON DELETE SET NULL;

-- Create default sections for existing projects (migrate existing tools to "Tools" section)
-- This will be handled by the PHP migration script

-- Insert default sections template (for new projects)
-- These are the built-in section types that will be created automatically
CREATE TABLE IF NOT EXISTS `sidebar_section_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `icon` VARCHAR(100) DEFAULT 'folder-outline',
    `default_order` INT DEFAULT 0,
    `description` TEXT,
    `is_system` BOOLEAN DEFAULT FALSE COMMENT 'If true, this section type is managed by the system (like Tables, APIs)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default section templates
INSERT INTO `sidebar_section_templates` (`name`, `slug`, `icon`, `default_order`, `description`, `is_system`) VALUES
('Tools', 'tools', 'construct-outline', 1, 'General tools and utilities', FALSE),
('Dashboards', 'dashboards', 'bar-chart-outline', 2, 'Analytics and reporting dashboards', FALSE),
('Integrations', 'integrations', 'git-branch-outline', 3, 'Third-party integrations and connections', FALSE),
('Automation', 'automation', 'flash-outline', 4, 'Automated workflows and tasks', FALSE),
('Settings', 'settings', 'settings-outline', 99, 'Project settings and configuration', FALSE)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
