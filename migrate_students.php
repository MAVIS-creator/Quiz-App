<?php
require __DIR__ . '/db.php';

try {
    $pdo = db();
    
    // Create students table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            group_id INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_students_identifier (identifier),
            INDEX idx_students_group (group_id)
        ) ENGINE=InnoDB;
    ");
    
    // Create audio_clips table with file storage
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audio_clips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            duration INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audio_identifier (identifier)
        ) ENGINE=InnoDB;
    ");
    
    // Update snapshots table to use filename instead of dataURL
    $pdo->exec("
        ALTER TABLE snapshots ADD COLUMN IF NOT EXISTS filename VARCHAR(255) AFTER image
    ");
    
    echo "✅ Database migrations completed successfully.\n";
    echo "   • Created students table\n";
    echo "   • Created audio_clips table\n";
    echo "   • Updated snapshots table\n";
    
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
