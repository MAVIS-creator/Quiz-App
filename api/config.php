<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1');
        json_out($stmt->fetch() ?: ['exam_minutes' => 60, 'question_count' => 40]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            json_out(['error' => 'Invalid payload'], 400);
        }

        $exam = max(5, min(300, intval($data['examMinutes'] ?? 60)));
        $count = max(1, min(100, intval($data['questionCount'] ?? 40)));

        $stmt = $pdo->prepare('UPDATE config SET exam_minutes=?, question_count=? WHERE id=1');
        $stmt->execute([$exam, $count]);
        
        json_out(['ok' => true, 'exam_minutes' => $exam, 'question_count' => $count]);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
