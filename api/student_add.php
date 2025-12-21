<?php
session_start();
require_once __DIR__ . '/../db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'Method not allowed'], 405);
    }
    $group = intval($_SESSION['admin_group'] ?? 1);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);

    $name = trim($data['name'] ?? '');
    $identifier = trim($data['identifier'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if (!$name || !$identifier) {
        json_out(['error' => 'Name and identifier are required'], 400);
    }

    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO students (identifier, name, phone, group_id) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), group_id=VALUES(group_id)');
    $stmt->execute([$identifier, $name, $phone, $group]);

    json_out(['success' => true, 'message' => 'Student added successfully']);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
