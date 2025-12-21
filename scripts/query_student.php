<?php
require __DIR__ . '/../db.php';

$identifier = $argv[1] ?? null;
if (!$identifier) {
    echo "Usage: php scripts/query_student.php <identifier-or-phone>\n";
    exit(1);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT identifier, name, phone, group_id FROM students WHERE UPPER(identifier) = UPPER(?) OR phone = ? LIMIT 5');
    $stmt->execute([$identifier, $identifier]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        echo "No student found for input: $identifier\n";
    } else {
        foreach ($rows as $r) {
            echo "Found: identifier=" . $r['identifier'] . ", name=" . $r['name'] . ", phone=" . ($r['phone'] ?? '') . ", group_id=" . ($r['group_id'] ?? 'null') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
