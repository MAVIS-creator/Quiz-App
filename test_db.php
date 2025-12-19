<?php
// Quick database and config test
require __DIR__ . '/db.php';

echo "=== Database Connection Test ===\n";

try {
    $pdo = db();
    echo "✅ Database connection: SUCCESS\n\n";
    
    echo "=== Testing Config Table ===\n";
    $result = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
    if ($result) {
        echo "✅ Config data found:\n";
        echo "   Exam Minutes: " . $result['exam_minutes'] . "\n";
        echo "   Question Count: " . $result['question_count'] . "\n\n";
    } else {
        echo "❌ No config data found\n\n";
    }
    
    echo "=== Testing Sessions Table ===\n";
    $sessions = $pdo->query('SELECT COUNT(*) as count FROM sessions')->fetch();
    echo "✅ Sessions table accessible\n";
    echo "   Total sessions: " . $sessions['count'] . "\n\n";
    
    echo "=== Testing Questions Table ===\n";
    $questions = $pdo->query('SELECT COUNT(*) as count FROM questions')->fetch();
    echo "✅ Questions table accessible\n";
    echo "   Total questions: " . $questions['count'] . "\n\n";
    
    echo "=== All Tests Passed! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
