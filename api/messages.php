<?php
require __DIR__ . '/../db.php';
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $a = $_GET['a'] ?? null; // admin or student id
  $b = $_GET['b'] ?? null;
  if (!$a || !$b) json_out(['error' => 'a and b required'], 400);
  $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender=? AND receiver=?) OR (sender=? AND receiver=?) ORDER BY created_at ASC');
  $stmt->execute([$a,$b,$b,$a]);
  json_out($stmt->fetchAll());
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
  $sender = $data['sender'] ?? null;
  $receiver = $data['receiver'] ?? null;
  $text = trim($data['text'] ?? '');
  if (!$sender || !$receiver || $text === '') json_out(['error' => 'sender, receiver, text required'], 400);
  $pdo->prepare('INSERT INTO messages(sender,receiver,text) VALUES (?,?,?)')->execute([$sender,$receiver,$text]);
  json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);
