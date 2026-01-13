-- Migration: Add section_id to form_settings for flexible section assignment
-- Forms can now be assigned to any custom section, mixed with tools

ALTER TABLE `form_settings` 
ADD COLUMN `section_id` INT DEFAULT NULL AFTER `project`,
ADD COLUMN `order_index` INT DEFAULT 0 AFTER `section_id`,
ADD COLUMN `icon` VARCHAR(100) DEFAULT 'list-outline' AFTER `order_index`,
ADD INDEX `idx_form_section` (`section_id`);

-- Note: We don't add a foreign key here because form_settings uses project name (string)
-- and project_sidebar_sections uses projectID (int). The relationship is handled in PHP.
