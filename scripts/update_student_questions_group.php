<?php
require __DIR__ . '/../db.php';

try {
    $pdo = db();

    // Add group_id to student_questions if missing
    $col = $pdo->query("SHOW COLUMNS FROM student_questions LIKE 'group_id'");
    if ($col->rowCount() === 0) {
        $pdo->exec("ALTER TABLE student_questions ADD COLUMN group_id INT DEFAULT 1 AFTER identifier");
        $pdo->exec("CREATE INDEX idx_student_questions_group ON student_questions(group_id)");
        echo "Added group_id to student_questions.\n";
    } else {
        echo "group_id already present on student_questions.\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit(1);
}
