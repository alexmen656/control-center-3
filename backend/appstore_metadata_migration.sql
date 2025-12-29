-- Migration for App Store Metadata Module
-- Adds columns needed for App Store Connect API sync

-- Add appstore_version_id to versions table
ALTER TABLE appstore_app_versions 
ADD COLUMN IF NOT EXISTS appstore_version_id VARCHAR(255) DEFAULT NULL 
COMMENT 'App Store Connect Version ID' AFTER app_id;

-- Add last_synced_at to apps table
ALTER TABLE appstore_apps 
ADD COLUMN IF NOT EXISTS last_synced_at TIMESTAMP NULL DEFAULT NULL 
COMMENT 'Last sync with App Store Connect' AFTER status;

-- Add appstore_localization_id to app localizations
ALTER TABLE appstore_app_localizations 
ADD COLUMN IF NOT EXISTS appstore_localization_id VARCHAR(255) DEFAULT NULL 
COMMENT 'App Store Connect Localization ID' AFTER privacy_choices_url;

-- Add appstore_localization_id to version localizations
ALTER TABLE appstore_version_localizations 
ADD COLUMN IF NOT EXISTS appstore_localization_id VARCHAR(255) DEFAULT NULL 
COMMENT 'App Store Connect Localization ID' AFTER promotional_text;

-- Add index for faster lookups
ALTER TABLE appstore_app_versions ADD INDEX IF NOT EXISTS idx_appstore_version_id (appstore_version_id);
ALTER TABLE appstore_app_localizations ADD INDEX IF NOT EXISTS idx_appstore_loc_id (appstore_localization_id);
ALTER TABLE appstore_version_localizations ADD INDEX IF NOT EXISTS idx_appstore_vloc_id (appstore_localization_id);
