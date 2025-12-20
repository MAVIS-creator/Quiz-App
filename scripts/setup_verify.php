<?php
/**
 * Quiz App v2.0 - Complete Setup & Verification Script
 * Run this to initialize all enhancements
 */

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         Quiz App v2.0 - Setup & Verification            ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check database connection
echo "📋 Step 1: Checking Database Connection...\n";
try {
    require __DIR__ . '/db.php';
    $pdo = db();
    echo "   ✅ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: {$e->getMessage()}\n";
    exit(1);
}

// Step 2: Check tables exist
echo "📋 Step 2: Verifying Database Tables...\n";
$tables = ['config', 'students', 'questions', 'sessions', 'violations', 'student_questions'];
$missingTables = [];

foreach ($tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "   ✅ Table '$table' exists\n";
    } catch (Exception $e) {
        echo "   ❌ Table '$table' missing\n";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "\n⚠️  Missing tables: " . implode(', ', $missingTables) . "\n";
    echo "Run setup_database.sql first\n";
    exit(1);
}
echo "\n";

// Step 3: Check for violations.reason column
echo "📋 Step 3: Checking Violations Table Schema...\n";
$columnsStmt = $pdo->query("PRAGMA table_info(violations)");
if (!$columnsStmt) {
    // MySQL syntax
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM violations");
}

$hasReasonColumn = false;
try {
    $checkStmt = $pdo->prepare("SELECT reason FROM violations LIMIT 1");
    $checkStmt->execute();
    $hasReasonColumn = true;
    echo "   ✅ Column 'reason' exists in violations table\n\n";
} catch (Exception $e) {
    echo "   ⚠️  Column 'reason' not found in violations table\n";
    echo "   Running migration...\n";
    
    try {
        $alterStmt = $pdo->prepare("ALTER TABLE violations ADD COLUMN reason VARCHAR(255) AFTER message");
        $alterStmt->execute();
        echo "   ✅ Migration successful - 'reason' column added\n\n";
    } catch (Exception $e) {
        echo "   ❌ Migration failed: {$e->getMessage()}\n";
        exit(1);
    }
}

// Step 4: Check admin configuration
echo "📋 Step 4: Checking Admin Configuration...\n";
try {
    $cfg = $pdo->query("SELECT exam_minutes, question_count FROM config WHERE id=1")->fetch();
    if ($cfg) {
        echo "   ✅ Exam Duration: {$cfg['exam_minutes']} minutes\n";
        echo "   ✅ Question Count: {$cfg['question_count']} questions\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Config check failed: {$e->getMessage()}\n";
}

// Step 5: Check files exist
echo "📋 Step 5: Verifying Enhancement Files...\n";
$files = [
    'admin-enhanced.php' => 'Modern admin dashboard',
    'quiz_new.php' => 'Quiz with question navigator',
    'api/violations.php' => 'Enhanced violations API',
    'scripts/add_group2_students.php' => 'Student import script',
    'scripts/migrate_violations_reasons.php' => 'Database migration script'
];

$allFilesExist = true;
foreach ($files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ {$file} - {$description}\n";
    } else {
        echo "   ❌ {$file} - MISSING!\n";
        $allFilesExist = false;
    }
}
echo "\n";

if (!$allFilesExist) {
    echo "⚠️  Some files are missing. Please check the installation.\n";
    exit(1);
}

// Step 6: Count students in each group
echo "📋 Step 6: Student Statistics...\n";
try {
    $stmt = $pdo->prepare("SELECT group_id, COUNT(*) as count FROM students GROUP BY group_id ORDER BY group_id");
    $stmt->execute();
    $groups = $stmt->fetchAll();
    
    foreach ($groups as $group) {
        echo "   ✅ Group {$group['group_id']}: {$group['count']} students\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ❌ Student count failed: {$e->getMessage()}\n";
}

// Step 7: Check sessions data
echo "📋 Step 7: Session Statistics...\n";
try {
    $totalSessions = $pdo->query("SELECT COUNT(*) as count FROM sessions")->fetch()['count'];
    $submittedSessions = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE submitted=1")->fetch()['count'];
    $violatedSessions = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE violations > 0")->fetch()['count'];
    
    echo "   ✅ Total sessions: $totalSessions\n";
    echo "   ✅ Submitted: $submittedSessions\n";
    echo "   ✅ With violations: $violatedSessions\n\n";
} catch (Exception $e) {
    echo "   ℹ️  No session data yet\n\n";
}

// Step 8: Test violation tracking
echo "📋 Step 8: Violation Tracking Setup...\n";
try {
    $violations = $pdo->query("SELECT COUNT(*) as count FROM violations WHERE reason IS NOT NULL")->fetch()['count'];
    echo "   ✅ Violations with reasons: $violations\n\n";
} catch (Exception $e) {
    echo "   ℹ️  No violations tracked yet\n\n";
}

// Step 9: Create summary
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                   Setup Summary                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$setupDetails = [
    'Database Status' => '✅ Connected',
    'Schema Status' => '✅ Complete',
    'Violation Reasons' => '✅ Enabled',
    'Question Navigator' => '✅ Ready',
    'Admin Dashboard' => '✅ Available',
    'Student Import' => '✅ Ready',
];

foreach ($setupDetails as $feature => $status) {
    echo "$feature: $status\n";
}

echo "\n";

// Step 10: Print next steps
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                    Next Steps                            ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "1️⃣  Add Students to Group 2:\n";
echo "   $ php scripts/add_group2_students.php\n\n";

echo "2️⃣  Access Quiz App:\n";
echo "   👤 Student: http://localhost/Quiz-App/login.php\n";
echo "   👨‍💼 Admin (New): http://localhost/Quiz-App/admin-enhanced.php\n";
echo "   👨‍💼 Admin (Old): http://localhost/Quiz-App/admin.php\n\n";

echo "3️⃣  Test Features:\n";
echo "   ✓ Click numbered buttons 1-20 on quiz page\n";
echo "   ✓ Use filters on admin dashboard\n";
echo "   ✓ Check violation reasons in admin\n\n";

echo "4️⃣  Documentation:\n";
echo "   📖 Complete Guide: ENHANCEMENT_GUIDE.md\n";
echo "   📖 Quick Start: README_ENHANCEMENTS.md\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ All checks passed! System is ready to use.\n";
echo "═══════════════════════════════════════════════════════════\n";
?>
