<?php
require 'db.php';
$pdo = db();

// Check if sessions table has accuracy_score column
$stmt = $pdo->query("DESCRIBE sessions");
$columns = $stmt->fetchAll();

echo "=== Sessions Table Columns ===\n";
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Check if there's an accuracy_score or similar column
$hasAccuracy = false;
foreach ($columns as $col) {
    if (stripos($col['Field'], 'accuracy') !== false || stripos($col['Field'], 'score') !== false) {
        $hasAccuracy = true;
        echo "\n✓ Found accuracy column: " . $col['Field'] . "\n";
    }
}

if (!$hasAccuracy) {
    echo "\n✗ No accuracy column found\n";
}

// Get a submitted session and check what data it has
echo "\n=== Sample Submitted Session ===\n";
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE submitted = 1 LIMIT 1");
$stmt->execute();
$session = $stmt->fetch();

if ($session) {
    foreach ($session as $key => $value) {
        echo "$key: " . substr(json_encode($value), 0, 100) . "\n";
    }
}
?>
