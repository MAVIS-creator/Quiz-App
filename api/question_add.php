<?php
require_once __DIR__ . '/../db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'Method not allowed'], 405);
    }
    session_start();
    $group = intval($_SESSION['admin_group'] ?? 1);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);

    $prompt = trim($data['prompt'] ?? '');
    $category = trim($data['category'] ?? 'General');
    $oa = trim($data['option_a'] ?? '');
    $ob = trim($data['option_b'] ?? '');
    $oc = trim($data['option_c'] ?? '');
    $od = trim($data['option_d'] ?? '');
    $answer = trim($data['answer'] ?? '');

    if (!$prompt || !$oa || !$ob || !$oc || !$od || !$answer) {
        json_out(['error' => 'All fields are required'], 400);
    }

    $pdo = db();
    $row = $pdo->query('SELECT MAX(id) as max_id FROM questions')->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;

    $stmt = $pdo->prepare('INSERT INTO questions (id, category, prompt, option_a, option_b, option_c, option_d, answer, `group`) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$nextId, $category ?: 'General', $prompt, $oa, $ob, $oc, $od, $answer, $group]);

    json_out(['success' => true, 'id' => $nextId, 'message' => 'Question added successfully']);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
