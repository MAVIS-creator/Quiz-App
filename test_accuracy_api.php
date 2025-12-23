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

echo "From DB:\n";
echo "- Submitted: " . $session['submitted'] . "\n";
echo "- Stored Accuracy: " . $session['accuracy_score'] . "%\n";

$answers = json_decode($session['answers_json'], true) ?? [];
$qids = json_decode($session['question_ids_json'], true) ?? [];
$answeredCount = count(array_filter($answers, fn($a) => $a !== null && $a !== ''));

echo "- Total Questions: " . count($qids) . "\n";
echo "- Answered: " . $answeredCount . "\n";
echo "- Unanswered: " . (count($qids) - $answeredCount) . "\n";

echo "\nFirst few answers:\n";
foreach(array_slice($answers, 0, 5) as $qid => $ans) {
    echo "  Q$qid: " . json_encode($ans) . "\n";
}

echo "\n=== Now Testing accuracy.php API ===\n";
// Simulate what accuracy.php does
$query = 'SELECT s.*, (SELECT COUNT(*) FROM violations WHERE identifier = s.identifier) as violation_count FROM sessions s WHERE s.identifier = ?';
$stmt = $pdo->prepare($query);
$stmt->execute([$identifier]);
$student = $stmt->fetch();

if (!$student['submitted']) {
    echo "Student not submitted\n";
    exit;
}

$answers = json_decode($student['answers_json'], true) ?? [];
$questionIds = json_decode($student['question_ids_json'], true) ?? [];
$correctCount = 0;
$totalQuestions = count($questionIds);

echo "Processing $totalQuestions questions...\n";

foreach ($questionIds as $qid) {
    $qStmt = $pdo->prepare('SELECT option_a, option_b, option_c, option_d, answer FROM questions WHERE id = ?');
    $qStmt->execute([$qid]);
    $qRow = $qStmt->fetch();
    
    if (!$qRow) {
        echo "  Q$qid: NOT FOUND IN DB\n";
        continue;
    }

    $correctField = trim($qRow['answer'] ?? '');
    $studentAnswer = $answers[$qid] ?? '';
    
    // Check if correct (simple comparison)
    $isCorrect = strcasecmp($studentAnswer, $correctField) === 0;
    
    if ($isCorrect) {
        $correctCount++;
        echo "  Q$qid: ✓ Correct (Student: '$studentAnswer' = DB: '$correctField')\n";
    } else {
        echo "  Q$qid: ✗ Wrong (Student: '$studentAnswer' vs DB: '$correctField')\n";
    }
}

$accuracy = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;

echo "\n=== RESULTS ===\n";
echo "Total: $totalQuestions\n";
echo "Correct: $correctCount\n";
echo "Wrong: " . ($answeredCount - $correctCount) . "\n";
echo "Skipped: " . ($totalQuestions - $answeredCount) . "\n";
echo "Accuracy: " . round($accuracy, 2) . "%\n";

echo "\nWhat API returns:\n";
$apiData = [
    'identifier' => $student['identifier'],
    'name' => $student['name'],
    'accuracy' => round($accuracy, 2),
    'score' => $correctCount,
    'total_questions' => $totalQuestions,
];
echo json_encode($apiData, JSON_PRETTY_PRINT) . "\n";
?>
