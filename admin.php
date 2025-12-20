<?php
session_start();
require __DIR__ . '/db.php';
$pdo = db();

// Check authentication
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.html');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.html');
    exit;
}

// Redirect to enhanced dashboard to keep a single admin UI
header('Location: admin-enhanced.php');
exit;

$adminGroup = $_SESSION['admin_group'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Admin is logged in, show dashboard
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();

// Get sessions for this group only
$sessStmt = $pdo->prepare('SELECT * FROM sessions WHERE `group` = ? ORDER BY last_saved DESC');
$sessStmt->execute([$adminGroup]);
$sessions = $sessStmt->fetchAll();

// Get violations for this group only
$violStmt = $pdo->prepare('SELECT v.identifier, COUNT(*) as count FROM violations v JOIN sessions s ON v.identifier = s.identifier WHERE s.`group` = ? GROUP BY v.identifier ORDER BY v.identifier');
$violStmt->execute([$adminGroup]);
$violations = $violStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Group <?php echo $adminGroup; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Design tokens */
        :root {
            --brand-start: #6366f1; /* indigo-500 */
            --brand-end: #7c3aed;   /* violet-600 */
            --accent: #10b981;      /* emerald-500 */
            --surface: #ffffff;
            --muted: #f8fafc;       /* slate-50 */
            --border: #e5e7eb;      /* gray-200 */
            --text-primary: #0f172a; /* slate-900 */
            --text-muted: #64748b;  /* slate-500 */
        }

        .gradient-bg { background: linear-gradient(135deg, var(--brand-start) 0%, var(--brand-end) 100%); }
        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #eab308, #3b82f6);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease infinite;
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* React-like components */
        .ui-card {
            background: var(--surface);
            border-radius: 1.25rem;
            box-shadow: 0 10px 20px rgba(2, 6, 23, 0.06);
            border: 1px solid var(--border);
        }
        .ui-card-header { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; color: var(--text-primary); }
        .ui-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .6rem; border-radius:.75rem; background: rgba(124,58,237,.12); color:#6d28d9; font-weight:600; font-size:.75rem; }
        .ui-btn { transition: all .2s ease; box-shadow: 0 6px 12px rgba(2, 6, 23, 0.08); }
        .ui-btn:hover { transform: translateY(-1px); }
        .ui-input { border-width: 2px; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class='bx bxs-dashboard text-3xl'></i>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold">Admin Dashboard</h1>
                        <div class="ui-chip mt-1">
                            <i class='bx bxs-group'></i>
                            Group <?php echo $adminGroup; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden md:inline text-white/80 text-sm">Welcome, <?php echo htmlspecialchars($adminUsername); ?></span>
                    <a href="proctor.php" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition font-semibold flex items-center ui-btn">
                        <i class='bx bx-video text-lg mr-2'></i>
                        Proctor View
                    </a>
                    <a href="?logout" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition font-semibold flex items-center ui-btn">
                        <i class='bx bx-log-out text-lg mr-2'></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Quiz Configuration -->
            <div class="ui-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class='bx bx-cog text-2xl mr-2 text-purple-600'></i>
                    Quiz Configuration
                </h2>
                <form id="cfgForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Questions</label>
                        <input 
                            type="number" 
                            id="qcount" 
                            value="<?php echo $cfg['question_count'] ?? 40; ?>" 
                            min="1" 
                            max="100"
                            class="ui-input w-full px-4 py-3 rounded-lg border-gray-200 focus:border-purple-500 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Duration (minutes)</label>
                        <input 
                            type="number" 
                            id="minutes" 
                            value="<?php echo $cfg['exam_minutes'] ?? 60; ?>" 
                            min="5" 
                            max="300"
                            class="ui-input w-full px-4 py-3 rounded-lg border-gray-200 focus:border-purple-500 focus:outline-none"
                        >
                    </div>
                    <button 
                        type="button" 
                        id="saveCfg"
                        class="ui-btn w-full bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-purple-900 transition flex items-center justify-center"
                    >
                        <i class='bx bx-save text-xl mr-2'></i>
                        Save Configuration
                    </button>
                </form>
            </div>

            <!-- Import Questions -->
            <div class="ui-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class='bx bx-upload text-2xl mr-2 text-blue-600'></i>
                    Import Questions
                </h2>
                <form id="questionForm" class="space-y-4">
                    <div class="border-2 border-dashed border-blue-200 rounded-xl p-4 bg-blue-50/50 hover:border-blue-400 transition">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Upload .md/.txt File (Group <?php echo $adminGroup; ?>)</p>
                                <p class="text-xs text-gray-500">Format: # Group, ## Question, Option, ~~Correct~~</p>
                            </div>
                            <a href="/Quiz-App/samples/sample_questions_group<?php echo $adminGroup; ?>.md" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1" download>
                                <i class='bx bx-download'></i> Download MD sample
                            </a>
                        </div>
                        <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-blue-200 hover:border-blue-400 cursor-pointer shadow-sm">
                            <i class='bx bx-upload text-xl text-blue-600 mr-2'></i>
                            <span class="text-sm font-semibold text-gray-700">Choose Markdown/Text File</span>
                            <input 
                                type="file" 
                                id="questionFile" 
                                accept=".md,.txt"
                                class="hidden"
                            >
                        </label>
                        <p id="questionFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                    </div>
                    <button 
                        type="button" 
                        id="importQuestions"
                        class="ui-btn w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center justify-center"
                    >
                        <i class='bx bx-upload text-xl mr-2'></i>
                        Import Questions (MD/TXT)
                    </button>

                    <div class="border-2 border-dashed border-blue-200 rounded-xl p-4 bg-blue-50/50 hover:border-blue-400 transition mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Upload .csv File (Group <?php echo $adminGroup; ?>)</p>
                                <p class="text-xs text-gray-500">Headers: Group,Category,Prompt,Option A,Option B,Option C,Option D,Answer</p>
                            </div>
                            <a href="/Quiz-App/samples/sample_questions_group<?php echo $adminGroup; ?>.csv" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1" download>
                                <i class='bx bx-download'></i> Download CSV sample
                            </a>
                        </div>
                        <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-blue-200 hover:border-blue-400 cursor-pointer shadow-sm">
                            <i class='bx bx-upload text-xl text-blue-600 mr-2'></i>
                            <span class="text-sm font-semibold text-gray-700">Choose CSV File</span>
                            <input 
                                type="file" 
                                id="questionCsvFile" 
                                accept=".csv"
                                class="hidden"
                            >
                        </label>
                        <p id="questionCsvFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                    </div>
                    <button 
                        type="button" 
                        id="importQuestionsCsv"
                        class="ui-btn w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center justify-center"
                    >
                        <i class='bx bx-upload text-xl mr-2'></i>
                        Import Questions (CSV)
                    </button>
                </form>
            </div>

            <!-- Delete Questions -->
            <div class="ui-card p-6">
                <h2 class="ui-card-header text-xl font-bold">
                    <i class='bx bx-trash text-2xl text-red-600'></i>
                    <span>Delete Questions</span>
                </h2>
                <form id="deleteQuestionForm" class="space-y-4">
                    <div class="border-2 border-dashed border-red-200 rounded-xl p-4 bg-red-50/50 hover:border-red-400 transition">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Upload .md/.txt or .csv File</p>
                                <p class="text-xs text-gray-500">Questions matching the prompt will be deleted</p>
                            </div>
                        </div>
                        <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-red-200 hover:border-red-400 cursor-pointer shadow-sm">
                            <i class='bx bx-trash text-xl text-red-600 mr-2'></i>
                            <span class="text-sm font-semibold text-gray-700">Choose File to Delete</span>
                            <input 
                                type="file" 
                                id="deleteQuestionFile" 
                                accept=".md,.txt,.csv"
                                class="hidden"
                            >
                        </label>
                        <p id="deleteQuestionFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                    </div>
                    <button 
                        type="button" 
                        id="deleteQuestions"
                        class="ui-btn w-full bg-gradient-to-r from-red-600 to-red-800 text-white font-bold py-3 px-6 rounded-lg hover:from-red-700 hover:to-red-900 transition flex items-center justify-center"
                    >
                        <i class='bx bx-trash text-xl mr-2'></i>
                        Delete Matching Questions
                    </button>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="ui-card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class='bx bx-bar-chart text-2xl mr-2 text-purple-600'></i>
                    Quick Statistics
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="text-3xl font-bold text-blue-600"><?php echo count($sessions); ?></div>
                        <div class="text-sm text-gray-600 mt-1">Total Sessions</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="text-3xl font-bold text-green-600"><?php echo count(array_filter($sessions, fn($s) => $s['submitted'] == 1)); ?></div>
                        <div class="text-sm text-gray-600 mt-1">Submitted</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <div class="text-3xl font-bold text-yellow-600"><?php echo count($violations); ?></div>
                        <div class="text-sm text-gray-600 mt-1">With Violations</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div class="text-3xl font-bold text-purple-600"><?php echo $cfg['question_count'] ?? 40; ?></div>
                        <div class="text-sm text-gray-600 mt-1">Questions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Add: Question -->
        <div class="ui-card p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-plus-circle text-2xl mr-2 text-blue-600'></i>
                Add Question (Group <?php echo $adminGroup; ?>)
            </h2>
            <form id="addQuestionForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Prompt</label>
                    <textarea id="qPrompt" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" rows="3" placeholder="Enter question text"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Option A</label>
                    <input id="qA" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Option B</label>
                    <input id="qB" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Option C</label>
                    <input id="qC" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Option D</label>
                    <input id="qD" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Correct Option</label>
                    <select id="qAnswer" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category (optional)</label>
                    <input id="qCategory" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:outline-none" placeholder="e.g., General" />
                </div>
                <div class="md:col-span-2">
                    <button type="button" id="addQuestionBtn" class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center justify-center shadow-md">
                        <i class='bx bx-plus-circle text-xl mr-2'></i>
                        Add Question
                    </button>
                </div>
            </form>
        </div>

        <!-- Manual Add: Student -->
        <div class="ui-card p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-user-plus text-2xl mr-2 text-green-600'></i>
                Add Student (Group <?php echo $adminGroup; ?>)
            </h2>
            <form id="addStudentForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                    <input id="sName" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-green-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Matric / Identifier</label>
                    <input id="sId" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-green-500 focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Phone (optional)</label>
                    <input id="sPhone" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-green-500 focus:outline-none" />
                </div>
                <div class="md:col-span-3">
                    <button type="button" id="addStudentBtn" class="w-full bg-gradient-to-r from-green-600 to-green-800 text-white font-bold py-3 px-6 rounded-lg hover:from-green-700 hover:to-green-900 transition flex items-center justify-center shadow-md">
                        <i class='bx bx-user-plus text-xl mr-2'></i>
                        Add Student
                    </button>
                </div>
            </form>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class='bx bx-group text-2xl mr-2 text-purple-600'></i>
                    Student Sessions
                </h2>
                <button onclick="refreshAccuracy()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center text-sm font-semibold">
                    <i class='bx bx-refresh text-lg mr-1'></i>
                    Refresh Accuracy
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Progress</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Accuracy</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Violations</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Last Saved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sessions as $s): 
                            $qids = json_decode($s['question_ids_json'] ?? '[]', true) ?: [];
                            $ans = json_decode($s['answers_json'] ?? '[]', true) ?: [];
                            $prog = count($qids) ? intval(count($ans)/count($qids)*100) : 0;
                        ?>
                        <tr class="border-t hover:bg-gray-50" data-identifier="<?php echo htmlspecialchars($s['identifier'] ?? ''); ?>">
                            <td class="py-3 px-4"><?php echo htmlspecialchars($s['name'] ?? 'N/A'); ?></td>
                            <td class="py-3 px-4 font-mono text-xs"><?php echo htmlspecialchars($s['identifier'] ?? ''); ?></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-purple-600 h-2 rounded-full js-progress-bar" style="width: <?php echo $prog; ?>%"></div>
                                    </div>
                                    <span class="text-xs js-progress-text"><?php echo $prog; ?>%</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-sm font-semibold js-accuracy <?php echo ($s['submitted']==1?'text-green-600':'text-gray-400'); ?>">
                                    <?php echo $s['submitted'] == 1 ? number_format($s['accuracy_score'] ?? 0, 1) . '%' : '-'; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold js-violations <?php echo ($s['violations']>=3?'bg-red-100 text-red-800':($s['violations']>=1?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800')); ?>">
                                    <?php echo $s['violations']; ?>/3
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold js-status <?php echo ($s['submitted']==1?'bg-blue-100 text-blue-800':'bg-gray-100 text-gray-800'); ?>"><?php echo $s['submitted']==1?'Submitted':'In Progress'; ?></span>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-600 js-last-saved"><?php echo htmlspecialchars($s['last_saved'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Import Students -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-upload text-2xl mr-2 text-green-600'></i>
                Import Students (CSV) - Group <?php echo $adminGroup; ?>
            </h2>
            <form id="studentForm" class="space-y-4">
                <div class="border-2 border-dashed border-green-200 rounded-xl p-4 bg-green-50/50 hover:border-green-400 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Upload CSV File</p>
                            <p class="text-xs text-gray-500">Format: Name, Matric/ID, Phone (CSV with headers)</p>
                        </div>
                        <a href="/Quiz-App/samples/sample_students_group<?php echo $adminGroup; ?>.csv" class="text-xs font-semibold text-green-700 hover:text-green-900 flex items-center gap-1" download>
                            <i class='bx bx-download'></i> Download sample
                        </a>
                    </div>
                    <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-green-200 hover:border-green-400 cursor-pointer shadow-sm">
                        <i class='bx bx-upload text-xl text-green-600 mr-2'></i>
                        <span class="text-sm font-semibold text-gray-700">Choose CSV File</span>
                        <input 
                            type="file" 
                            id="studentFile" 
                            accept=".csv,.txt"
                            class="hidden"
                        >
                    </label>
                    <p id="studentFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                </div>
                <button 
                    type="button" 
                    id="importStudents"
                    class="ui-btn w-full bg-gradient-to-r from-green-600 to-green-800 text-white font-bold py-3 px-6 rounded-lg hover:from-green-700 hover:to-green-900 transition flex items-center justify-center"
                >
                    <i class='bx bx-upload text-xl mr-2'></i>
                    Import Students
                </button>
            </form>
        </div>

        <!-- Violations Summary -->
        <div class="ui-card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-error text-2xl mr-2 text-red-600'></i>
                Violations Summary (Sorted by Student)
            </h2>
            <?php if (empty($violations)): ?>
                <p class="text-gray-600">No violations recorded yet.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach($violations as $v): ?>
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($v['identifier']); ?></div>
                            <div class="text-2xl font-bold text-red-600"><?php echo $v['count']; ?></div>
                            <div class="text-sm text-gray-600">violation(s)</div>
                            <a href="proctor.php?student=<?php echo urlencode($v['identifier']); ?>" class="text-xs text-purple-600 hover:text-purple-800 mt-2 inline-block">
                                View Details →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">
                <span class="text-gray-600">Made by </span>
                <span class="text-2xl font-bold gradient-text">MAVIS</span>
            </p>
        </div>
    </footer>

    <script>
        const API = '/Quiz-App/api';

        document.getElementById('saveCfg').onclick = async () => {
            const questionCount = Number(document.getElementById('qcount').value);
            const examMinutes = Number(document.getElementById('minutes').value);
            
            if (questionCount < 1 || questionCount > 100) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Questions must be between 1 and 100'
                });
                return;
            }
            
            if (examMinutes < 5 || examMinutes > 300) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Duration must be between 5 and 300 minutes'
                });
                return;
            }

            Swal.fire({
                title: 'Saving...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch(API+'/config.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({questionCount, examMinutes})
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Quiz configuration updated successfully',
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error('Failed to save');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save configuration'
                });
            }
        };

        function computeProgress(sessionRow) {
            try {
                const ids = JSON.parse(sessionRow.question_ids_json || '[]');
                const answers = JSON.parse(sessionRow.answers_json || '{}');
                const answered = Array.isArray(answers)
                    ? answers.filter(a => a !== null && a !== '').length
                    : Object.values(answers).filter(a => a !== null && a !== '').length;
                if (!ids.length) return 0;
                return Math.min(100, Math.round((answered / ids.length) * 100));
            } catch (e) {
                return 0;
            }
        }

        function updateBadge(el, value) {
            if (!el) return;
            el.textContent = `${value}/3`;
            el.classList.remove('bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800', 'bg-green-100', 'text-green-800');
            if (value >= 3) {
                el.classList.add('bg-red-100', 'text-red-800');
            } else if (value >= 1) {
                el.classList.add('bg-yellow-100', 'text-yellow-800');
            } else {
                el.classList.add('bg-green-100', 'text-green-800');
            }
        }

        function updateStatus(el, submitted) {
            if (!el) return;
            el.textContent = submitted ? 'Submitted' : 'In Progress';
            el.classList.remove('bg-blue-100', 'text-blue-800', 'bg-gray-100', 'text-gray-800');
            if (submitted) {
                el.classList.add('bg-blue-100', 'text-blue-800');
            } else {
                el.classList.add('bg-gray-100', 'text-gray-800');
            }
        }

        function updateRow(sessionRow, accuracyRow) {
            const id = sessionRow.identifier;
            const tr = document.querySelector(`tr[data-identifier="${id}"]`);
            if (!tr) return;

            const progress = computeProgress(sessionRow);
            const progressBar = tr.querySelector('.js-progress-bar');
            const progressText = tr.querySelector('.js-progress-text');
            if (progressBar) progressBar.style.width = `${progress}%`;
            if (progressText) progressText.textContent = `${progress}%`;

            const accuracyEl = tr.querySelector('.js-accuracy');
            const submitted = accuracyRow ? !!accuracyRow.submitted : !!sessionRow.submitted;
            if (accuracyEl) {
                if (submitted) {
                    const accVal = accuracyRow ? (accuracyRow.accuracy ?? accuracyRow.accuracy_score ?? 0) : (sessionRow.accuracy_score ?? 0);
                    accuracyEl.textContent = `${Number(accVal).toFixed(1)}%`;
                    accuracyEl.classList.remove('text-gray-400');
                    accuracyEl.classList.add('text-green-600');
                } else {
                    accuracyEl.textContent = '-';
                    accuracyEl.classList.add('text-gray-400');
                    accuracyEl.classList.remove('text-green-600');
                }
            }

            const vioEl = tr.querySelector('.js-violations');
            const vioCount = accuracyRow && typeof accuracyRow.violations === 'number' ? accuracyRow.violations : (sessionRow.violations ?? 0);
            updateBadge(vioEl, vioCount);

            const statusEl = tr.querySelector('.js-status');
            updateStatus(statusEl, submitted);

            const lastSaved = tr.querySelector('.js-last-saved');
            if (lastSaved) lastSaved.textContent = sessionRow.last_saved || '—';
        }

        async function pollDashboard() {
            try {
                const [accuracyRes, sessionsRes] = await Promise.all([
                    fetch(API + '/accuracy.php'),
                    fetch(API + '/sessions.php')
                ]);

                if (!accuracyRes.ok || !sessionsRes.ok) throw new Error('Network error');

                const accuracyData = await accuracyRes.json();
                const sessionsData = await sessionsRes.json();
                const accuracyMap = new Map();
                (accuracyData.students || []).forEach(s => accuracyMap.set(s.identifier, s));

                sessionsData.forEach(sessionRow => {
                    const accRow = accuracyMap.get(sessionRow.identifier);
                    updateRow(sessionRow, accRow);
                });
            } catch (err) {
                console.error('Dashboard polling failed', err);
            }
        }

        function refreshAccuracy() {
            pollDashboard();
        }

        // Show selected filenames for nicer UX
        const qFile = document.getElementById('questionFile');
        const qFileName = document.getElementById('questionFileName');
        if (qFile && qFileName) {
            qFile.addEventListener('change', () => {
                qFileName.textContent = qFile.files.length ? qFile.files[0].name : 'No file chosen';
            });
        }

        const qCsvFile = document.getElementById('questionCsvFile');
        const qCsvFileName = document.getElementById('questionCsvFileName');
        if (qCsvFile && qCsvFileName) {
            qCsvFile.addEventListener('change', () => {
                qCsvFileName.textContent = qCsvFile.files.length ? qCsvFile.files[0].name : 'No file chosen';
            });
        }

        const delFile = document.getElementById('deleteQuestionFile');
        const delFileName = document.getElementById('deleteQuestionFileName');
        if (delFile && delFileName) {
            delFile.addEventListener('change', () => {
                delFileName.textContent = delFile.files.length ? delFile.files[0].name : 'No file chosen';
            });
        }

        const sFile = document.getElementById('studentFile');
        const sFileName = document.getElementById('studentFileName');
        if (sFile && sFileName) {
            sFile.addEventListener('change', () => {
                sFileName.textContent = sFile.files.length ? sFile.files[0].name : 'No file chosen';
            });
        }

        // Question Import Handler
        document.getElementById('importQuestions').onclick = async () => {
            const file = document.getElementById('questionFile').files[0];
            if (!file) {
                Swal.fire('Error', 'Please select a file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(API + '/question_import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Questions Imported',
                        text: data.message + `\n\nImported: ${data.imported}/${data.total}`,
                        confirmButtonColor: '#3085d6'
                    });
                    document.getElementById('questionFile').value = '';
                    setTimeout(pollDashboard, 1000);
                } else {
                    Swal.fire('Error', data.error || 'Import failed', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Import failed: ' + err.message, 'error');
            }
        };

        // Question CSV Import Handler
        document.getElementById('importQuestionsCsv').onclick = async () => {
            const file = document.getElementById('questionCsvFile').files[0];
            if (!file) {
                Swal.fire('Error', 'Please select a CSV file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(API + '/question_import_csv.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Questions Imported (CSV)',
                        text: data.message + `\n\nImported: ${data.imported}/${data.total}`,
                        confirmButtonColor: '#3085d6'
                    });
                    document.getElementById('questionCsvFile').value = '';
                    setTimeout(pollDashboard, 1000);
                } else {
                    Swal.fire('Error', data.error || 'Import failed', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Import failed: ' + err.message, 'error');
            }
        };

        // Manual Add Question
        document.getElementById('addQuestionBtn').onclick = async () => {
            const prompt = document.getElementById('qPrompt').value.trim();
            const A = document.getElementById('qA').value.trim();
            const B = document.getElementById('qB').value.trim();
            const C = document.getElementById('qC').value.trim();
            const D = document.getElementById('qD').value.trim();
            const ansKey = document.getElementById('qAnswer').value;
            const category = document.getElementById('qCategory').value.trim() || 'General';

            const map = { A, B, C, D };
            const answer = map[ansKey];
            if (!prompt || !A || !B || !C || !D || !answer) {
                Swal.fire('Error', 'Please fill all fields', 'error');
                return;
            }

            try {
                const res = await fetch(API + '/question_add.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt, option_a: A, option_b: B, option_c: C, option_d: D, answer, category })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Question Added', text: data.message });
                    document.getElementById('addQuestionForm').reset();
                    setTimeout(pollDashboard, 1000);
                } else {
                    Swal.fire('Error', data.error || 'Add failed', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Add failed: ' + err.message, 'error');
            }
        };

        // Manual Add Student
        document.getElementById('addStudentBtn').onclick = async () => {
            const name = document.getElementById('sName').value.trim();
            const identifier = document.getElementById('sId').value.trim();
            const phone = document.getElementById('sPhone').value.trim();

            if (!name || !identifier) {
                Swal.fire('Error', 'Name and Matric/Identifier are required', 'error');
                return;
            }

            try {
                const res = await fetch(API + '/student_add.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, identifier, phone })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Student Added', text: data.message });
                    document.getElementById('addStudentForm').reset();
                } else {
                    Swal.fire('Error', data.error || 'Add failed', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Add failed: ' + err.message, 'error');
            }
        };

        // Delete Questions Handler
        document.getElementById('deleteQuestions').onclick = async () => {
            const file = document.getElementById('deleteQuestionFile').files[0];
            if (!file) {
                Swal.fire('Error', 'Please select a file with questions to delete', 'error');
                return;
            }

            const result = await Swal.fire({
                title: 'Confirm Deletion',
                text: 'This will permanently delete all matching questions. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete them',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(API + '/question_delete.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deletion Complete',
                        html: `
                            <p>${data.message}</p>
                            <p class="mt-2">Deleted: ${data.deleted} / ${data.total}</p>
                            ${data.not_found > 0 ? `<p class="text-yellow-600">Not found: ${data.not_found}</p>` : ''}
                        `,
                        confirmButtonColor: '#3085d6'
                    });
                    document.getElementById('deleteQuestionFile').value = '';
                    document.getElementById('deleteQuestionFileName').textContent = 'No file chosen';
                    setTimeout(pollDashboard, 1000);
                } else {
                    Swal.fire('Error', data.error || 'Deletion failed', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Deletion failed: ' + err.message, 'error');
            }
        };


        // Student Import Handler
        document.getElementById('importStudents').onclick = async () => {
            const file = document.getElementById('studentFile').files[0];
            if (!file) {
                Swal.fire('Error', 'Please select a file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(API + '/student_import.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    let msg = `Imported: ${data.imported}/${data.total}`;
                    if (data.duplicates > 0) msg += `\nDuplicates skipped: ${data.duplicates}`;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Students Imported',
                        text: data.message + '\n\n' + msg,
                        confirmButtonColor: '#10b981'
                    });
                    document.getElementById('studentFile').value = '';
                } else {
                    let errMsg = data.error || 'Import failed';
                    if (data.details && data.details.length > 0) {
                        errMsg += '\n\n' + data.details.slice(0, 3).join('\n');
                    }
                    Swal.fire('Error', errMsg, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Import failed: ' + err.message, 'error');
            }
        };

        setInterval(pollDashboard, 5000);
        pollDashboard();
    </script>
</body>
</html>
