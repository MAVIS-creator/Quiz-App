<?php
require __DIR__ . '/../db.php';
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
  $name = $data['name'] ?? null;
  $submitted = !empty($data['submitted']) ? 1 : 0;
  $answers = json_encode($data['answers'] ?? []);
  $timings = json_encode($data['questionTimings'] ?? []);
  $qids = json_encode($data['questionIds'] ?? []);
  $violations = intval($data['violations'] ?? 0);
  $exam = intval($data['examMinutes'] ?? 60);
  $lastSaved = date('Y-m-d H:i:s');

  // Check if session exists
  $check = $pdo->prepare('SELECT id FROM sessions WHERE identifier = ?');
  $check->execute([$identifier]);
  
  if ($check->fetch()) {
    // Update existing session
    $pdo->prepare('UPDATE sessions SET name=?, submitted=?, last_saved=?, answers_json=?, timings_json=?, question_ids_json=?, violations=?, exam_minutes=? WHERE identifier=?')
      ->execute([$name, $submitted, $lastSaved, $answers, $timings, $qids, $violations, $exam, $identifier]);
  } else {
    // Insert new session
    $pdo->prepare('INSERT INTO sessions(identifier, name, submitted, last_saved, answers_json, timings_json, question_ids_json, violations, exam_minutes) VALUES(?,?,?,?,?,?,?,?,?)')
      ->execute([$identifier, $name, $submitted, $lastSaved, $answers, $timings, $qids, $violations, $exam]);
  }

  json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);
