<?php
require_once __DIR__ . '/../db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'Method not allowed'], 405);
    }

    $pdo = db();
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

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $questionsToDelete = [];

    if ($ext === 'csv') {
        // Parse CSV: Group,Category,Prompt,Option A,Option B,Option C,Option D,Answer
        $lines = explode("\n", $content);
        $skipHeader = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            
            $fields = str_getcsv($line);
            if ($skipHeader) {
                $skipHeader = false;
                $headerStr = strtolower(implode(',', $fields));
                if (strpos($headerStr, 'prompt') !== false) continue;
            }

            if (count($fields) >= 3) {
                $rowGroup = isset($fields[0]) && is_numeric($fields[0]) ? intval($fields[0]) : $group;
                $prompt = trim($fields[2] ?? '');
                if ($prompt) {
                    $questionsToDelete[] = [
                        'prompt' => $prompt,
                        'group' => $rowGroup
                    ];
                }
            }
        }
    } else {
        // Parse Markdown/Text format: ## Question text
        $lines = explode("\n", $content);
        $currentGroup = $group;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check for group marker
            if (preg_match('/^# Group (\d+)/', $line, $m)) {
                $currentGroup = intval($m[1]);
                continue;
            }

            // Extract question prompt
            if (preg_match('/^## (.+)$/', $line, $m)) {
                $prompt = trim($m[1]);
                if ($prompt) {
                    $questionsToDelete[] = [
                        'prompt' => $prompt,
                        'group' => $currentGroup
                    ];
                }
            }
        }
    }

    if (empty($questionsToDelete)) {
        json_out(['error' => 'No valid questions found in file for deletion'], 400);
    }

    // Delete matching questions
    $deleteCount = 0;
    $notFound = [];
    $stmt = $pdo->prepare('DELETE FROM questions WHERE prompt = ? AND `group` = ? LIMIT 1');

    foreach ($questionsToDelete as $q) {
        // Check if question exists first
        $checkStmt = $pdo->prepare('SELECT id FROM questions WHERE prompt = ? AND `group` = ? LIMIT 1');
        $checkStmt->execute([$q['prompt'], $q['group']]);
        
        if ($checkStmt->fetch()) {
            $stmt->execute([$q['prompt'], $q['group']]);
            if ($stmt->rowCount() > 0) {
                $deleteCount++;
            }
        } else {
            $notFound[] = substr($q['prompt'], 0, 60) . '...';
        }
    }

    $message = "Deleted $deleteCount out of " . count($questionsToDelete) . " questions";
    if (count($notFound) > 0) {
        $message .= " (" . count($notFound) . " not found in database)";
    }

    json_out([
        'success' => true,
        'deleted' => $deleteCount,
        'total' => count($questionsToDelete),
        'not_found' => count($notFound),
        'group' => $group,
        'message' => $message
    ]);

} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
