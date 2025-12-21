<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();
    // Detect whether config has a dedicated `group` column
    $hasGroupCol = $pdo->query("SHOW COLUMNS FROM config LIKE 'group'")->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $group = intval($_GET['group'] ?? 1);
        if ($hasGroupCol) {
            $stmt = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE `group`=?');
            $stmt->execute([$group]);
        } else {
            $stmt = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE id=?');
            $stmt->execute([$group]);
        }
        $row = $stmt->fetch();
        if (!$row) {
            // Fallback to group 1 defaults
            if ($hasGroupCol) {
                $stmt1 = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE `group`=1');
                $stmt1->execute();
            } else {
                $stmt1 = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE id=1');
                $stmt1->execute();
            }
            $row = $stmt1->fetch() ?: ['exam_minutes' => 60, 'question_count' => 40];
        }
        json_out($row);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            json_out(['error' => 'Invalid payload'], 400);
        }

        $exam = max(5, min(300, intval($data['examMinutes'] ?? 60)));
        $count = max(1, min(100, intval($data['questionCount'] ?? 40)));
        $group = max(1, intval($data['group'] ?? 1));

        if ($hasGroupCol) {
            // Try update by group; insert if missing. For insert, set id to group to keep compatibility.
            $sel = $pdo->prepare('SELECT id FROM config WHERE `group`=? LIMIT 1');
            $sel->execute([$group]);
            if ($sel->fetch()) {
                $upd = $pdo->prepare('UPDATE config SET exam_minutes=?, question_count=? WHERE `group`=?');
                $upd->execute([$exam, $count, $group]);
            } else {
                $ins = $pdo->prepare('INSERT INTO config(id, `group`, exam_minutes, question_count) VALUES(?,?,?,?)');
                $ins->execute([$group, $group, $exam, $count]);
            }
        } else {
            // Fallback: id-based upsert
            $stmt = $pdo->prepare('INSERT INTO config(id, exam_minutes, question_count) VALUES(?,?,?) ON DUPLICATE KEY UPDATE exam_minutes=VALUES(exam_minutes), question_count=VALUES(question_count)');
            $stmt->execute([$group, $exam, $count]);
        }
        
        json_out(['ok' => true, 'group' => $group, 'exam_minutes' => $exam, 'question_count' => $count]);
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
