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

    // Parse CSV format: name, identifier (matric), phone
    // Header row: Name,Matric,Phone
    // Data rows: John Doe,M12345,08012345678

    $lines = explode("\n", $content);
    $students = [];
    $skipHeader = true;
    $errors = [];
    $count = 0;

    foreach ($lines as $idx => $line) {
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) continue;
        
        // Skip header
        if ($skipHeader) {
            $skipHeader = false;
            if (preg_match('/name|matric|identifier/i', $line)) {
                continue;
            }
        }

        // Parse CSV line
        $fields = str_getcsv($line);
        if (count($fields) < 2) {
            $errors[] = "Line " . ($idx + 1) . ": Invalid format (need at least Name,Matric)";
            continue;
        }

        $name = trim($fields[0] ?? '');
        $identifier = trim($fields[1] ?? '');
        $phone = trim($fields[2] ?? '');

        if (empty($name) || empty($identifier)) {
            $errors[] = "Line " . ($idx + 1) . ": Name and Matric required";
            continue;
        }

        $students[] = [
            'name' => $name,
            'identifier' => $identifier,
            'phone' => $phone,
            'group' => $group
        ];
    }

    if (empty($students)) {
        json_out([
            'error' => 'No valid students found in file',
            'details' => $errors
        ], 400);
    }

    // Insert students
    $insertCount = 0;
    $duplicateCount = 0;
    $stmt = $pdo->prepare('
        INSERT INTO students (identifier, name, phone, group_id)
        VALUES (?, ?, ?, ?)
    ');

    foreach ($students as $s) {
        try {
            $stmt->execute([
                $s['identifier'],
                $s['name'],
                $s['phone'],
                $s['group']
            ]);
            $insertCount++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
                $duplicateCount++;
            }
        }
    }

    $message = "Imported $insertCount students for Group $group";
    if ($duplicateCount > 0) {
        $message .= " ($duplicateCount duplicates skipped)";
    }

    json_out([
        'success' => true,
        'imported' => $insertCount,
        'duplicates' => $duplicateCount,
        'total' => count($students),
        'group' => $group,
        'message' => $message,
        'errors' => array_slice($errors, 0, 5) // Show first 5 errors
    ]);

} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
?>
