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

    $lines = explode("\n", $content);
    $questions = [];
    $errors = [];
    $skipHeader = true;

    foreach ($lines as $idx => $line) {
        $line = trim($line);
        if ($line === '') continue;

        $fields = str_getcsv($line);
        if ($skipHeader) {
            $skipHeader = false;
            // Try to detect headers; if present, skip
            $headerStr = strtolower(implode(',', $fields));
            if (strpos($headerStr, 'group') !== false && strpos($headerStr, 'prompt') !== false) {
                continue;
            }
        }

        // Expect: Group,Category,Prompt,Option A,Option B,Option C,Option D,Answer
        if (count($fields) < 8) {
            $errors[] = "Line " . ($idx + 1) . ": Invalid format";
            continue;
        }

        $rowGroup = intval($fields[0]);
        $category = trim($fields[1]);
        $prompt = trim($fields[2]);
        $oa = trim($fields[3]);
        $ob = trim($fields[4]);
        $oc = trim($fields[5]);
        $od = trim($fields[6]);
        $answer = trim($fields[7]);

        if ($rowGroup <= 0) $rowGroup = $group;
        if (!$prompt || !$oa || !$ob || !$oc || !$od || !$answer) {
            $errors[] = "Line " . ($idx + 1) . ": Missing fields";
            continue;
        }

        $questions[] = [
            'prompt' => $prompt,
            'category' => $category ?: 'General',
            'options' => [$oa, $ob, $oc, $od],
            'answer' => $answer,
            'group' => $rowGroup
        ];
    }

    if (empty($questions)) {
        json_out(['error' => 'No valid questions found', 'details' => $errors], 400);
    }

    $stmtMax = $pdo->query('SELECT MAX(id) as max_id FROM questions');
    $row = $stmtMax->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;

    $insert = $pdo->prepare('INSERT INTO questions (id, category, prompt, option_a, option_b, option_c, option_d, answer, `group`) VALUES (?,?,?,?,?,?,?,?,?)');
    $insertCount = 0;
    foreach ($questions as $q) {
        try {
            $insert->execute([
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
            // skip duplicates/errors
        }
    }

    json_out([
        'success' => true,
        'imported' => $insertCount,
        'total' => count($questions),
        'group' => $group,
        'message' => "Successfully imported $insertCount questions from CSV"
    ]);

} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
