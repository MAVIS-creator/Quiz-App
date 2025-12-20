<?php
/**
 * Migration to add detailed violation reasons
 * Run from command line: php migrate_violations_reasons.php
 */

require __DIR__ . '/../db.php';
$pdo = db();

try {
    // Check if reason column already exists
    $check = $pdo->query("SHOW COLUMNS FROM violations LIKE 'reason'");
    if (!$check->fetch()) {
        // Add the reason column if it doesn't exist
        $pdo->exec('ALTER TABLE violations ADD COLUMN reason VARCHAR(255) AFTER type');
        echo "✓ Added 'reason' column to violations table\n";
    } else {
        echo "✓ 'reason' column already exists\n";
    }

    // Create a mapping of violation reasons
    $reasons = [
        'tab-switch' => 'Switched Tabs',
        'fullscreen-exit' => 'Exited Fullscreen',
        'clipboard' => 'Clipboard Access Attempt',
        'suspicious-timing' => 'Suspicious Timing',
        'network-anomaly' => 'Network Anomaly',
        'cheating-detection' => 'AI/Cheating Detection',
        'multiple-clicks' => 'Rapid Multiple Clicks',
        'copy-paste' => 'Copy/Paste Attempt',
        'devtools' => 'Developer Tools Detected',
        'window-blur' => 'Window Lost Focus',
    ];

    echo "\n=== VIOLATION REASON TYPES ===\n";
    foreach ($reasons as $key => $label) {
        echo "- $key: $label\n";
    }

    echo "\nViolation migration completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    exit(1);
}
?>
