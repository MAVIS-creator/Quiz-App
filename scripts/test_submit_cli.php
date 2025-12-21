<?php
// Terminal-based Submit API Test
require __DIR__ . '/../db.php';

$identifier = $argv[1] ?? 'TEST001';
$questionCount = intval($argv[2] ?? 5);

echo "=== Quiz Submit API Test ===\n\n";
echo "Identifier: $identifier\n";
echo "Questions: $questionCount\n";
echo "API URL: http://localhost/Quiz-App/api/sessions.php\n\n";

// Generate test data
$answers = [];
$timings = [];
$questionIds = [];
$options = ['A', 'B', 'C', 'D'];

for ($i = 1; $i <= $questionCount; $i++) {
    $questionIds[] = $i;
    $answers[$i] = $options[array_rand($options)];
    $timings[$i] = rand(10, 60);
}

$sessionId = $identifier . '_' . time() . '_' . uniqid();

echo "Generated session ID: $sessionId\n";
echo "Answers: " . json_encode($answers) . "\n\n";

// Check if identifier already has a session
try {
    $pdo = db();
    $check = $pdo->prepare('SELECT identifier, session_id, submitted FROM sessions WHERE identifier = ? ORDER BY created_at DESC LIMIT 1');
    $check->execute([$identifier]);
    $existing = $check->fetch();
    
    if ($existing) {
        echo "⚠️  WARNING: Found existing session for $identifier\n";
        echo "   Session ID: {$existing['session_id']}\n";
        echo "   Submitted: " . ($existing['submitted'] ? 'Yes' : 'No') . "\n";
        echo "   Action: Will update existing session\n\n";
    } else {
        echo "✓ No existing session found. Will create new one.\n\n";
    }
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
    exit(1);
}

$submitPayload = [
    'identifier' => $identifier,
    'session_id' => $sessionId,
    'name' => 'Test Student',
    'answers' => $answers,
    'timings' => $timings,
    'question_ids' => $questionIds,
    'submitted' => true,
    'group' => 1
];

echo "Sending POST request...\n";
$startTime = microtime(true);

$ch = curl_init('http://localhost/Quiz-App/api/sessions.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($submitPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo "Response received in {$elapsed}ms (HTTP $httpCode)\n\n";

if ($curlError) {
    echo "❌ CURL Error: $curlError\n";
    exit(1);
}

$data = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && ($data['ok'] ?? false)) {
    echo "✅ SUCCESS!\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    exit(0);
} else {
    echo "❌ FAILED!\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($data['error']) && strpos($data['error'], 'Duplicate entry') !== false) {
        echo "\n💡 TIP: The identifier '$identifier' already exists in the database.\n";
        echo "   Either use a different identifier or clear the test data:\n";
        echo "   php scripts/clear_test_sessions.php\n";
    }
    exit(1);
}
