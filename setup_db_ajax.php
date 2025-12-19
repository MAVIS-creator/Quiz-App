<?php
header('Content-Type: application/json');

try {
    // Database connection
    $host = 'localhost';
    $port = '3306';
    $user = 'root';
    $pass = '';
    
    // Connect without database first
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Create database
    $pdo->exec("DROP DATABASE IF EXISTS quiz_app");
    $pdo->exec("CREATE DATABASE quiz_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE quiz_app");
    
    // Create tables
    $sql = file_get_contents(__DIR__ . '/setup_database.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(DROP DATABASE|CREATE DATABASE|USE quiz_app)/', $statement)) {
            $pdo->exec($statement);
        }
    }
    
    // Reconnect to quiz_app database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=quiz_app;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Seed questions from questions.md
    $mdPath = __DIR__ . '/questions.md';
    if (file_exists($mdPath)) {
        $content = file_get_contents($mdPath);
        $lines = preg_split('/\r?\n/', $content);
        
        $category = '';
        $prompt = null;
        $options = [];
        $answer = '';
        $id = 1;
        
        $insert = $pdo->prepare('INSERT INTO questions (id, category, prompt, option_a, option_b, option_c, option_d, answer) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE category=VALUES(category), prompt=VALUES(prompt), option_a=VALUES(option_a), option_b=VALUES(option_b), option_c=VALUES(option_c), option_d=VALUES(option_d), answer=VALUES(answer)');
        
        $flush = function() use (&$id, &$category, &$prompt, &$options, &$answer, $insert) {
            if ($prompt && count($options) >= 4) {
                while (count($options) < 4) { $options[] = end($options); }
                $insert->execute([$id, $category, $prompt, $options[0], $options[1], $options[2], $options[3], $answer ?: $options[0]]);
                $id++;
            }
            $prompt = null; $options = []; $answer = '';
        };
        
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            
            if (preg_match('/^Part\s+\d+:\s+(.+?)\s+\(/', $t, $m)) {
                $category = $m[1];
                continue;
            }
            
            if (preg_match('/^(\d+)\.\s+(.+?)\s+A\)\s+(.+?)\s+B\)\s+(.+?)\s+C\)\s+(.+?)\s+D\)\s+(.+?)\s+Answer:\s+(.+)$/', $t, $m)) {
                $flush();
                $id = intval($m[1]);
                $prompt = $m[2];
                $options = [$m[3], $m[4], $m[5], $m[6]];
                $answer = $m[7];
                $flush();
            }
        }
        $flush();
        $questionCount = $id - 1;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Database setup complete!<br>Created database and tables.<br>Seeded {$questionCount} questions."
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
