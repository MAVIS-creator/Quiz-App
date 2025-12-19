<?php
echo "=== Quiz App API Test Suite ===\n\n";

$API = 'http://localhost/Quiz-App/api';
$passCount = 0;
$failCount = 0;

function testAPI($name, $url, $method = 'GET', $data = null) {
    global $passCount, $failCount;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $json = json_decode($response, true);
    $success = ($httpCode >= 200 && $httpCode < 400) && $json !== null;
    
    if ($success) {
        echo "✅ $name - PASS\n";
        $passCount++;
    } else {
        echo "❌ $name - FAIL (HTTP $httpCode)\n";
        echo "   Response: " . substr($response, 0, 100) . "\n";
        $failCount++;
    }
    
    return $success;
}

// Test 1: Config GET
testAPI('Config GET', "$API/config.php");

// Test 2: Config POST
testAPI('Config POST', "$API/config.php", 'POST', [
    'examMinutes' => 60,
    'questionCount' => 40
]);

// Test 3: Sessions GET
testAPI('Sessions GET', "$API/sessions.php");

// Test 4: Sessions POST
testAPI('Sessions POST', "$API/sessions.php", 'POST', [
    'identifier' => 'TEST_' . time(),
    'name' => 'Test Student',
    'answers' => ['1' => 'A', '2' => 'B'],
    'timings' => ['1' => 10, '2' => 20],
    'question_ids' => [1, 2, 3]
]);

// Test 5: Violations GET
testAPI('Violations GET', "$API/violations.php");

// Test 6: Violations POST
testAPI('Violations POST', "$API/violations.php", 'POST', [
    'identifier' => 'TEST001',
    'type' => 'test_violation',
    'severity' => 2,
    'message' => 'Test violation'
]);

// Test 7: Messages GET (with params)
testAPI('Messages GET', "$API/messages.php?a=TEST001");

// Test 8: Messages POST
testAPI('Messages POST', "$API/messages.php", 'POST', [
    'sender' => 'admin',
    'receiver' => 'TEST001',
    'text' => 'Test message'
]);

// Test 9: Shuffle GET
testAPI('Shuffle GET', "$API/shuffle.php?identifier=TEST001");

// Test 10: Accuracy GET
testAPI('Accuracy GET', "$API/accuracy.php?identifier=TEST001");

// Test 11: Time Control POST
testAPI('Time Control POST', "$API/time_control.php", 'POST', [
    'identifier' => 'TEST001',
    'adjustment_seconds' => 300,
    'reason' => 'Test adjustment',
    'admin_name' => 'Test Admin'
]);

// Test 12: Admin Actions POST
testAPI('Admin Actions POST', "$API/admin_actions.php", 'POST', [
    'identifier' => 'TEST001',
    'action_type' => 'warning',
    'reason' => 'Test warning',
    'admin_name' => 'Test Admin'
]);

// Test 13: Snapshot POST
testAPI('Snapshot POST', "$API/snapshot.php", 'POST', [
    'identifier' => 'TEST001',
    'image' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg=='
]);

// Test 14: Audio Clip POST
testAPI('Audio Clip POST', "$API/audio_clip.php", 'POST', [
    'identifier' => 'TEST001',
    'audio' => 'data:audio/webm;base64,GkXfo59ChoEBQveBAULygQRC',
    'timestamp' => time() * 1000
]);

echo "\n=== Test Summary ===\n";
echo "✅ Passed: $passCount\n";
echo "❌ Failed: $failCount\n";
echo "📊 Total: " . ($passCount + $failCount) . "\n";

if ($failCount === 0) {
    echo "\n🎉 ALL TESTS PASSED! 🎉\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Check the output above.\n";
    exit(1);
}
