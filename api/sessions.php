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
  $lastSaved = date('c');

  $pdo->prepare('INSERT INTO sessions(identifier,name,submitted,last_saved,answers_json,timings_json,question_ids_json,violations,exam_minutes)
    VALUES(?,?,?,?,?,?,?,?,?) ON CONFLICT(identifier) DO UPDATE SET name=excluded.name, submitted=excluded.submitted, last_saved=excluded.last_saved, answers_json=excluded.answers_json, timings_json=excluded.timings_json, question_ids_json=excluded.question_ids_json, violations=excluded.violations, exam_minutes=excluded.exam_minutes')
    ->execute([$identifier,$name,$submitted,$lastSaved,$answers,$timings,$qids,$violations,$exam]);

  json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);
