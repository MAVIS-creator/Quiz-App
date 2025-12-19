<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $identifier = $_GET['identifier'] ?? null;
        if ($identifier) {
            $rows = $pdo->prepare('SELECT * FROM violations WHERE identifier=? ORDER BY created_at DESC');
            $rows->execute([$identifier]);
            json_out($rows->fetchAll());
        } else {
            $stmt = $pdo->query('SELECT identifier, COUNT(*) as count FROM violations GROUP BY identifier');
            json_out($stmt->fetchAll());
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        $pdo->prepare('INSERT INTO violations(identifier,type,severity,message) VALUES (?,?,?,?)')
            ->execute([$data['identifier'] ?? '', $data['type'] ?? 'tab-switch', intval($data['severity'] ?? 1), $data['message'] ?? null]);
        json_out(['ok' => true]);
    }
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
