<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $id = $_GET['identifier'] ?? null;
        if (!$id) json_out(['error' => 'identifier required'], 400);
        $stmt = $pdo->prepare('SELECT filename, timestamp FROM snapshots WHERE identifier=? ORDER BY timestamp DESC LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && $row['filename']) {
            $row['url'] = '/Quiz-App/uploads/' . $row['filename'];
        }
        json_out($row ?: ['filename' => null, 'timestamp' => null, 'url' => null]);
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        $id = $data['identifier'] ?? null;
        $image = $data['image'] ?? null;
        if (!$id || !$image) json_out(['error' => 'identifier and image required'], 400);

        // Create uploads directory if it doesn't exist
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Convert data URL to file
        if (preg_match('/^data:image\/(\w+);base64,(.*)$/', $image, $m)) {
            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $data = base64_decode($m[2]);
            $filename = 'snapshot_' . $id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadsDir . '/' . $filename;
            
            if (file_put_contents($filepath, $data) !== false) {
                $pdo->prepare('INSERT INTO snapshots(identifier,filename) VALUES (?,?)')->execute([$id, $filename]);
                json_out(['ok' => true, 'filename' => $filename, 'url' => '/Quiz-App/uploads/' . $filename]);
            } else {
                json_out(['error' => 'Failed to save file'], 500);
            }
        } else {
            json_out(['error' => 'Invalid image format'], 400);
        }
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
