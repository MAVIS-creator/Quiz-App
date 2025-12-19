<?php
/**
 * API: Get or generate shuffled questions for a student
 * Returns randomized question order unique to each student
 */

require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

try {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $identifier = $_GET['identifier'] ?? '';
        
        if (empty($identifier)) {
            json_out(['error' => 'Identifier required'], 400);
        }
        
        // Check if student already has a shuffled order
        $stmt = $pdo->prepare('SELECT question_ids_order FROM student_questions WHERE identifier = ?');
        $stmt->execute([$identifier]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Return existing order
            $questionIds = json_decode($existing['question_ids_order'], true);
            json_out(['question_ids' => $questionIds]);
        }
        
        // Get total questions configured
        $configStmt = $pdo->query('SELECT question_count FROM config WHERE id = 1');
        $config = $configStmt->fetch();
        $totalQuestions = $config['question_count'] ?? 40;
        
        // Get all available questions
        $questionsStmt = $pdo->query('SELECT id FROM questions ORDER BY id');
        $allQuestions = $questionsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($allQuestions) < $totalQuestions) {
            json_out(['error' => 'Not enough questions in database'], 500);
        }
        
        // Shuffle and select required number
        shuffle($allQuestions);
        $selectedQuestions = array_slice($allQuestions, 0, $totalQuestions);
        
        // Save to database
        $insertStmt = $pdo->prepare('INSERT INTO student_questions (identifier, question_ids_order) VALUES (?, ?)');
        $insertStmt->execute([$identifier, json_encode($selectedQuestions)]);
        
        json_out(['question_ids' => $selectedQuestions]);
    }
    
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
