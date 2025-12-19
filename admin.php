<?php
session_start();
require __DIR__ . '/db.php';
$pdo = db();

// Simple admin authentication (you can enhance this)
$adminPassword = 'admin123'; // Change this to a secure password

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $loginError = 'Invalid password';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <style>
            .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
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
        </style>
    </head>
    <body class="gradient-bg min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 shadow-2xl w-full max-w-md">
            <div class="text-center mb-6">
                <i class='bx bxs-shield text-6xl text-purple-600 mb-4'></i>
                <h1 class="text-3xl font-bold text-gray-800">Admin Login</h1>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input 
                        type="password" 
                        name="admin_password" 
                        required
                        class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none"
                        placeholder="Enter admin password"
                    >
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-purple-900 transition">
                    Login
                </button>
            </form>
            
            <?php if (isset($loginError)): ?>
                <p class="text-red-600 text-sm mt-4 text-center"><?php echo $loginError; ?></p>
            <?php endif; ?>
            
            <div class="text-center mt-6">
                <a href="login.php" class="text-purple-600 hover:text-purple-800 text-sm">Back to Student Login</a>
            </div>
        </div>
        
        <footer class="fixed bottom-4 left-0 right-0 text-center">
            <p class="text-sm">
                <span class="text-white">&copy; Web Dev </span>
                <span class="text-lg font-bold gradient-text">Group 1</span>
            </p>
        </footer>
    </body>
    </html>
    <?php
    exit;
}

// Admin is logged in, show dashboard
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
$sessions = $pdo->query('SELECT * FROM sessions ORDER BY last_saved DESC')->fetchAll();
$violations = $pdo->query('SELECT identifier, COUNT(*) as count FROM violations GROUP BY identifier ORDER BY identifier')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class='bx bxs-dashboard text-4xl mr-3'></i>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold">Admin Dashboard</h1>
                        <p class="text-white/80 text-sm">Quiz Management System</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="proctor.php" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition font-semibold flex items-center">
                        <i class='bx bx-video text-xl mr-2'></i>
                        Proctor View
                    </a>
                    <a href="?logout" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition font-semibold flex items-center">
                        <i class='bx bx-log-out text-xl mr-2'></i>
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
            <div class="bg-white rounded-2xl p-6 shadow-lg">
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
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none"
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
                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none"
                        >
                    </div>
                    <button 
                        type="button" 
                        id="saveCfg"
                        class="w-full bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-purple-900 transition flex items-center justify-center"
                    >
                        <i class='bx bx-save text-xl mr-2'></i>
                        Save Configuration
                    </button>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-2xl p-6 shadow-lg">
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

        <!-- Violations Summary -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
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

        setInterval(pollDashboard, 5000);
        pollDashboard();
    </script>
</body>
</html>
