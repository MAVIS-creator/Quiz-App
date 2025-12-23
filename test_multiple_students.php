<?php
require 'db.php';
$pdo = db();

echo "=== Testing Multiple Students ===\n\n";

$stmt = $pdo->query('SELECT identifier, name, answers_json, question_ids_json, accuracy_score FROM sessions WHERE submitted = 1 LIMIT 5');
while($s = $stmt->fetch()) {
    $qids = json_decode($s['question_ids_json'], true) ?? [];
    $answers = json_decode($s['answers_json'], true) ?? [];
    $answeredCount = count(array_filter($answers, fn($a) => $a !== null && $a !== ''));
    $acc = floatval($s['accuracy_score']);
    $correctCount = count($qids) > 0 ? round(($acc / 100) * count($qids)) : 0;
    
    echo $s['identifier'] . " ({$s['name']})\n";
    echo "  Accuracy: " . $acc . "%\n";
    echo "  Correct: " . $correctCount . "\n";
    echo "  Wrong: " . ($answeredCount - $correctCount) . "\n";
    echo "  Skipped: " . (count($qids) - $answeredCount) . "\n";
    echo "  Total: " . count($qids) . "\n\n";
}
?>
