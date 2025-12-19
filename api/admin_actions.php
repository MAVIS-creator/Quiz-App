<?php
/**
 * API: Admin action controls (penalties, boot, cancel)
 * Allows admin to take disciplinary actions on students
 */

require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

try {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $identifier = $data['identifier'] ?? '';
        $actionType = $data['action_type'] ?? '';
        $value = intval($data['value'] ?? 0);
        $reason = $data['reason'] ?? '';
        $adminName = $data['admin_name'] ?? 'Admin';
        
        if (empty($identifier) || empty($actionType)) {
            json_out(['error' => 'Identifier and action type required'], 400);
        }
        
        $validActions = ['time_penalty', 'point_deduction', 'boot_out', 'exam_cancelled', 'warning'];
        if (!in_array($actionType, $validActions)) {
            json_out(['error' => 'Invalid action type'], 400);
        }
        
        // Record the action
        $stmt = $pdo->prepare('INSERT INTO admin_actions (identifier, action_type, value, reason, admin_name) 
                               VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$identifier, $actionType, $value, $reason, $adminName]);
        
        // Apply the action to session
        switch ($actionType) {
            case 'point_deduction':
                $updateStmt = $pdo->prepare('UPDATE sessions 
                                             SET point_deduction = point_deduction + ? 
                                             WHERE identifier = ?');
                $updateStmt->execute([$value, $identifier]);
                break;
                
            case 'time_penalty':
                $updateStmt = $pdo->prepare('UPDATE sessions 
                                             SET time_adjustment_seconds = time_adjustment_seconds - ? 
                                             WHERE identifier = ?');
                $updateStmt->execute([$value, $identifier]);
                break;
                
            case 'boot_out':
                $updateStmt = $pdo->prepare('UPDATE sessions 
                                             SET status = "booted", submitted = 1 
                                             WHERE identifier = ?');
                $updateStmt->execute([$identifier]);
                break;
                
            case 'exam_cancelled':
                $updateStmt = $pdo->prepare('UPDATE sessions 
                                             SET status = "cancelled", submitted = 1 
                                             WHERE identifier = ?');
                $updateStmt->execute([$identifier]);
                break;
        }
        
        json_out([
            'success' => true, 
            'message' => 'Action applied successfully',
            'action' => $actionType
        ]);
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $identifier = $_GET['identifier'] ?? '';
        
        if (empty($identifier)) {
            // Get all actions
            $stmt = $pdo->query('SELECT a.*, s.name as student_name 
                                FROM admin_actions a 
                                LEFT JOIN sessions s ON a.identifier = s.identifier 
                                ORDER BY a.created_at DESC');
        } else {
            // Get actions for specific student
            $stmt = $pdo->prepare('SELECT a.*, s.name as student_name 
                                   FROM admin_actions a 
                                   LEFT JOIN sessions s ON a.identifier = s.identifier 
                                   WHERE a.identifier = ? 
                                   ORDER BY a.created_at DESC');
            $stmt->execute([$identifier]);
        }
        
        $actions = $stmt->fetchAll();
        json_out(['actions' => $actions]);
    }
    
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
