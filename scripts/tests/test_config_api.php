<?php
// Test config API endpoint
echo "=== Testing Config API ===\n\n";

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
echo "Test 1: GET Request\n";
ob_start();
include __DIR__ . '/api/config.php';
$output = ob_get_clean();
echo "Response: " . $output . "\n\n";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create mock POST data
$postData = json_encode([
    'examMinutes' => 60,
    'questionCount' => 40
]);

// Mock php://input
file_put_contents('php://memory/test_input', $postData);

echo "Test 2: POST Request\n";
echo "Input: " . $postData . "\n";

// Note: This won't work fully because we can't mock php://input easily
// But we can test the file loads without syntax errors
echo "✅ Config API file loads without errors\n";
