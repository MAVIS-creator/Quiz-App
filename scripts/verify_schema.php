<?php
require __DIR__ . '/db.php';

try {
    $pdo = db();
    
    // Add group column to questions table if it doesn't exist
    $result = $pdo->query("SHOW COLUMNS FROM questions LIKE 'group'");
    if ($result->rowCount() === 0) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN `group` INT DEFAULT 1 AFTER answer");
        echo "✅ Added 'group' column to questions table\n";
    } else {
        echo "✅ 'group' column already exists on questions table\n";
    }
    
    // Add group column to sessions table if it doesn't exist
    $result = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'group'");
    if ($result->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN `group` INT DEFAULT 1 AFTER identifier");
        echo "✅ Added 'group' column to sessions table\n";
    } else {
        echo "✅ 'group' column already exists on sessions table\n";
    }

    echo "\n✅ All schema updates completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
