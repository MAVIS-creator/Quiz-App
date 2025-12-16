<?php
require __DIR__ . '/../db.php';
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $id = $_GET['identifier'] ?? null;
  if (!$id) json_out(['error' => 'identifier required'], 400);
  $stmt = $pdo->prepare('SELECT image, timestamp FROM snapshots WHERE identifier=? ORDER BY timestamp DESC LIMIT 1');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  json_out($row ?: ['image' => null, 'timestamp' => null]);
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
  $id = $data['identifier'] ?? null;
  $image = $data['image'] ?? null;
  if (!$id || !$image) json_out(['error' => 'identifier and image required'], 400);
  $pdo->prepare('INSERT INTO snapshots(identifier,image) VALUES (?,?)')->execute([$id,$image]);
  json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);
