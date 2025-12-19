<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $identifier = $_GET['identifier'] ?? null;
        if ($identifier) {
            $stmt = $pdo->prepare('SELECT * FROM audio_clips WHERE identifier=? ORDER BY timestamp DESC');
            $stmt->execute([$identifier]);
            json_out($stmt->fetchAll());
        } else {
            $rows = $pdo->query('SELECT * FROM audio_clips ORDER BY timestamp DESC')->fetchAll();
            json_out($rows);
        }
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);

        $id = $data['identifier'] ?? null;
        $audio = $data['audio'] ?? null;
        $timestamp = $data['timestamp'] ?? time();

        if (!$id || !$audio) {
            json_out(['error' => 'Missing required fields'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO audio_clips (identifier, audio_data, timestamp) VALUES (?, ?, ?)');
        $stmt->execute([$id, $audio, $timestamp]);
        json_out(['ok' => true, 'id' => $pdo->lastInsertId()]);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
