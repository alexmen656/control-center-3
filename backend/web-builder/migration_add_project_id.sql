-- =============================================
-- Migration Script: Add project_id to Web Builder
-- =============================================
-- This script adds the project_id column to link 
-- Web Builder projects to Control Center projects
-- =============================================

-- Step 1: Add project_id column (nullable initially for migration)
ALTER TABLE `control_center_modul_web_builder_projects` 
ADD COLUMN `project_id` varchar(255) NULL COMMENT 'References projects.projectID - REQUIRED link to Control Center project' 
AFTER `user_id`;

-- Step 2: MANUAL STEP - Update existing rows
-- You need to manually assign project_ids to existing web builder projects
-- Example:
-- UPDATE control_center_modul_web_builder_projects 
-- SET project_id = 'your-project-id' 
-- WHERE id = 1;

-- Uncomment and run this query to see existing projects that need migration:
-- SELECT id, user_id, name, description, created_at 
-- FROM control_center_modul_web_builder_projects 
-- WHERE project_id IS NULL;

-- Step 3: After all projects have a project_id, make the column NOT NULL
-- ALTER TABLE `control_center_modul_web_builder_projects` 
-- MODIFY COLUMN `project_id` varchar(255) NOT NULL COMMENT 'References projects.projectID - REQUIRED link to Control Center project';

-- Step 4: Add unique index to ensure one web builder project per CC project
-- CREATE UNIQUE INDEX `idx_project_id` ON `control_center_modul_web_builder_projects` (`project_id`);

-- =============================================
-- Verification Queries
-- =============================================

-- Check if any projects still need project_id assignment:
-- SELECT wb.id, wb.name, wb.user_id, wb.project_id,
--        (SELECT COUNT(*) FROM control_center_user_projects up WHERE up.userID = wb.user_id) as user_project_count
-- FROM control_center_modul_web_builder_projects wb
-- WHERE wb.project_id IS NULL;

-- Show all web builder projects with their CC project info:
-- SELECT wb.id, wb.name as wb_name, wb.project_id, 
--        p.name as cc_project_name, p.link as cc_project_link
-- FROM control_center_modul_web_builder_projects wb
-- LEFT JOIN projects p ON wb.project_id = p.projectID;
