-- Migration: Change projectID field from INT to VARCHAR(255) in project_api_subscriptions table
-- This migration is needed to support string-based project identifiers

-- Check if the table exists and show current structure
DESCRIBE project_api_subscriptions;

-- Start transaction for safe migration
START TRANSACTION;

-- Step 1: Add a temporary column with the new data type
ALTER TABLE project_api_subscriptions 
ADD COLUMN projectID_new VARCHAR(255) NOT NULL AFTER projectID;

-- Step 2: Copy data from old column to new column (converting INT to VARCHAR)
UPDATE project_api_subscriptions 
SET projectID_new = CAST(projectID AS CHAR);

-- Step 3: Drop the old column
ALTER TABLE project_api_subscriptions 
DROP COLUMN projectID;

-- Step 4: Rename the new column to the original name
ALTER TABLE project_api_subscriptions 
CHANGE COLUMN projectID_new projectID VARCHAR(255) NOT NULL;

-- Step 5: Recreate any indexes that were on the projectID column
-- (Add back any indexes that might have been dropped)
CREATE INDEX idx_project_api_subscriptions_projectid ON project_api_subscriptions(projectID);

-- Show the updated structure
DESCRIBE project_api_subscriptions;

-- Commit the transaction
COMMIT;

-- Verification query to check the migration
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'project_api_subscriptions' 
  AND COLUMN_NAME = 'projectID';
