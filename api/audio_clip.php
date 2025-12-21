<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    // Detect storage mode (filename vs audio_data)
    $hasFilename = $pdo->query("SHOW COLUMNS FROM audio_clips LIKE 'filename'")->fetch();
    $hasDuration = $pdo->query("SHOW COLUMNS FROM audio_clips LIKE 'duration'")->fetch();

    if ($method === 'GET') {
        $identifier = $_GET['identifier'] ?? null;
        if ($identifier) {
            if ($hasFilename) {
                $stmt = $pdo->prepare('SELECT id, identifier, filename, duration, created_at FROM audio_clips WHERE identifier=? ORDER BY created_at DESC LIMIT 10');
            } else {
                $stmt = $pdo->prepare('SELECT * FROM audio_clips WHERE identifier=? ORDER BY timestamp DESC LIMIT 10');
            }
            $stmt->execute([$identifier]);
            $rows = $stmt->fetchAll();
            
            // Build URLs for file-based storage
            foreach ($rows as &$row) {
                if ($hasFilename && isset($row['filename'])) {
                    $path = ltrim($row['filename'], '/');
                    $row['url'] = '/Quiz-App/uploads/' . $path;
                }
            }
            json_out(['clips' => $rows]);
        } else {
            if ($hasFilename) {
                $rows = $pdo->query('SELECT id, identifier, filename, duration, created_at FROM audio_clips ORDER BY created_at DESC LIMIT 50')->fetchAll();
                foreach ($rows as &$row) {
                    if (isset($row['filename'])) {
                        $path = ltrim($row['filename'], '/');
                        $row['url'] = '/Quiz-App/uploads/' . $path;
                    }
                }
            } else {
                $rows = $pdo->query('SELECT * FROM audio_clips ORDER BY timestamp DESC LIMIT 50')->fetchAll();
            }
            json_out(['clips' => $rows]);
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

        // If using old schema with audio_data column
        if (!$hasFilename) {
            $stmt = $pdo->prepare('INSERT INTO audio_clips (identifier, audio_data, timestamp) VALUES (?, ?, ?)');
            $stmt->execute([$id, $audio, $timestamp]);
            json_out(['ok' => true, 'id' => $pdo->lastInsertId()]);
        }

        json_out(['error' => 'Use audio_save.php for file-based storage'], 400);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
