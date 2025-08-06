<?php
/**
 * Migration Script: Change projectID from INT to VARCHAR(255)
 * 
 * This script migrates the project_api_subscriptions table to use VARCHAR(255) 
 * for projectID instead of INT to support string-based project identifiers.
 */

include_once 'head.php';

echo "=== Project API Subscriptions Migration ===\n";
echo "Changing projectID from INT to VARCHAR(255)\n\n";

try {
    // Check if table exists
    $tableExists = query("SHOW TABLES LIKE 'project_api_subscriptions'");
    if (mysqli_num_rows($tableExists) == 0) {
        echo "❌ Table 'project_api_subscriptions' does not exist!\n";
        exit(1);
    }
    
    echo "✅ Table 'project_api_subscriptions' found\n";
    
    // Show current structure
    echo "\n📋 Current table structure:\n";
    $currentStructure = query("DESCRIBE project_api_subscriptions");
    while ($row = mysqli_fetch_assoc($currentStructure)) {
        if ($row['Field'] == 'projectID') {
            echo "   projectID: {$row['Type']} (Current)\n";
            
            // Check if it's already VARCHAR
            if (strpos($row['Type'], 'varchar') !== false) {
                echo "✅ projectID is already VARCHAR - no migration needed!\n";
                exit(0);
            }
        }
    }
    
    // Start migration
    echo "\n🔄 Starting migration...\n";
    
    // Begin transaction
    query("START TRANSACTION");
    echo "   ✓ Transaction started\n";
    
    // Step 1: Add temporary column
    $result1 = query("ALTER TABLE project_api_subscriptions ADD COLUMN projectID_new VARCHAR(255) NOT NULL AFTER projectID");
    if (!$result1) {
        throw new Exception("Failed to add temporary column: " . mysqli_error($GLOBALS['con']));
    }
    echo "   ✓ Temporary column 'projectID_new' added\n";
    
    // Step 2: Copy data
    $result2 = query("UPDATE project_api_subscriptions SET projectID_new = CAST(projectID AS CHAR)");
    if (!$result2) {
        throw new Exception("Failed to copy data: " . mysqli_error($GLOBALS['con']));
    }
    echo "   ✓ Data copied to new column\n";
    
    // Step 3: Drop old column
    $result3 = query("ALTER TABLE project_api_subscriptions DROP COLUMN projectID");
    if (!$result3) {
        throw new Exception("Failed to drop old column: " . mysqli_error($GLOBALS['con']));
    }
    echo "   ✓ Old column 'projectID' dropped\n";
    
    // Step 4: Rename new column
    $result4 = query("ALTER TABLE project_api_subscriptions CHANGE COLUMN projectID_new projectID VARCHAR(255) NOT NULL");
    if (!$result4) {
        throw new Exception("Failed to rename column: " . mysqli_error($GLOBALS['con']));
    }
    echo "   ✓ Column renamed to 'projectID'\n";
    
    // Step 5: Add index
    $result5 = query("CREATE INDEX idx_project_api_subscriptions_projectid ON project_api_subscriptions(projectID)");
    if (!$result5) {
        // Index creation is not critical, just warn
        echo "   ⚠️  Warning: Could not create index (may already exist)\n";
    } else {
        echo "   ✓ Index created on projectID\n";
    }
    
    // Commit transaction
    query("COMMIT");
    echo "   ✓ Transaction committed\n";
    
    // Verify migration
    echo "\n🔍 Verifying migration...\n";
    $verification = query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_api_subscriptions' AND COLUMN_NAME = 'projectID'");
    
    if ($row = mysqli_fetch_assoc($verification)) {
        echo "   projectID: {$row['DATA_TYPE']}({$row['CHARACTER_MAXIMUM_LENGTH']})\n";
        
        if ($row['DATA_TYPE'] == 'varchar' && $row['CHARACTER_MAXIMUM_LENGTH'] == 255) {
            echo "\n✅ Migration completed successfully!\n";
            echo "   projectID is now VARCHAR(255)\n";
        } else {
            throw new Exception("Migration verification failed - unexpected data type");
        }
    } else {
        throw new Exception("Migration verification failed - column not found");
    }
    
} catch (Exception $e) {
    // Rollback on error
    query("ROLLBACK");
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   Transaction rolled back\n";
    exit(1);
}

echo "\n🎉 Migration completed successfully!\n";
echo "The project_api_subscriptions table now uses VARCHAR(255) for projectID.\n";
?>
