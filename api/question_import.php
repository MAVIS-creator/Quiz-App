<?php
require __DIR__ . '/../db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'Method not allowed'], 405);
    }

    $pdo = db();
    
    // Check admin session
    session_start();
    $group = intval($_SESSION['admin_group'] ?? 1);
    
    if (!isset($_FILES['file'])) {
        json_out(['error' => 'No file provided'], 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_out(['error' => 'File upload error'], 400);
    }

    $content = file_get_contents($file['tmp_name']);
    if (!$content) {
        json_out(['error' => 'Cannot read file'], 400);
    }

    // Parse markdown format
    // Expected format:
    // # Group 1
    // ## Question 1
    // Option A
    // Option B
    // Option C
    // ~~Option D~~ (this is the answer, marked with ~~)
    // ## Question 2
    // ...

    $lines = explode("\n", $content);
    $questions = [];
    $currentQuestion = null;
    $options = [];
    $answer = null;
    $category = 'General';

    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) continue;
        
        // Group marker (optional, but validates correct file)
        if (preg_match('/^# Group (\d+)/', $line, $m)) {
            $group = intval($m[1]);
            continue;
        }
        
        // Question prompt
        if (preg_match('/^## (.+)$/', $line, $m)) {
            if ($currentQuestion !== null && count($options) === 4 && $answer !== null) {
                $questions[] = [
                    'prompt' => $currentQuestion,
                    'category' => $category,
                    'options' => $options,
                    'answer' => $answer,
                    'group' => $group
                ];
            }
            $currentQuestion = $m[1];
            $options = [];
            $answer = null;
            continue;
        }
        
        // Option line (can be marked with ~~ to indicate correct answer)
        if ($currentQuestion !== null && !empty($line)) {
            if (preg_match('/^~~(.+)~~$/', $line, $m)) {
                // This marks which of the 4 options is correct
                $answer = trim($m[1]);
                // Don't add to options - it should already be there
            } else {
                // Regular option - add only if we have less than 4
                if (count($options) < 4) {
                    $options[] = $line;
                }
            }
        }
    }

    // Don't forget the last question
    if ($currentQuestion !== null && count($options) === 4 && $answer !== null) {
        $questions[] = [
            'prompt' => $currentQuestion,
            'category' => $category,
            'options' => $options,
            'answer' => $answer,
            'group' => $group
        ];
    }

    if (empty($questions)) {
        json_out(['error' => 'No valid questions found in file'], 400);
    }

    // Check for existing questions and skip duplicates
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM questions WHERE prompt = ? AND `group` = ?');
    $insertCount = 0;
    $duplicateCount = 0;
    $skipCount = 0;

    // Get next question ID
    $stmt = $pdo->query('SELECT MAX(id) as max_id FROM questions');
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;

    // Insert questions
    $insertStmt = $pdo->prepare('
        INSERT INTO questions (id, category, prompt, option_a, option_b, option_c, option_d, answer, `group`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    foreach ($questions as $q) {
        try {
            // Check if question with same prompt already exists in this group
            $checkStmt->execute([$q['prompt'], $q['group']]);
            $exists = $checkStmt->fetch()['cnt'] > 0;

            if ($exists) {
                $duplicateCount++;
                continue; // Skip this question
            }

            // Insert only if not duplicate
            $insertStmt->execute([
                $nextId,
                $q['category'],
                $q['prompt'],
                $q['options'][0],
                $q['options'][1],
                $q['options'][2],
                $q['options'][3],
                $q['answer'],
                $q['group']
            ]);
            $insertCount++;
            $nextId++;
        } catch (Exception $e) {
            // Log other errors but continue
            $skipCount++;
            continue;
        }
    }

    $message = "Imported $insertCount questions for Group $group";
    if ($duplicateCount > 0) {
        $message .= " ($duplicateCount duplicates skipped)";
    }
    if ($skipCount > 0) {
        $message .= " ($skipCount errors skipped)";
    }

    json_out([
        'success' => true,
        'imported' => $insertCount,
        'duplicates' => $duplicateCount,
        'skipped' => $skipCount,
        'total' => count($questions),
        'group' => $group,
        'message' => $message
    ]);

} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
?>
