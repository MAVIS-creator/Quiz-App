<?php
/**
 * The Patcher API - Safe Code Repair Tool
 * Handles file operations with safety mechanisms
 */

session_start();
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    json_out(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'listFiles':
            handleListFiles();
            break;
        case 'readFile':
            handleReadFile();
            break;
        case 'previewDiff':
            handlePreviewDiff();
            break;
        case 'applyFix':
            handleApplyFix();
            break;
        case 'listBackups':
            handleListBackups();
            break;
        case 'createFile':
            handleCreateFile();
            break;
        case 'createFolder':
            handleCreateFolder();
            break;
        default:
            json_out(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}

/**
 * Security: Path validation and safety checks
 */
function validatePath($path) {
    $rootDir = realpath(__DIR__ . '/..');
    
    // Whitelist of safe directories
    $whitelist = [
        'api',
        'assets',
        'components',
        'scripts/tests'
    ];
    
    // Blocked files (absolute no-edit)
    $blocked = [
        'db.php',
        '.env',
        '.htaccess',
        'config.php'
    ];
    
    // Allowed extensions
    $allowedExtensions = ['php', 'js', 'css', 'html', 'json'];
    
    // Normalize and strip traversal
    $cleanPath = ltrim(str_replace(['../', '..\\'], '', str_replace('\\', '/', (string)$path)), '/');
    $candidate = $rootDir . '/' . $cleanPath;
    
    // Resolve to real path when possible; if not, fallback to candidate (some hosts limit realpath)
    $fullPath = file_exists($candidate) ? (realpath($candidate) ?: $candidate) : false;
    
    // Check 1: File must exist
    if (!$fullPath || !file_exists($fullPath)) {
        throw new Exception('File not found: ' . $cleanPath);
    }
    
    // Check 2: Must be within root directory (no escape)
    $fullDir = realpath(dirname($fullPath)) ?: dirname($fullPath);
    if (strpos($fullDir, $rootDir) !== 0) {
        throw new Exception('Path traversal detected - access denied');
    }
    
    // Check 3: Check if in whitelisted directory (use sanitized relative path)
    $relativePath = $cleanPath;
    $inWhitelist = false;
    foreach ($whitelist as $allowed) {
        if (strpos($relativePath, $allowed . '/') === 0 || $relativePath === $allowed) {
            $inWhitelist = true;
            break;
        }
    }
    if (!$inWhitelist) {
        throw new Exception('File not in allowed directory - access denied');
    }
    
    // Check 4: Not in blocked list
    $filename = basename($fullPath);
    if (in_array($filename, $blocked)) {
        throw new Exception('File is protected and cannot be edited');
    }
    
    // Check 5: Valid extension
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    if (!in_array($ext, $allowedExtensions)) {
        throw new Exception('File type not allowed for editing');
    }
    
    return [
        'fullPath' => $fullPath,
        'relativePath' => $relativePath,
        'filename' => $filename,
        'extension' => $ext
    ];
}

/**
 * List all editable files in whitelisted directories
 */
function handleListFiles() {
    $rootDir = realpath(__DIR__ . '/..');
    $whitelist = ['api', 'assets', 'components', 'scripts/tests'];
    $blocked = ['db.php', '.env', '.htaccess', 'config.php'];
    $allowedExtensions = ['php', 'js', 'css', 'html', 'json'];
    
    $files = [];
    
    foreach ($whitelist as $dir) {
        $dirPath = $rootDir . '/' . $dir;
        if (!is_dir($dirPath)) continue;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $filename = $file->getFilename();
            $ext = $file->getExtension();
            
            // Normalize both paths to use forward slashes for comparison
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $normalRoot = str_replace('\\', '/', $rootDir);
            
            // Strip root directory and leading slash to get relative path
            if (strpos($fullPath, $normalRoot . '/') === 0) {
                $relativePath = substr($fullPath, strlen($normalRoot) + 1);
            } else {
                // Fallback if stripping didn't work
                $relativePath = substr($fullPath, strlen($normalRoot));
                $relativePath = ltrim($relativePath, '/');
            }
            
            // Skip blocked files
            if (in_array($filename, $blocked)) continue;
            
            // Only allowed extensions
            if (!in_array($ext, $allowedExtensions)) continue;
            
            $files[] = [
                'path' => $relativePath,
                'name' => $filename,
                'dir' => dirname($relativePath),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'extension' => $ext,
                'editable' => true
            ];
        }
    }
    
    // Sort by directory then name
    usort($files, function($a, $b) {
        $dirCompare = strcmp($a['dir'], $b['dir']);
        return $dirCompare !== 0 ? $dirCompare : strcmp($a['name'], $b['name']);
    });
    
    json_out(['files' => $files, 'count' => count($files)]);
}

/**
 * Read file content
 */
function handleReadFile() {
    $path = $_GET['path'] ?? '';
    $validated = validatePath($path);
    
    $content = file_get_contents($validated['fullPath']);
    
    json_out([
        'content' => $content,
        'path' => $validated['relativePath'],
        'filename' => $validated['filename'],
        'size' => strlen($content),
        'lines' => substr_count($content, "\n") + 1,
        'extension' => $validated['extension']
    ]);
}

/**
 * Preview diff between original and edited content
 */
function handlePreviewDiff() {
    $data = json_decode(file_get_contents('php://input'), true);
    $path = $data['path'] ?? '';
    $newContent = $data['content'] ?? '';
    
    $validated = validatePath($path);
    $originalContent = file_get_contents($validated['fullPath']);
    
    // Generate line-by-line diff
    $diff = generateDiff($originalContent, $newContent);
    
    json_out([
        'diff' => $diff,
        'hasChanges' => $diff['hasChanges'],
        'stats' => $diff['stats']
    ]);
}

/**
 * Apply fix: Create backup then write new content
 */
function handleApplyFix() {
    $data = json_decode(file_get_contents('php://input'), true);
    $path = $data['path'] ?? '';
    $newContent = $data['content'] ?? '';
    
    $validated = validatePath($path);
    $originalContent = file_get_contents($validated['fullPath']);
    
    // Check if there are actually changes
    if ($originalContent === $newContent) {
        json_out(['error' => 'No changes detected'], 400);
    }
    
    // Step 1: Create backup
    $timestamp = date('Ymd_His');
    $backupPath = $validated['fullPath'] . '.bak.' . $timestamp;
    
    if (!copy($validated['fullPath'], $backupPath)) {
        throw new Exception('Failed to create backup - operation aborted');
    }
    
    // Step 2: Write new content
    if (file_put_contents($validated['fullPath'], $newContent) === false) {
        throw new Exception('Failed to write new content');
    }
    
    // Step 3: Log the operation
    logPatcherAction([
        'user' => $_SESSION['admin_username'] ?? 'admin',
        'action' => 'apply_fix',
        'file' => $validated['relativePath'],
        'backup' => basename($backupPath),
        'timestamp' => date('Y-m-d H:i:s'),
        'changes' => [
            'lines_added' => substr_count($newContent, "\n") - substr_count($originalContent, "\n"),
            'size_before' => strlen($originalContent),
            'size_after' => strlen($newContent)
        ]
    ]);
    
    json_out([
        'success' => true,
        'message' => 'Fix applied successfully',
        'backup' => basename($backupPath),
        'backupPath' => $backupPath
    ]);
}

/**
 * List available backups for a file
 */
function handleListBackups() {
    $path = $_GET['path'] ?? '';
    $validated = validatePath($path);
    
    $dir = dirname($validated['fullPath']);
    $filename = $validated['filename'];
    $backups = [];
    
    foreach (glob($dir . '/' . $filename . '.bak.*') as $backup) {
        $backups[] = [
            'name' => basename($backup),
            'path' => str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', $backup),
            'size' => filesize($backup),
            'created' => date('Y-m-d H:i:s', filemtime($backup))
        ];
    }
    
    // Sort by newest first
    usort($backups, function($a, $b) {
        return strcmp($b['created'], $a['created']);
    });
    
    json_out(['backups' => $backups, 'count' => count($backups)]);
}

/**
 * Validate target path for creation (file or folder)
 */
function validateCreateTarget($targetPath, $isFile = true) {
    $rootDir = realpath(__DIR__ . '/..');
    $whitelist = ['api', 'assets', 'components', 'scripts/tests'];
    $blocked = ['db.php', '.env', '.htaccess', 'config.php'];
    $allowedExtensions = ['php', 'js', 'css', 'html', 'json'];

    // Normalize and strip traversal (same as validatePath)
    $clean = ltrim(str_replace(['../', '..\\'], '', str_replace('\\', '/', (string)$targetPath)), '/');
    $candidate = $rootDir . '/' . $clean;
    $parentDir = dirname($candidate);

    // Ensure target is under a whitelisted root
    $inWhitelist = false;
    foreach ($whitelist as $allowed) {
        if (strpos($clean, $allowed . '/') === 0 || $clean === $allowed) {
            $inWhitelist = true;
            break;
        }
    }
    if (!$inWhitelist) {
        throw new Exception('Target not in allowed directory');
    }

    // Blocked base names
    $base = basename($candidate);
    if (in_array($base, $blocked)) {
        throw new Exception('Target name is protected');
    }

    if ($isFile) {
        $ext = pathinfo($candidate, PATHINFO_EXTENSION);
        if (!$ext || !in_array($ext, $allowedExtensions)) {
            throw new Exception('File type not allowed');
        }
    }

    return [
        'rootDir' => $rootDir,
        'fullPath' => $candidate,
        'relativePath' => $clean,
        'parentDir' => $parentDir
    ];
}

/**
 * Create a new file with optional initial content
 */
function handleCreateFile() {
    $data = json_decode(file_get_contents('php://input'), true);
    $path = $data['path'] ?? '';
    $content = $data['content'] ?? null;

    if (!$path) {
        json_out(['error' => 'Path is required'], 400);
    }

    $target = validateCreateTarget($path, true);

    // Create parent directories if missing (within whitelist)
    if (!is_dir($target['parentDir'])) {
        if (!mkdir($target['parentDir'], 0755, true)) {
            throw new Exception('Failed to create parent directories');
        }
    }

    if (file_exists($target['fullPath'])) {
        json_out(['error' => 'File already exists'], 400);
    }

    // Choose default content by extension if content is null
    if ($content === null) {
        $ext = pathinfo($target['fullPath'], PATHINFO_EXTENSION);
        $content = defaultTemplateForExt($ext, basename($target['fullPath']));
    }

    if (file_put_contents($target['fullPath'], $content) === false) {
        throw new Exception('Failed to create file');
    }

    logPatcherAction([
        'user' => $_SESSION['admin_username'] ?? 'admin',
        'action' => 'create_file',
        'file' => $target['relativePath'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    json_out(['success' => true, 'path' => $target['relativePath']]);
}

/**
 * Create a new folder (recursive)
 */
function handleCreateFolder() {
    $data = json_decode(file_get_contents('php://input'), true);
    $path = $data['path'] ?? '';

    if (!$path) {
        json_out(['error' => 'Path is required'], 400);
    }

    $target = validateCreateTarget($path, false);

    if (is_dir($target['fullPath'])) {
        json_out(['error' => 'Folder already exists'], 400);
    }

    if (!mkdir($target['fullPath'], 0755, true)) {
        throw new Exception('Failed to create folder');
    }

    logPatcherAction([
        'user' => $_SESSION['admin_username'] ?? 'admin',
        'action' => 'create_folder',
        'folder' => $target['relativePath'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    json_out(['success' => true, 'path' => $target['relativePath']]);
}

/**
 * Default starter templates for new files
 */
function defaultTemplateForExt($ext, $name) {
    switch ($ext) {
        case 'php':
            return "<?php\n/**\n * ${name}\n */\n\n?>\n";
        case 'js':
            return "// ${name}\n'use strict';\n\n";
        case 'css':
            return "/* ${name} */\n\n";
        case 'html':
            return "<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>${name}</title>\n</head>\n<body>\n\n</body>\n</html>\n";
        case 'json':
            return "{\n  \"name\": \"${name}\"\n}\n";
        default:
            return "";
    }
}

/**
 * Generate line-by-line diff
 */
function generateDiff($original, $new) {
    $originalLines = explode("\n", $original);
    $newLines = explode("\n", $new);
    
    $diff = [];
    $stats = [
        'added' => 0,
        'removed' => 0,
        'unchanged' => 0
    ];
    
    $maxLines = max(count($originalLines), count($newLines));
    
    for ($i = 0; $i < $maxLines; $i++) {
        $origLine = $originalLines[$i] ?? null;
        $newLine = $newLines[$i] ?? null;
        
        if ($origLine === $newLine) {
            $diff[] = [
                'type' => 'unchanged',
                'lineNum' => $i + 1,
                'content' => $origLine
            ];
            $stats['unchanged']++;
        } elseif ($origLine !== null && $newLine === null) {
            $diff[] = [
                'type' => 'removed',
                'lineNum' => $i + 1,
                'content' => $origLine
            ];
            $stats['removed']++;
        } elseif ($origLine === null && $newLine !== null) {
            $diff[] = [
                'type' => 'added',
                'lineNum' => $i + 1,
                'content' => $newLine
            ];
            $stats['added']++;
        } else {
            // Line modified
            $diff[] = [
                'type' => 'removed',
                'lineNum' => $i + 1,
                'content' => $origLine
            ];
            $diff[] = [
                'type' => 'added',
                'lineNum' => $i + 1,
                'content' => $newLine
            ];
            $stats['removed']++;
            $stats['added']++;
        }
    }
    
    return [
        'lines' => $diff,
        'hasChanges' => ($stats['added'] > 0 || $stats['removed'] > 0),
        'stats' => $stats
    ];
}

/**
 * Log patcher actions to audit file
 */
function logPatcherAction($data) {
    $logFile = __DIR__ . '/../logs/patcher_audit.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $entry = json_encode($data) . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}
?>
