-- Migration: Add dirty flag system for efficient sync
-- Only push localizations that have been modified since last sync

-- Add dirty flag and last_synced_at to app localizations
ALTER TABLE appstore_app_localizations 
ADD COLUMN IF NOT EXISTS is_dirty TINYINT(1) DEFAULT 1 COMMENT 'Flag to track if localization needs to be pushed',
ADD COLUMN IF NOT EXISTS last_synced_at TIMESTAMP NULL COMMENT 'Last successful sync to App Store Connect';

-- Add dirty flag and last_synced_at to version localizations  
ALTER TABLE appstore_version_localizations
ADD COLUMN IF NOT EXISTS is_dirty TINYINT(1) DEFAULT 1 COMMENT 'Flag to track if localization needs to be pushed',
ADD COLUMN IF NOT EXISTS last_synced_at TIMESTAMP NULL COMMENT 'Last successful sync to App Store Connect';

-- Create indexes for efficient dirty queries
CREATE INDEX IF NOT EXISTS idx_app_loc_dirty ON appstore_app_localizations(is_dirty);
CREATE INDEX IF NOT EXISTS idx_version_loc_dirty ON appstore_version_localizations(is_dirty);

-- Mark all existing localizations as dirty (need initial sync)
UPDATE appstore_app_localizations SET is_dirty = 1 WHERE is_dirty IS NULL;
UPDATE appstore_version_localizations SET is_dirty = 1 WHERE is_dirty IS NULL;
