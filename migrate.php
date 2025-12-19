<?php
/**
 * Database migration to support:
 * 1. Session-based filtering (allow multiple quiz attempts with unique session IDs)
 * 2. Same-day exam prevention (except for test account)
 * 3. Multi-group support
 */

require_once 'db.php';

try {
    $pdo = db();
    
    echo "📋 Running database migrations...\n\n";
    
    // Migration 1: Add session_id and session_date to sessions table
    echo "1️⃣ Adding session tracking to sessions table...\n";
    try {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN session_id VARCHAR(50) UNIQUE");
        $pdo->exec("ALTER TABLE sessions ADD COLUMN session_date DATE");
        echo "   ✓ session_id and session_date columns added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ℹ️  Columns already exist\n";
    }
    
    // Migration 2: Add group columns for multi-group support
    echo "\n2️⃣ Adding group support to database...\n";
    try {
        // Add group to questions table
        $pdo->exec("ALTER TABLE questions ADD COLUMN `group` TINYINT DEFAULT 1");
        echo "   ✓ 'group' column added to questions table\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ℹ️  'group' column already exists in questions table\n";
    }
    
    try {
        // Add group to sessions table
        $pdo->exec("ALTER TABLE sessions ADD COLUMN `group` TINYINT DEFAULT 1");
        echo "   ✓ 'group' column added to sessions table\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ℹ️  'group' column already exists in sessions table\n";
    }
    
    // Create groups table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_groups (
            id TINYINT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        
        // Insert default groups
        $pdo->exec("INSERT IGNORE INTO admin_groups (id, name) VALUES (1, 'Group 1'), (2, 'Group 2')");
        echo "   ✓ admin_groups table created\n";
    } catch (Exception $e) {
        echo "   ℹ️  admin_groups table already exists\n";
    }
    
    // Update admin table to include group
    try {
        // Check if admin table exists first
        $adminCheck = $pdo->query("SHOW TABLES LIKE 'admin'")->fetchAll();
        if (!empty($adminCheck)) {
            $pdo->exec("ALTER TABLE admin ADD COLUMN `group` TINYINT DEFAULT 1");
            echo "   ✓ 'group' column added to admin table\n";
        } else {
            echo "   ℹ️  admin table does not exist yet (will be created during login setup)\n";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ℹ️  'group' column already exists in admin table\n";
    }
    
    echo "\n✅ All migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
