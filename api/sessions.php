<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $rows = $pdo->query('SELECT * FROM sessions')->fetchAll();
        json_out($rows);
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        $identifier = $data['identifier'] ?? null;
        if (!$identifier) json_out(['error' => 'identifier required'], 400);
        
        $sessionId = $data['session_id'] ?? null;
        $name = $data['name'] ?? null;
        $submitted = !empty($data['submitted']) ? 1 : 0;
        $answers = json_encode($data['answers'] ?? []);
        $timings = json_encode($data['timings'] ?? $data['questionTimings'] ?? []);
        $qids = json_encode($data['question_ids'] ?? $data['questionIds'] ?? []);
        $violations = intval($data['violations'] ?? 0);
        $exam = intval($data['examMinutes'] ?? 60);
        $lastSaved = date('Y-m-d H:i:s');
        $group = intval($data['group'] ?? 1);

        // Check if session exists (by session_id if provided, otherwise by identifier)
        $check = null;
        if ($sessionId) {
            $checkStmt = $pdo->prepare('SELECT id FROM sessions WHERE session_id = ?');
            $checkStmt->execute([$sessionId]);
            $check = $checkStmt->fetch();
        }
        
        if ($check) {
            // Update existing session
            $pdo->prepare('UPDATE sessions SET name=?, submitted=?, last_saved=?, answers_json=?, timings_json=?, question_ids_json=?, violations=?, exam_minutes=? WHERE session_id=?')
                ->execute([$name, $submitted, $lastSaved, $answers, $timings, $qids, $violations, $exam, $sessionId]);
        } else {
            // Insert new session
            $pdo->prepare('INSERT INTO sessions(identifier, session_id, name, submitted, last_saved, answers_json, timings_json, question_ids_json, violations, exam_minutes, `group`, session_date) VALUES(?,?,?,?,?,?,?,?,?,?,?,CURDATE())')
                ->execute([$identifier, $sessionId, $name, $submitted, $lastSaved, $answers, $timings, $qids, $violations, $exam, $group]);
        }

        json_out(['ok' => true]);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
