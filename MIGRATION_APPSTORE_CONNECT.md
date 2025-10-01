# App Store Connect Module - Migration Instructions

## Changes Made

### 1. Charts Loading Issue - FIXED ✓
- Added proper canvas context retrieval with `getContext('2d')`
- Added better error handling for chart rendering
- Added delays with `$nextTick()` and `setTimeout()` to ensure DOM is ready
- Each chart now has individual try-catch blocks for better error isolation

### 2. Database Connection for App Selection - IMPLEMENTED ✓
- Created new table `appstore_connections` to store project-app relationships
- Created API endpoint `appstore_connections.php` with GET, POST, DELETE methods
- Updated ConfigView.vue to save/load from database
- Updated Modul1View.vue to load configuration from database first, then localStorage as fallback

### 3. Performance Optimization - FIXED ✓
- Apps list now loads with only 7 days of data instead of 30 days
- Implemented 6-hour cache for apps list
- Separated apps loading from downloads loading
- Cache file: `cache/apps_list_cache.json`

## Database Migration

Run the following SQL on your server:

```sql
CREATE TABLE IF NOT EXISTS `appstore_connections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `app_sku` VARCHAR(255) NOT NULL,
  `app_title` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_app` (`project_id`, `app_sku`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Or run the migration script:
```bash
cd backend
chmod +x migrate_appstore_connections.sh
./migrate_appstore_connections.sh
```

## Files Created/Modified

### New Files:
1. `backend/appstore_connections.php` - API for managing app connections
2. `backend/create_appstore_connections_table.sql` - SQL for table creation
3. `backend/migrate_appstore_connections.sh` - Shell script for migration

### Modified Files:
1. `backend/appstore_downloads.php`
   - Optimized `get_apps` endpoint to use 7-day period
   - Added 6-hour cache for apps list
   - Reduced unnecessary API calls

2. `src/modules/appstore-connect/components/Modul1View.vue`
   - Fixed chart rendering with proper canvas context
   - Added database loading for app selection
   - Improved error handling
   - Added delays to ensure DOM readiness

3. `src/modules/appstore-connect/components/ConfigView.vue`
   - Added database save/load functionality
   - Improved error handling
   - Better user feedback

## API Endpoints

### appstore_connections.php

**GET** - Get connection for current project
```
GET /backend/appstore_connections.php
Response: {
  "connection": { "app_sku": "...", "app_title": "..." },
  "has_connection": true
}
```

**POST** - Save connection
```
POST /backend/appstore_connections.php
Body: {
  "app_sku": "com.example.app",
  "app_title": "My App"
}
Response: {
  "success": true,
  "message": "Connection saved"
}
```

**DELETE** - Remove connection
```
DELETE /backend/appstore_connections.php
Response: {
  "success": true,
  "message": "Connection removed"
}
```

## Testing

1. **Clear cache to test fresh load:**
   - Delete `backend/cache/apps_list_cache.json`
   - Reload config page

2. **Test database connection:**
   - Go to config page
   - Select an app
   - Check database for new entry in `appstore_connections`

3. **Test charts:**
   - Go to main dashboard
   - Verify all 5 charts render correctly
   - Check browser console for any errors

## Performance Improvements

- **Before:** Apps list loaded with 30-day period (~30 API calls)
- **After:** Apps list loads with 7-day period (~7 API calls) + 6-hour cache
- **Result:** ~75% faster initial load for config page

## Troubleshooting

### Charts not rendering
- Check browser console for errors
- Verify Chart.js is loaded
- Check that canvas refs exist in DOM

### Database errors
- Verify table exists: `SHOW TABLES LIKE 'appstore_connections';`
- Check permissions: `SHOW GRANTS;`
- Verify PDO connection in `head.php`

### Cache issues
- Clear cache folder: `rm -f backend/cache/apps_list_cache.json`
- Verify cache directory exists and is writable: `chmod 755 backend/cache`
