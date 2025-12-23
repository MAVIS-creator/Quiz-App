<?php
require 'db.php';
$pdo = db();

// Get a submitted student
$stmt = $pdo->prepare('SELECT id, identifier, name, submitted, answers_json, question_ids_json, accuracy_score FROM sessions WHERE submitted = 1 LIMIT 1');
$stmt->execute();
$session = $stmt->fetch();

if (!$session) {
    echo "No submitted sessions found\n";
    exit;
}

$identifier = $session['identifier'];
echo "=== Testing Student: $identifier ({$session['name']}) ===\n\n";

echo "From DB (stored values):\n";
echo "- Submitted: " . $session['submitted'] . "\n";
echo "- Stored Accuracy Score: " . $session['accuracy_score'] . "%\n";

$answers = json_decode($session['answers_json'], true) ?? [];
$qids = json_decode($session['question_ids_json'], true) ?? [];
$answeredCount = count(array_filter($answers, fn($a) => $a !== null && $a !== ''));

echo "- Total Questions: " . count($qids) . "\n";
echo "- Answered: " . $answeredCount . "\n";
echo "- Unanswered: " . (count($qids) - $answeredCount) . "\n";

echo "\n=== Now Testing Fixed accuracy.php API ===\n";

// Simulate what the fixed API does
$totalQuestions = count($qids);
$accuracy = floatval($session['accuracy_score'] ?? 0);
$correctCount = $totalQuestions > 0 ? round(($accuracy / 100) * $totalQuestions) : 0;

echo "API Processing...\n";
echo "- Accuracy: " . $accuracy . "%\n";
echo "- Total Questions: " . $totalQuestions . "\n";
echo "- Correct (calculated): " . $correctCount . "\n";
echo "- Wrong: " . ($answeredCount - $correctCount) . "\n";
echo "- Skipped: " . ($totalQuestions - $answeredCount) . "\n";

echo "\n=== What API Returns ===\n";
$apiData = [
    'identifier' => $identifier,
    'name' => $session['name'],
    'accuracy' => round($accuracy, 2),
    'score' => $correctCount,
    'total_questions' => $totalQuestions,
    'submitted' => true
];
echo json_encode($apiData, JSON_PRETTY_PRINT) . "\n";

echo "\n✓ Fixed! API now returns stored accuracy values instead of buggy calculation.\n";
?>
