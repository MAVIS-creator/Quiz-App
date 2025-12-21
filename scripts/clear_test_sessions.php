<?php
// Clear test sessions from database
require __DIR__ . '/../db.php';

$pattern = $argv[1] ?? 'TEST%';

echo "=== Clear Test Sessions ===\n\n";
echo "Pattern: $pattern\n";

try {
    $pdo = db();
    
    // Count sessions to delete
    $count = $pdo->prepare('SELECT COUNT(*) FROM sessions WHERE identifier LIKE ?');
    $count->execute([$pattern]);
    $total = $count->fetchColumn();
    
    if ($total == 0) {
        echo "No sessions found matching pattern '$pattern'\n";
        exit(0);
    }
    
    echo "Found $total session(s) to delete\n";
    
    // Show what will be deleted
    $list = $pdo->prepare('SELECT identifier, name, created_at, submitted FROM sessions WHERE identifier LIKE ? ORDER BY created_at DESC');
    $list->execute([$pattern]);
    $sessions = $list->fetchAll();
    
    echo "\nSessions to be deleted:\n";
    foreach ($sessions as $s) {
        $status = $s['submitted'] ? 'Submitted' : 'In Progress';
        echo "  - {$s['identifier']} ({$s['name']}) - {$s['created_at']} - $status\n";
    }
    
    echo "\nAre you sure you want to delete these sessions? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "Cancelled.\n";
        exit(0);
    }
    
    // Delete sessions
    $delete = $pdo->prepare('DELETE FROM sessions WHERE identifier LIKE ?');
    $delete->execute([$pattern]);
    
    echo "\n✅ Deleted $total session(s)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
