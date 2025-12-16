<?php
require __DIR__ . '/db.php';

$pdo = db();

// Apply MySQL schema
$schema = file_get_contents(__DIR__ . '/schema.mysql.sql');
foreach (array_filter(explode(";\n", $schema)) as $stmt) {
  $trim = trim($stmt);
  if ($trim !== '') { $pdo->exec($trim); }
}

// Parse questions.md from project root
$mdPath = realpath(__DIR__ . '/../questions.md');
if (!$mdPath || !file_exists($mdPath)) {
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
  if (preg_match('/^##\s+(.+)/', $t, $m)) {
    $category = $m[1];
  } elseif (preg_match('/^\d+\.\s+(.+)/', $t, $m)) {
    $flush();
    $prompt = $m[1];
  } elseif (preg_match('/^-\s+(.+)/', $t, $m)) {
    $options[] = $m[1];
  } elseif (preg_match('/^Answer:\s+(.+)/', $t, $m)) {
    $answer = $m[1];
  }
}
$flush();

echo "Seeded questions: " . ($id - 1) . "\n";
