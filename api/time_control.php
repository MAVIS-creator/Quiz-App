<?php
/**
 * API: Admin time control for students
 * Allows admin to add or subtract time from student's quiz
 */

require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

try {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $identifier = $data['identifier'] ?? '';
        $adjustmentSeconds = intval($data['adjustment_seconds'] ?? 0);
        $reason = $data['reason'] ?? 'Admin adjustment';
        $adminName = $data['admin_name'] ?? 'Admin';
        
        if (empty($identifier)) {
            json_out(['error' => 'Identifier required'], 400);
        }
        
        // Record the adjustment
        $stmt = $pdo->prepare('INSERT INTO time_adjustments (identifier, adjustment_seconds, reason, admin_name) 
                               VALUES (?, ?, ?, ?)');
        $stmt->execute([$identifier, $adjustmentSeconds, $reason, $adminName]);
        
        // Update session total adjustment
        $updateStmt = $pdo->prepare('UPDATE sessions 
                                     SET time_adjustment_seconds = time_adjustment_seconds + ? 
                                     WHERE identifier = ?');
        $updateStmt->execute([$adjustmentSeconds, $identifier]);
        
        json_out([
            'success' => true, 
            'message' => 'Time adjusted',
            'adjustment' => $adjustmentSeconds
        ]);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $identifier = $_GET['identifier'] ?? '';
        
        if (empty($identifier)) {
            json_out(['error' => 'Identifier required'], 400);
        }
        
        // Get current adjustment
        $stmt = $pdo->prepare('SELECT time_adjustment_seconds FROM sessions WHERE identifier = ?');
        $stmt->execute([$identifier]);
        $result = $stmt->fetch();
        
        // Get adjustment history
        $historyStmt = $pdo->prepare('SELECT * FROM time_adjustments WHERE identifier = ? ORDER BY created_at DESC');
        $historyStmt->execute([$identifier]);
        $history = $historyStmt->fetchAll();
        
        json_out([
            'current_adjustment' => $result['time_adjustment_seconds'] ?? 0,
            'history' => $history
        ]);
    }
    
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
