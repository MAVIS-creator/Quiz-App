<?php
require_once __DIR__ . '/../db.php';

// Violation reason mappings
$violationReasons = [
    'tab-switch' => 'Switched Tabs During Exam',
    'fullscreen-exit' => 'Exited Fullscreen Mode',
    'clipboard' => 'Clipboard Access Attempt',
    'suspicious-timing' => 'Suspicious Answer Timing',
    'network-anomaly' => 'Network Connection Issue',
    'cheating-detection' => 'AI/Cheating Content Detected',
    'multiple-clicks' => 'Rapid Multiple Button Clicks',
    'copy-paste' => 'Copy/Paste Action Detected',
    'devtools' => 'Developer Tools Opened',
    'window-blur' => 'Application Window Lost Focus',
];

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $identifier = $_GET['identifier'] ?? null;
        if ($identifier) {
            $rows = $pdo->prepare('SELECT v.*, COALESCE(v.reason, ?) as violation_reason FROM violations v WHERE v.identifier=? ORDER BY v.created_at DESC');
            $rows->execute(['Unknown Violation', $identifier]);
            $violations = $rows->fetchAll();
            
            // Enrich with reason labels
            foreach ($violations as &$v) {
                $v['reason_label'] = $violationReasons[$v['type']] ?? 'Unknown Violation';
            }
            
            json_out($violations);
        } else {
            // Summary with violation reasons
            $stmt = $pdo->query('SELECT identifier, type, COUNT(*) as count FROM violations GROUP BY identifier, type ORDER BY identifier, type');
            $violations = $stmt->fetchAll();
            
            foreach ($violations as &$v) {
                $v['reason_label'] = $violationReasons[$v['type']] ?? 'Unknown Violation';
            }
            
            json_out($violations);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        
        $type = $data['type'] ?? 'tab-switch';
        $reason = $violationReasons[$type] ?? 'Unknown Violation';
        
        $pdo->prepare('INSERT INTO violations(identifier,type,reason,severity,message) VALUES (?,?,?,?,?)')
            ->execute([
                $data['identifier'] ?? '', 
                $type, 
                $reason,
                intval($data['severity'] ?? 1), 
                $data['message'] ?? null
            ]);
        json_out(['ok' => true, 'reason' => $reason]);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
