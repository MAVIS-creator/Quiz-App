<?php
require_once 'db.php';

try {
    $pdo = db();
    
    // Check if config exists
    $cfg = $pdo->query('SELECT * FROM config WHERE id=1')->fetch();
    
    if ($cfg) {
        echo "✓ Config exists: Questions: {$cfg['question_count']}, Duration: {$cfg['exam_minutes']} min\n";
    } else {
        echo "✗ Config not found. Creating default...\n";
        $pdo->exec('INSERT INTO config (id, exam_minutes, question_count) VALUES (1, 60, 40)');
        echo "✓ Default config created\n";
    }
    
    // Check snapshots table exists
    $snapTables = $pdo->query("SHOW TABLES LIKE 'snapshots'")->fetchAll();
    if (!empty($snapTables)) {
        echo "✓ Snapshots table exists\n";
    } else {
        echo "✗ Snapshots table missing - creating...\n";
        $pdo->exec("CREATE TABLE snapshots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            image LONGBLOB NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        echo "✓ Snapshots table created\n";
    }
    
    // Check audio_clips table exists
    $audioTables = $pdo->query("SHOW TABLES LIKE 'audio_clips'")->fetchAll();
    if (!empty($audioTables)) {
        echo "✓ Audio clips table exists\n";
    } else {
        echo "✗ Audio clips table missing - creating...\n";
        $pdo->exec("CREATE TABLE audio_clips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            audio_data LONGBLOB NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        echo "✓ Audio clips table created\n";
    }
    
    echo "\n✓ All database checks passed!\n";
    
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
?>
