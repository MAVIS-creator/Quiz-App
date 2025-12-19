<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $id = $_GET['identifier'] ?? null;
        if (!$id) json_out(['error' => 'identifier required'], 400);
        $stmt = $pdo->prepare('SELECT filename, duration, created_at FROM audio_clips WHERE identifier=? ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['url'] = '/Quiz-App/uploads/' . $row['filename'];
        }
        json_out(['clips' => $rows]);
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        $id = $data['identifier'] ?? null;
        $audio = $data['audio'] ?? null;
        $duration = floatval($data['duration'] ?? 0);
        
        if (!$id || !$audio) json_out(['error' => 'identifier and audio required'], 400);

        // Create uploads directory if it doesn't exist
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Convert data URL to file (audio/wav;base64 or audio/webm;base64)
        if (preg_match('/^data:audio\/(\w+);base64,(.*)$/', $audio, $m)) {
            $audioType = $m[1]; // wav, webm, mp3, etc
            $audioData = base64_decode($m[2], true);
            
            if ($audioData === false) {
                json_out(['error' => 'Invalid base64 audio'], 400);
            }

            $filename = 'audio_' . $id . '_' . time() . '_' . uniqid() . '.' . $audioType;
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_put_contents($filepath, $audioData) !== false) {
                $pdo->prepare('INSERT INTO audio_clips(identifier, filename, duration) VALUES (?,?,?)')->execute([
                    $id,
                    $filename,
                    $duration > 0 ? intval($duration) : null
                ]);
                json_out([
                    'ok' => true,
                    'filename' => $filename,
                    'url' => '/Quiz-App/uploads/' . $filename,
                    'duration' => $duration
                ]);
            } else {
                json_out(['error' => 'Failed to save audio file'], 500);
            }
        } else {
            json_out(['error' => 'Invalid audio format'], 400);
        }
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
?>
