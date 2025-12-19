<?php
require __DIR__ . '/db.php';

$pdo = db();

// Schema should already be loaded via setup_database.sql
// This script only seeds questions from questions.md

// Parse questions.md from project root
$mdPath = __DIR__ . '/questions.md';
if (!file_exists($mdPath)) {
  echo "questions.md not found at $mdPath\n";
  exit(0);
}

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
    // pad options to 4
    while (count($options) < 4) { $options[] = end($options); }
    $insert->execute([$id, $category, $prompt, $options[0], $options[1], $options[2], $options[3], $answer ?: $options[0]]);
    $id++;
  }
  $prompt = null; $options = []; $answer = '';
};

foreach ($lines as $line) {
  $t = trim($line);
  if ($t === '') continue;
  
  // Match category: "Part X: Title (range)"
  if (preg_match('/^Part\s+\d+:\s+(.+?)\s+\(/', $t, $m)) {
    $category = $m[1];
    continue;
  }
  
  // Match question line: "1. Question? A) opt B) opt C) opt D) opt Answer: X"
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

echo "Seeded questions: " . ($id - 1) . "\n";
