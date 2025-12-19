<?php
/**
 * Admin login with group selection
 */
session_start();

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !$data['username'] || !$data['password']) {
        json_out(['error' => 'Username and password required'], 400);
    }
    
    $username = $data['username'];
    $password = $data['password'];
    $group = intval($data['group'] ?? 1);
    
    // Simple hardcoded admin credentials for demo
    // In production, use proper authentication
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_group'] = $group;
        
        json_out(['ok' => true, 'group' => $group]);
    } else {
        json_out(['error' => 'Invalid credentials'], 401);
    }
    
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
?>
