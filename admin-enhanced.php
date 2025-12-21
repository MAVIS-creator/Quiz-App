<?php
/**
 * Modern React-style Admin Dashboard
 * Replaces the old admin.php with enhanced UI and filtering
 */
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

$adminGroup = $_SESSION['admin_group'] ?? 1;
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Get config
$cfgStmt = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE id=?');
$cfgStmt->execute([$adminGroup]);
$cfg = $cfgStmt->fetch();
if (!$cfg) {
    $cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch() ?: ['exam_minutes' => 60, 'question_count' => 40];
}

// Get sessions with filtering
$filter = $_GET['filter'] ?? 'all'; // all, today, submitted, in-progress, booted
$filterDate = $_GET['date'] ?? date('Y-m-d');

$query = 'SELECT * FROM sessions WHERE `group` = ?';
$params = [$adminGroup];

switch ($filter) {
    case 'today':
        $query .= ' AND DATE(created_at) = ?';
        $params[] = date('Y-m-d');
        break;
    case 'submitted':
        $query .= ' AND submitted = 1';
        break;
    case 'in-progress':
        $query .= ' AND submitted = 0';
        break;
    case 'booted':
        $query .= ' AND status = "booted"';
        break;
    case 'date':
        $query .= ' AND DATE(created_at) = ?';
        $params[] = $filterDate;
        break;
}

$query .= ' ORDER BY created_at DESC';
$sessStmt = $pdo->prepare($query);
$sessStmt->execute($params);
$sessions = $sessStmt->fetchAll();

// Get violations
$violStmt = $pdo->prepare('SELECT v.identifier, v.type, v.reason, COUNT(*) as count FROM violations v JOIN sessions s ON v.identifier = s.identifier WHERE s.`group` = ? GROUP BY v.identifier, v.type ORDER BY v.identifier');
$violStmt->execute([$adminGroup]);
$violations = $violStmt->fetchAll();

// Get student stats
$statsStmt = $pdo->prepare('SELECT COUNT(DISTINCT identifier) as total, SUM(CASE WHEN submitted = 1 THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN violations > 0 THEN 1 ELSE 0 END) as flagged FROM sessions WHERE `group` = ?');
$statsStmt->execute([$adminGroup]);
$stats = $statsStmt->fetch();

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
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <style>
        :root {
            --brand-start: #6366f1;
            --brand-end: #7c3aed;
            --accent: #10b981;
            --surface: #ffffff;
            --muted: #f8fafc;
            --border: #e5e7eb;
            --text-primary: #0f172a;
            --text-muted: #64748b;
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

        .ui-card {
            background: var(--surface);
            border-radius: 1.25rem;
            box-shadow: 0 10px 20px rgba(2, 6, 23, 0.06);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .ui-card:hover {
            box-shadow: 0 15px 30px rgba(2, 6, 23, 0.1);
            transform: translateY(-2px);
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card.completed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-card.flagged { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

        .ui-btn {
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(2, 6, 23, 0.08);
        }

        .ui-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 6, 23, 0.12);
        }

        .filter-chip {
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid var(--border);
            background: white;
        }

        .filter-chip.active {
            background: var(--brand-start);
            color: white;
            border-color: var(--brand-start);
        }

        .table-row:hover {
            background-color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.35rem 0.6rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
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
                        <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                        <p class="text-white/80 text-sm">Group <?php echo $adminGroup; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-right">
                        <p class="text-white/90 font-medium"><?php echo htmlspecialchars($adminUsername); ?></p>
                        <p class="text-white/60 text-sm">Administrator</p>
                    </div>
                    <a href="?logout=1" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all duration-200">
                        <i class='bx bx-log-out mr-1'></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card">
                <div>
                    <p class="text-white/80 text-sm">Total Students</p>
                    <p class="text-4xl font-bold"><?php echo $stats['total'] ?? 0; ?></p>
                </div>
                <i class='bx bxs-group text-6xl opacity-30'></i>
            </div>

            <div class="stat-card completed">
                <div>
                    <p class="text-white/80 text-sm">Completed</p>
                    <p class="text-4xl font-bold"><?php echo $stats['completed'] ?? 0; ?></p>
                </div>
                <i class='bx bxs-check-circle text-6xl opacity-30'></i>
            </div>

            <div class="stat-card flagged">
                <div>
                    <p class="text-white/80 text-sm">Flagged</p>
                    <p class="text-4xl font-bold"><?php echo $stats['flagged'] ?? 0; ?></p>
                </div>
                <i class='bx bxs-error text-6xl opacity-30'></i>
            </div>
        </div>

        <!-- Import & Management Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <a href="#" id="openQuestionModal" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-upload text-3xl text-blue-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Question Management</h3>
                <p class="text-sm text-gray-600">Import, export, delete</p>
            </a>

            <a href="#" id="openStudentModal" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-user-plus text-3xl text-green-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Student Management</h3>
                <p class="text-sm text-gray-600">Import and add students</p>
            </a>

            <a href="#" id="openConfigModal" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-cog text-3xl text-purple-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Configuration</h3>
                <p class="text-sm text-gray-600">Manage exam settings</p>
            </a>

            <a href="proctor.php" class="ui-card p-6 text-center cursor-pointer group" id="proctor">
                <div class="inline-block p-3 bg-gradient-to-br from-red-100 to-red-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-shield-quarter text-3xl text-red-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Proctor Console</h3>
                <p class="text-sm text-gray-600">Open live proctoring</p>
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="ui-card p-6">
                <h3 class="font-bold text-gray-800 mb-2 flex items-center"><i class='bx bx-bar-chart text-xl mr-2 text-blue-600'></i>Quick Statistics</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div id="statTotalSessions" class="text-3xl font-bold text-blue-600"><?php echo count($sessions); ?></div>
                        <div class="text-sm text-gray-600 mt-1">Total Sessions</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div id="statSubmitted" class="text-3xl font-bold text-green-600"><?php echo count(array_filter($sessions, fn($s) => $s['submitted'] == 1)); ?></div>
                        <div class="text-sm text-gray-600 mt-1">Submitted</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <div id="statViolations" class="text-3xl font-bold text-yellow-600"><?php echo count($violations); ?></div>
                        <div class="text-sm text-gray-600 mt-1">With Violations</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div id="statQuestions" class="text-3xl font-bold text-purple-600"><?php echo $cfg['question_count'] ?? 40; ?></div>
                        <div class="text-sm text-gray-600 mt-1">Questions</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms moved to modals (below). Page simplified. -->

        <!-- Sessions Section -->
        <div class="ui-card p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class='bx bx-list-check mr-2'></i>Student Sessions
                </h2>
                <div class="flex items-center gap-3">
                    <button onclick="refreshAccuracy()" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center text-sm font-semibold">
                        <i class='bx bx-refresh text-lg mr-1'></i>
                        Refresh
                    </button>
                    <span class="text-sm bg-gray-100 px-3 py-1 rounded-full font-semibold text-gray-700">
                        <?php echo count($sessions); ?> Total
                    </span>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex gap-2 md:flex-wrap overflow-x-auto -mx-2 px-2">
                <button type="button" data-filter="all" class="filter-chip <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class='bx bx-filter-alt'></i> All
                </button>
                <button type="button" data-filter="today" class="filter-chip <?php echo $filter === 'today' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar-today'></i> Today
                </button>
                <button type="button" data-filter="submitted" class="filter-chip <?php echo $filter === 'submitted' ? 'active' : ''; ?>">
                    <i class='bx bx-check'></i> Submitted
                </button>
                <button type="button" data-filter="in-progress" class="filter-chip <?php echo $filter === 'in-progress' ? 'active' : ''; ?>">
                    <i class='bx bx-hourglass'></i> In Progress
                </button>
                <button type="button" data-filter="booted" class="filter-chip <?php echo $filter === 'booted' ? 'active' : ''; ?>">
                    <i class='bx bx-x-circle'></i> Booted
                </button>
                <input type="date" id="dateFilter" value="<?php echo $filterDate; ?>" class="px-3 py-2 border-2 border-gray-300 rounded-lg text-sm font-medium">
            </div>

            <!-- Sessions Table (Desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Matric</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Progress</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700">Accuracy</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700">Violations</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Last Saved</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsBody">
                        <?php foreach ($sessions as $session): 
                            $totalQuestions = (int)($session['questions_total'] ?? 0);
                            $answered = (int)($session['questions_answered'] ?? 0);
                            $progressPct = $totalQuestions > 0 ? min(100, ($answered / $totalQuestions) * 100) : 0;
                            $accuracyVal = isset($session['accuracy']) ? $session['accuracy'] : ($session['accuracy_score'] ?? null);
                            $violCount = (int)($session['violations'] ?? 0);
                        ?>
                        <tr class="table-row border-b border-gray-200 hover:bg-gray-50" data-identifier="<?php echo htmlspecialchars($session['identifier'] ?? ''); ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($session['name']); ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($session['identifier']); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full js-progress-bar" style="width: <?php echo $progressPct; ?>%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600 js-progress-text"><?php echo round($progressPct); ?>%</span>
                                </div>
                                <span class="text-xs text-gray-600"><?php echo $answered; ?>/<?php echo $totalQuestions; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-gray-900 js-accuracy"><?php echo $session['submitted'] ? number_format((float)$accuracyVal, 1) . '%' : '-'; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge <?php echo $violCount > 0 ? 'badge-danger' : 'badge-success'; ?> js-violations">
                                    <i class='bx bx-error-circle'></i>
                                    <?php echo $violCount; ?>/3
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($session['submitted']): ?>
                                    <span class="badge badge-success js-status">
                                        <i class='bx bx-check-circle'></i>
                                        Submitted
                                    </span>
                                <?php elseif (($session['status'] ?? '') === 'booted'): ?>
                                    <span class="badge badge-danger js-status">
                                        <i class='bx bx-x-circle'></i>
                                        Booted
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info js-status">
                                        <i class='bx bx-hourglass'></i>
                                        In Progress
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 js-last-saved">
                                <?php echo isset($session['last_saved']) ? date('H:i', strtotime($session['last_saved'])) : date('H:i', strtotime($session['created_at'])); ?>
                            </td>
                            <td class="hidden md:table-cell px-4 py-3">
                                <!-- Desktop: right-click menu -->
                            </td>
                            <td class="table-cell md:hidden px-4 py-3">
                                <button class="action-menu-btn px-2 py-1 rounded hover:bg-gray-200 text-gray-600" data-identifier="<?php echo htmlspecialchars($session['identifier'] ?? ''); ?>" title="Actions">
                                    <i class='bx bx-dots-vertical text-xl'></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sessions List (Mobile) -->
            <div class="md:hidden" id="sessionsListMobile">
                <?php foreach ($sessions as $session): 
                    $totalQuestions = (int)($session['questions_total'] ?? 0);
                    $answered = (int)($session['questions_answered'] ?? 0);
                    $progressPct = $totalQuestions > 0 ? min(100, ($answered / $totalQuestions) * 100) : 0;
                    $accuracyVal = isset($session['accuracy']) ? $session['accuracy'] : ($session['accuracy_score'] ?? null);
                    $violCount = (int)($session['violations'] ?? 0);
                ?>
                <div class="border border-gray-200 rounded-xl p-4 mb-3" data-identifier="<?php echo htmlspecialchars($session['identifier'] ?? ''); ?>">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($session['name']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($session['identifier']); ?></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-xs flex-1">
                                <?php if ($session['submitted']): ?>
                                    <span class="badge badge-success"><i class='bx bx-check-circle'></i> Submitted</span>
                                <?php elseif (($session['status'] ?? '') === 'booted'): ?>
                                    <span class="badge badge-danger"><i class='bx bx-x-circle'></i> Booted</span>
                                <?php else: ?>
                                    <span class="badge badge-info"><i class='bx bx-hourglass'></i> In Progress</span>
                                <?php endif; ?>
                            </div>
                            <button class="action-menu-btn px-1 py-1 rounded hover:bg-gray-200 text-gray-600 flex-shrink-0" data-identifier="<?php echo htmlspecialchars($session['identifier'] ?? ''); ?>" title="Actions">
                                <i class='bx bx-dots-vertical text-xl'></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center gap-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full js-progress-bar" style="width: <?php echo $progressPct; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-600 js-progress-text"><?php echo round($progressPct); ?>%</span>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-700">
                            <span><i class='bx bx-target-lock mr-1'></i><span class="js-accuracy"><?php echo $session['submitted'] ? number_format((float)$accuracyVal, 1) . '%' : '-'; ?></span></span>
                            <span class="js-violations"><i class='bx bx-error-circle mr-1'></i><?php echo $violCount; ?>/3</span>
                            <span class="js-last-saved"><i class='bx bx-time mr-1'></i><?php echo isset($session['last_saved']) ? date('H:i', strtotime($session['last_saved'])) : date('H:i', strtotime($session['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($sessions)): ?>
            <div class="text-center py-12">
                <i class='bx bx-inbox text-6xl text-gray-300 mb-3'></i>
                <p class="text-gray-600">No sessions found</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Violations Section -->
        <div class="ui-card p-6 mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                <i class='bx bx-error mr-2'></i>Violations Summary
            </h2>

            <div class="space-y-4">
                <?php if (!empty($violations)): ?>
                    <?php foreach ($violations as $v): ?>
                    <div class="border-l-4 border-orange-500 bg-orange-50 p-4 rounded-r-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($v['identifier']); ?></p>
                                <p class="text-sm text-gray-700 mt-1">
                                    <i class='bx bx-error mr-1'></i>
                                    <?php echo htmlspecialchars($v['reason'] ?? $v['type']); ?>
                                </p>
                            </div>
                            <span class="badge badge-warning">
                                <?php echo $v['count']; ?> violations
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class='bx bx-check-circle text-6xl text-green-300 mb-3'></i>
                        <p class="text-gray-600">No violations recorded</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm text-gray-600">
                &copy; 2025 Quiz Administration System - Group <?php echo $adminGroup; ?>
            </p>
        </div>
    </footer>

    <!-- Modals -->
    <!-- Question Management Modal -->
    <div id="questionModal" class="modal fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-2xl sm:max-w-3xl p-6 shadow-lg relative max-h-[90vh] overflow-y-auto">
            <button class="absolute top-4 right-4 inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100" onclick="closeModal('questionModal')" aria-label="Close Question Management">
                <i class='bx bx-x text-xl'></i><span class="text-sm font-semibold">Close</span>
            </button>
            <div class="flex items-center justify-between mb-4 pr-24">
                <h3 class="text-xl font-bold">Question Management</h3>
            </div>

            <!-- Tabs -->
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" class="tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold bg-purple-600 text-white" data-tab="tab-md">
                    <i class='bx bx-file mr-1'></i> Import MD/TXT
                </button>
                <button type="button" class="tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold" data-tab="tab-csv">
                    <i class='bx bx-table mr-1'></i> Import CSV
                </button>
                <button type="button" class="tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold" data-tab="tab-delete">
                    <i class='bx bx-trash mr-1'></i> Delete
                </button>
                <button type="button" class="tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold" data-tab="tab-add">
                    <i class='bx bx-plus mr-1'></i> Add Single
                </button>
            </div>

            <!-- Tab Panels -->
            <div class="space-y-6">
                <!-- Import MD/TXT -->
                <div id="tab-md" class="qm-tab">
                    <form id="questionForm" class="space-y-4">
                        <div class="border-2 border-dashed border-blue-200 rounded-xl p-4 bg-blue-50/50">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Upload .md/.txt File (Group <?php echo $adminGroup; ?>)</p>
                                    <p class="text-xs text-gray-500">Format: # Group, ## Question, Option, ~~Correct~~</p>
                                </div>
                                <a href="/samples/sample_questions_group<?php echo $adminGroup; ?>.md" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1" download>
                                    <i class='bx bx-download'></i> Download MD sample
                                </a>
                            </div>
                            <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-blue-200 hover:border-blue-400 cursor-pointer shadow-sm">
                                <i class='bx bx-upload text-xl text-blue-600 mr-2'></i>
                                <span class="text-sm font-semibold text-gray-700">Choose Markdown/Text File</span>
                                <input type="file" id="questionFile" accept=".md,.txt" class="hidden">
                            </label>
                            <p id="questionFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                        </div>
                        <button type="button" id="importQuestions" class="ui-btn w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg">Import Questions (MD/TXT)</button>
                    </form>
                </div>

                <!-- Import CSV -->
                <div id="tab-csv" class="qm-tab hidden">
                    <form class="space-y-4">
                        <div class="border-2 border-dashed border-blue-200 rounded-xl p-4 bg-blue-50/50">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Upload .csv File (Group <?php echo $adminGroup; ?>)</p>
                                    <p class="text-xs text-gray-500">Headers: Group,Category,Prompt,Option A,Option B,Option C,Option D,Answer</p>
                                </div>
                                <a href="/samples/sample_questions_group<?php echo $adminGroup; ?>.csv" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1" download>
                                    <i class='bx bx-download'></i> Download CSV sample
                                </a>
                            </div>
                            <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-blue-200 hover:border-blue-400 cursor-pointer shadow-sm">
                                <i class='bx bx-upload text-xl text-blue-600 mr-2'></i>
                                <span class="text-sm font-semibold text-gray-700">Choose CSV File</span>
                                <input type="file" id="questionCsvFile" accept=".csv" class="hidden">
                            </label>
                            <p id="questionCsvFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                        </div>
                        <button type="button" id="importQuestionsCsv" class="ui-btn w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg">Import Questions (CSV)</button>
                    </form>
                </div>

                <!-- Delete Questions -->
                <div id="tab-delete" class="qm-tab hidden">
                    <form class="space-y-4">
                        <div class="border-2 border-dashed border-red-200 rounded-xl p-4 bg-red-50/50">
                            <p class="text-sm font-semibold text-gray-800 mb-1">Delete Questions by File</p>
                            <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-red-200 hover:border-red-400 cursor-pointer shadow-sm">
                                <i class='bx bx-trash text-xl text-red-600 mr-2'></i>
                                <span class="text-sm font-semibold text-gray-700">Choose File to Delete</span>
                                <input type="file" id="deleteQuestionFile" accept=".md,.txt,.csv" class="hidden">
                            </label>
                            <p id="deleteQuestionFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                        </div>
                        <button type="button" id="deleteQuestions" class="ui-btn w-full bg-gradient-to-r from-red-600 to-red-800 text-white font-bold py-3 px-6 rounded-lg">Delete Matching Questions</button>
                    </form>
                </div>

                <!-- Add Single Question -->
                <div id="tab-add" class="qm-tab hidden">
                    <div class="border-t pt-4">
                        <h4 class="font-semibold mb-3">Add Single Question</h4>
                        <form id="addQuestionForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Prompt</label>
                                <textarea id="qPrompt" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" rows="3" placeholder="Enter question text"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Option A</label>
                                <input id="qA" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Option B</label>
                                <input id="qB" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Option C</label>
                                <input id="qC" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Option D</label>
                                <input id="qD" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Correct Option</label>
                                <select id="qAnswer" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Category (optional)</label>
                                <input id="qCategory" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" placeholder="e.g., General" />
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" id="addQuestionBtn" class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold py-3 px-6 rounded-lg">Add Question</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Student Management Modal -->
    <div id="studentModal" class="modal fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-lg relative max-h-[90vh] overflow-y-auto">
            <button class="absolute top-4 right-4 inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100" onclick="closeModal('studentModal')" aria-label="Close Student Management">
                <i class='bx bx-x text-xl'></i><span class="text-sm font-semibold">Close</span>
            </button>
            <div class="flex items-center justify-between mb-4 pr-24">
                <h3 class="text-xl font-bold">Student Management</h3>
            </div>
            <!-- Tabs -->
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" class="stud-tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold bg-green-600 text-white" data-tab="stud-tab-add">
                    <i class='bx bx-user-plus mr-1'></i> Add Single
                </button>
                <button type="button" class="stud-tab-btn px-3 py-2 rounded-lg border border-gray-200 text-sm font-semibold" data-tab="stud-tab-import">
                    <i class='bx bx-table mr-1'></i> Import CSV/TXT
                </button>
            </div>

            <div class="space-y-6">
                <!-- Add Single Student -->
                <div id="stud-tab-add" class="stud-tab">
                    <form id="addStudentForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                            <input id="sName" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Matric / Identifier</label>
                            <input id="sId" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phone (optional)</label>
                            <input id="sPhone" class="w-full px-4 py-3 rounded-lg border-2 border-gray-200" />
                        </div>
                        <div class="md:col-span-3">
                            <button type="button" id="addStudentBtn" class="w-full bg-gradient-to-r from-green-600 to-green-800 text-white font-bold py-3 px-6 rounded-lg">Add Student</button>
                        </div>
                    </form>
                </div>

                <!-- Import CSV/TXT -->
                <div id="stud-tab-import" class="stud-tab hidden">
                    <form id="studentForm" class="space-y-4">
                        <div class="border-2 border-dashed border-green-200 rounded-xl p-4 bg-green-50/50">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Upload CSV/TXT File</p>
                                    <p class="text-xs text-gray-500">Format: Name, Matric/ID, Phone (CSV with headers)</p>
                                </div>
                                <a href="/samples/sample_students_group<?php echo $adminGroup; ?>.csv" class="text-xs font-semibold text-green-700 hover:text-green-900 flex items-center gap-1" download>
                                    <i class='bx bx-download'></i> Download sample
                                </a>
                            </div>
                            <label class="flex items-center justify-center px-4 py-3 bg-white rounded-lg border border-green-200 hover:border-green-400 cursor-pointer shadow-sm">
                                <i class='bx bx-upload text-xl text-green-600 mr-2'></i>
                                <span class="text-sm font-semibold text-gray-700">Choose CSV/TXT File</span>
                                <input type="file" id="studentFile" accept=".csv,.txt" class="hidden">
                            </label>
                            <p id="studentFileName" class="text-xs text-gray-600 mt-2">No file chosen</p>
                        </div>
                        <button type="button" id="importStudents" class="ui-btn w-full bg-gradient-to-r from-green-600 to-green-800 text-white font-bold py-3 px-6 rounded-lg">Import Students</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="configModal" class="modal fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-lg relative max-h-[90vh] overflow-y-auto">
            <button class="absolute top-4 right-4 inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100" onclick="closeModal('configModal')" aria-label="Close Configuration">
                <i class='bx bx-x text-xl'></i><span class="text-sm font-semibold">Close</span>
            </button>
            <div class="flex items-center justify-between mb-4 pr-24">
                <h3 class="text-xl font-bold">Configuration</h3>
            </div>
            <form id="cfgForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Questions</label>
                    <input type="number" id="qcount" value="<?php echo $cfg['question_count'] ?? 40; ?>" min="1" max="100" class="ui-input w-full px-4 py-3 rounded-lg border-gray-200">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Duration (minutes)</label>
                    <input type="number" id="minutes" value="<?php echo $cfg['exam_minutes'] ?? 60; ?>" min="5" max="300" class="ui-input w-full px-4 py-3 rounded-lg border-gray-200">
                </div>
                <button type="button" id="saveCfg" class="ui-btn w-full bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-6 rounded-lg">Save Configuration</button>
            </form>
        </div>
    </div>

    <script>
        const API = 'api';
        const GROUP = <?php echo (int)$adminGroup; ?>;
        let currentFilter = '<?php echo $filter; ?>';
        let currentDate = '<?php echo $filterDate; ?>';

        // Filter chips
        document.querySelectorAll('.filter-chip[data-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                currentFilter = btn.getAttribute('data-filter');
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                pollDashboard();
            });
        });
        document.getElementById('dateFilter').addEventListener('change', (e) => {
            currentFilter = 'date';
            currentDate = e.target.value;
            pollDashboard();
        });

        // Modals
        function openModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('hidden');
            el.classList.add('flex');
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('flex');
        }
        document.getElementById('openQuestionModal').addEventListener('click', () => openModal('questionModal'));
        document.getElementById('openStudentModal').addEventListener('click', () => openModal('studentModal'));
        document.getElementById('openConfigModal').addEventListener('click', () => openModal('configModal'));

        // Question modal tabs
        function initQuestionTabs() {
            const buttons = Array.from(document.querySelectorAll('#questionModal .tab-btn'));
            const panels = new Map();
            buttons.forEach(btn => {
                const tabId = btn.getAttribute('data-tab');
                const panel = document.getElementById(tabId);
                if (panel) panels.set(tabId, panel);
                btn.addEventListener('click', () => {
                    // Toggle active button styles
                    buttons.forEach(b => b.classList.remove('bg-purple-600','text-white'));
                    btn.classList.add('bg-purple-600','text-white');
                    // Show selected panel, hide others
                    panels.forEach((el, id) => {
                        if (id === tabId) {
                            el.classList.remove('hidden');
                        } else {
                            el.classList.add('hidden');
                        }
                    });
                });
            });
            // Default to first tab
            if (buttons.length) buttons[0].click();
        }
        // Initialize tabs once DOM is ready
        document.addEventListener('DOMContentLoaded', initQuestionTabs);
        // In case script loads after DOMContentLoaded, call directly
        initQuestionTabs();

        // Student modal tabs
        function initStudentTabs() {
            const buttons = Array.from(document.querySelectorAll('#studentModal .stud-tab-btn'));
            const panels = new Map();
            buttons.forEach(btn => {
                const tabId = btn.getAttribute('data-tab');
                const panel = document.getElementById(tabId);
                if (panel) panels.set(tabId, panel);
                btn.addEventListener('click', () => {
                    buttons.forEach(b => b.classList.remove('bg-green-600','text-white'));
                    btn.classList.add('bg-green-600','text-white');
                    panels.forEach((el, id) => {
                        if (id === tabId) {
                            el.classList.remove('hidden');
                        } else {
                            el.classList.add('hidden');
                        }
                    });
                });
            });
            if (buttons.length) buttons[0].click();
        }
        document.addEventListener('DOMContentLoaded', initStudentTabs);
        initStudentTabs();

        // Close modal when clicking backdrop
        document.querySelectorAll('.modal').forEach(m => {
            m.addEventListener('click', (e) => {
                if (e.target === m) closeModal(m.id);
            });
        });
        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(m => {
                    if (!m.classList.contains('hidden')) closeModal(m.id);
                });
            }
        });

        // Config save
        document.getElementById('saveCfg').onclick = async () => {
            const questionCount = Number(document.getElementById('qcount').value);
            const examMinutes = Number(document.getElementById('minutes').value);

            if (questionCount < 1 || questionCount > 100) {
                Swal.fire({ icon: 'error', title: 'Invalid Input', text: 'Questions must be between 1 and 100' });
                return;
            }

            if (examMinutes < 5 || examMinutes > 300) {
                Swal.fire({ icon: 'error', title: 'Invalid Input', text: 'Duration must be between 5 and 300 minutes' });
                return;
            }

            Swal.fire({
                title: 'Saving...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res = await fetch(API + '/config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ questionCount, examMinutes, group: GROUP })
                });

                if (res.ok) {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: 'Quiz configuration updated successfully', timer: 2000 })
                        .then(() => location.reload());
                } else {
                    throw new Error('Failed to save');
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save configuration' });
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
                    const accVal = accuracyRow ? (accuracyRow.accuracy ?? accuracyRow.accuracy_score ?? 0) : (sessionRow.accuracy_score ?? sessionRow.accuracy ?? 0);
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

        function applyFilter(rows) {
            const byGroup = rows.filter(r => Number(r.group ?? r["group"]) === GROUP);
            if (currentFilter === 'all') return byGroup;
            if (currentFilter === 'submitted') return byGroup.filter(r => Number(r.submitted) === 1);
            if (currentFilter === 'in-progress') return byGroup.filter(r => Number(r.submitted) === 0);
            if (currentFilter === 'booted') return byGroup.filter(r => String(r.status) === 'booted');
            const todayStr = new Date().toISOString().slice(0,10);
            if (currentFilter === 'today') return byGroup.filter(r => String(r.created_at || r.session_date || '').slice(0,10) === todayStr);
            if (currentFilter === 'date') return byGroup.filter(r => String(r.created_at || r.session_date || '').slice(0,10) === currentDate);
            return byGroup;
        }

        function renderSessions(rows, accuracyMap) {
            const tbody = document.getElementById('sessionsBody');
            if (!tbody) return;
            tbody.innerHTML = rows.map(session => {
                const ids = safeJson(session.question_ids_json, []);
                const answers = safeJson(session.answers_json, {});
                const answeredCount = Array.isArray(answers) ? answers.filter(a => a !== null && a !== '').length : Object.values(answers).filter(a => a !== null && a !== '').length;
                const total = Array.isArray(ids) ? ids.length : 0;
                const progressPct = total > 0 ? Math.min(100, Math.round((answeredCount / total) * 100)) : 0;
                const accRow = accuracyMap.get(session.identifier);
                const submitted = accRow ? !!accRow.submitted : !!session.submitted;
                const accVal = accRow ? (accRow.accuracy ?? accRow.accuracy_score ?? 0) : (session.accuracy_score ?? session.accuracy ?? 0);
                const vioCount = accRow && typeof accRow.violations === 'number' ? accRow.violations : (session.violations ?? 0);

                return `
                <tr class="table-row border-b border-gray-200 hover:bg-gray-50" data-identifier="${escapeHtml(session.identifier || '')}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">${escapeHtml(session.name || '')}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">${escapeHtml(session.identifier || '')}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full js-progress-bar" style="width: ${progressPct}%"></div>
                            </div>
                            <span class="text-xs text-gray-600 js-progress-text">${progressPct}%</span>
                        </div>
                        <span class="text-xs text-gray-600">${answeredCount}/${total}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-gray-900 js-accuracy">${submitted ? Number(accVal).toFixed(1) + '%' : '-'}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="badge ${vioCount>0?'badge-danger':'badge-success'} js-violations"><i class='bx bx-error-circle'></i> ${vioCount}/3</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge ${submitted?'badge-success':'badge-info'} js-status">
                            <i class='bx ${submitted?'bx-check-circle':'bx-hourglass'}'></i>
                            ${submitted?'Submitted':'In Progress'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 js-last-saved">${escapeHtml(session.last_saved || session.created_at || '')}</td>
                </tr>`;
            }).join('');
        }

        function renderSessionsMobile(rows, accuracyMap) {
            const list = document.getElementById('sessionsListMobile');
            if (!list) return;
            list.innerHTML = rows.map(session => {
                const ids = safeJson(session.question_ids_json, []);
                const answers = safeJson(session.answers_json, {});
                const answeredCount = Array.isArray(answers) ? answers.filter(a => a !== null && a !== '').length : Object.values(answers).filter(a => a !== null && a !== '').length;
                const total = Array.isArray(ids) ? ids.length : 0;
                const progressPct = total > 0 ? Math.min(100, Math.round((answeredCount / total) * 100)) : 0;
                const accRow = accuracyMap.get(session.identifier);
                const submitted = accRow ? !!accRow.submitted : !!session.submitted;
                const accVal = accRow ? (accRow.accuracy ?? accRow.accuracy_score ?? 0) : (session.accuracy_score ?? session.accuracy ?? 0);
                const vioCount = accRow && typeof accRow.violations === 'number' ? accRow.violations : (session.violations ?? 0);
                const statusBadge = submitted ? `<span class="badge badge-success"><i class='bx bx-check-circle'></i> Submitted</span>`
                    : (String(session.status)==='booted' ? `<span class="badge badge-danger"><i class='bx bx-x-circle'></i> Booted</span>`
                    : `<span class="badge badge-info"><i class='bx bx-hourglass'></i> In Progress</span>`);

                return `
                <div class="border border-gray-200 rounded-xl p-4 mb-3" data-identifier="${escapeHtml(session.identifier || '')}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900">${escapeHtml(session.name || '')}</div>
                            <div class="text-xs text-gray-600">${escapeHtml(session.identifier || '')}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-xs">${statusBadge}</div>
                            <button class="action-menu-btn px-1 py-1 rounded hover:bg-gray-200 text-gray-600 flex-shrink-0" data-identifier="${escapeHtml(session.identifier || '')}" title="Actions">
                                <i class='bx bx-dots-vertical text-xl'></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center gap-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full js-progress-bar" style="width: ${progressPct}%"></div>
                            </div>
                            <span class="text-xs text-gray-600 js-progress-text">${progressPct}%</span>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-700">
                            <span><i class='bx bx-target-lock mr-1'></i><span class="js-accuracy">${submitted ? Number(accVal).toFixed(1) + '%' : '-'}</span></span>
                            <span class="js-violations"><i class='bx bx-error-circle mr-1'></i>${vioCount}/3</span>
                            <span class="js-last-saved"><i class='bx bx-time mr-1'></i>${escapeHtml(session.last_saved || session.created_at || '')}</span>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        function safeJson(val, fallback) {
            try { return JSON.parse(val || (Array.isArray(fallback)||typeof fallback==='object' ? JSON.stringify(fallback) : 'null')); } catch { return fallback; }
        }
        function escapeHtml(str) {
            return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
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

                const filtered = applyFilter(sessionsData);
                renderSessions(filtered, accuracyMap);
                renderSessionsMobile(filtered, accuracyMap);

                // Update stats
                document.getElementById('statTotalSessions').textContent = filtered.length;
                document.getElementById('statSubmitted').textContent = filtered.filter(r => Number(r.submitted) === 1).length;
                // Violations approximate from accuracy data
                const vioSet = new Set();
                (accuracyData.students || []).forEach(s => { if ((s.violations ?? 0) > 0) vioSet.add(s.identifier); });
                document.getElementById('statViolations').textContent = vioSet.size;
            } catch (err) {
                console.error('Dashboard polling failed', err);
            }
        }

        function refreshAccuracy() {
            pollDashboard();
        }

        // File name helpers
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
                const res = await fetch(API + '/question_import.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Questions Imported', text: data.message + `\n\nImported: ${data.imported}/${data.total}`, confirmButtonColor: '#3085d6' });
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
                const res = await fetch(API + '/question_import_csv.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Questions Imported (CSV)', text: data.message + `\n\nImported: ${data.imported}/${data.total}`, confirmButtonColor: '#3085d6' });
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
                const res = await fetch(API + '/question_delete.php', { method: 'POST', body: formData });
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
                const res = await fetch(API + '/student_import.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    let msg = `Imported: ${data.imported}/${data.total}`;
                    if (data.duplicates > 0) msg += `\nDuplicates skipped: ${data.duplicates}`;

                    Swal.fire({ icon: 'success', title: 'Students Imported', text: data.message + '\n\n' + msg, confirmButtonColor: '#10b981' });
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

        // Context menu for student rows
        document.addEventListener('contextmenu', (e) => {
            const row = e.target.closest('.table-row');
            if (!row) return;
            
            e.preventDefault();
            const identifier = row.getAttribute('data-identifier');
            if (!identifier) return;
            
            showStudentMenu(identifier, e.clientX, e.clientY);
        });

        // Mobile action menu button
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-menu-btn');
            if (!btn) return;
            
            const identifier = btn.getAttribute('data-identifier');
            if (!identifier) return;
            
            const rect = btn.getBoundingClientRect();
            showStudentMenu(identifier, rect.left, rect.bottom);
        });

        // Right-click menu for student actions
        function showStudentMenu(identifier, x, y) {
            const session = Array.from(document.querySelectorAll('.table-row')).find(r => r.getAttribute('data-identifier') === identifier);
            if (!session) return;

            const statusEl = session.querySelector('.js-status');
            const isSubmitted = statusEl?.textContent?.includes('Submitted');
            const accEl = session.querySelector('.js-accuracy');
            const accuracy = accEl?.textContent ?? '-';

            const menu = document.createElement('div');
            menu.className = 'fixed bg-white rounded-lg shadow-2xl border border-gray-200 z-[1000] py-1 min-w-48';
            menu.style.left = x + 'px';
            menu.style.top = y + 'px';
            
            menu.innerHTML = `
                <div class="px-3 py-2 border-b border-gray-100 text-xs font-bold text-gray-600">
                    ${identifier}
                </div>
                <button class="mark-submitted-btn w-full text-left px-4 py-2 hover:bg-blue-50 text-sm font-medium text-blue-700 flex items-center gap-2 ${isSubmitted ? 'opacity-50 cursor-not-allowed' : ''}">
                    <i class='bx bx-check'></i> Mark as Submitted
                </button>
                <button class="view-score-btn w-full text-left px-4 py-2 hover:bg-green-50 text-sm font-medium text-green-700 flex items-center gap-2">
                    <i class='bx bx-show'></i> View Score (${accuracy})
                </button>
                <button class="mark-in-progress-btn w-full text-left px-4 py-2 hover:bg-yellow-50 text-sm font-medium text-yellow-700 flex items-center gap-2 ${!isSubmitted ? 'opacity-50 cursor-not-allowed' : ''}">
                    <i class='bx bx-redo'></i> Undo (Mark In Progress)
                </button>
            `;

            document.body.appendChild(menu);

            // Mark as Submitted
            menu.querySelector('.mark-submitted-btn').onclick = (e) => {
                e.stopPropagation();
                markStudentSubmitted(identifier);
                menu.remove();
            };

            // View Score
            menu.querySelector('.view-score-btn').onclick = (e) => {
                e.stopPropagation();
                viewStudentScore(identifier, accuracy);
                menu.remove();
            };

            // Undo (Mark in Progress)
            menu.querySelector('.mark-in-progress-btn').onclick = (e) => {
                e.stopPropagation();
                undoStudentSubmitted(identifier);
                menu.remove();
            };

            // Click elsewhere to close menu
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target)) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 0);
        }

        // Mark student as submitted
        async function markStudentSubmitted(identifier) {
            const { value: reason } = await Swal.fire({
                title: 'Mark as Submitted?',
                input: 'textarea',
                inputLabel: 'Reason for marking as submitted',
                inputPlaceholder: 'e.g., Technical issue yesterday, allowing new quiz attempt...',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'Mark Submitted'
            });

            if (!reason) return;

            try {
                const res = await fetch(API + '/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        action_type: 'mark_submitted',
                        reason: reason,
                        admin_name: 'Admin'
                    })
                });

                const data = await res.json();
                if (data.ok || data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Marked as Submitted',
                        text: `${identifier} can now start a new quiz`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(pollDashboard, 500);
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (err) {
                Swal.fire({icon: 'error', title: 'Error', text: 'Failed: ' + err.message});
            }
        }

        // View student score with detailed results
        async function viewStudentScore(identifier, accuracy) {
            const row = document.querySelector(`tr[data-identifier="${identifier}"]`);
            const name = row?.querySelector('.font-medium')?.textContent || identifier;
            
            // Show loading
            Swal.fire({
                title: `Score: ${name}`,
                html: '<div class="text-center"><i class="bx bx-loader-alt bx-spin text-4xl text-blue-600"></i><p class="mt-2">Loading detailed results...</p></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            try {
                // Fetch full session data
                const sessRes = await fetch(API + '/sessions.php?identifier=' + encodeURIComponent(identifier));
                const sessData = await sessRes.json();
                
                if (!Array.isArray(sessData) || sessData.length === 0) {
                    throw new Error('Session not found');
                }

                const session = sessData[0];
                const answers = JSON.parse(session.answers_json || '{}');
                const questionIds = JSON.parse(session.question_ids_json || '[]');
                const totalQuestions = questionIds.length;
                
                // Calculate scores from answers
                let correctCount = 0;
                const answeredCount = Object.keys(answers).filter(k => answers[k] !== null && answers[k] !== '').length;
                
                // Fetch questions to compare answers
                if (totalQuestions > 0 && answeredCount > 0) {
                    const placeholders = questionIds.map(() => '?').join(',');
                    const qRes = await fetch(API + '/accuracy.php');
                    const qData = await qRes.json();
                    
                    // Try to get correct count from accuracy data
                    const studentData = qData.students?.find(s => s.identifier === identifier);
                    if (studentData) {
                        correctCount = studentData.correct_answers || 0;
                    }
                }
                
                const violCount = session.violations || 0;
                const wrongCount = answeredCount - correctCount;
                const skippedCount = totalQuestions - answeredCount;

                // Get question details
                let detailsHtml = `
                    <div class="text-left space-y-4 max-h-[60vh] overflow-y-auto">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-xs text-gray-600">Accuracy</div>
                                <div class="text-3xl font-bold text-blue-600">${accuracy}</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="text-xs text-gray-600">Correct</div>
                                <div class="text-3xl font-bold text-green-600">${correctCount}</div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg">
                                <div class="text-xs text-gray-600">Wrong</div>
                                <div class="text-3xl font-bold text-red-600">${wrongCount}</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <div class="text-xs text-gray-600">Violations</div>
                                <div class="text-3xl font-bold text-purple-600">${violCount}/3</div>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <h4 class="font-bold text-gray-900 mb-3">Questions Breakdown</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Total Questions:</span>
                                    <span class="font-bold text-gray-900">${totalQuestions}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Answered:</span>
                                    <span class="font-bold text-blue-600">${answeredCount}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Unanswered:</span>
                                    <span class="font-bold text-yellow-600">${skippedCount}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <h4 class="font-bold text-gray-900 mb-3">Performance</h4>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-3 rounded-full" style="width: ${accuracy}"></div>
                            </div>
                            <p class="text-xs text-gray-600 mt-2">Score: ${correctCount}/${totalQuestions} correct (${accuracy} accuracy)</p>
                        </div>

                        ${totalQuestions > 0 ? `<div class="border-t pt-4">
                            <h4 class="font-bold text-gray-900 mb-2">Status Summary</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-green-100 text-green-800 p-2 rounded text-xs text-center">
                                    <div class="font-bold">${correctCount}</div>
                                    <div>Correct</div>
                                </div>
                                <div class="bg-red-100 text-red-800 p-2 rounded text-xs text-center">
                                    <div class="font-bold">${wrongCount}</div>
                                    <div>Incorrect</div>
                                </div>
                                <div class="bg-yellow-100 text-yellow-800 p-2 rounded text-xs text-center">
                                    <div class="font-bold">${skippedCount}</div>
                                    <div>Skipped</div>
                                </div>
                                <div class="bg-purple-100 text-purple-800 p-2 rounded text-xs text-center">
                                    <div class="font-bold">${violCount}</div>
                                    <div>Violations</div>
                                </div>
                            </div>
                        </div>` : ''}
                    </div>
                `;

                await Swal.fire({
                    title: `📊 Score: ${name}`,
                    html: detailsHtml,
                    icon: 'success',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'Close',
                    width: '600px'
                });
            } catch (err) {
                console.error('Score fetch error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Results',
                    text: 'Could not load detailed results: ' + err.message
                });
            }
        }

        // Undo submitted status
        async function undoStudentSubmitted(identifier) {
            const { isConfirmed } = await Swal.fire({
                title: 'Undo Submission?',
                text: 'Mark this student back as "In Progress"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, Undo'
            });

            if (!isConfirmed) return;

            try {
                const res = await fetch(API + '/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        action_type: 'undo_submitted',
                        reason: 'Admin reversed submission status',
                        admin_name: 'Admin'
                    })
                });

                const data = await res.json();
                if (data.ok || data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Undone',
                        text: `${identifier} marked as In Progress`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(pollDashboard, 500);
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (err) {
                Swal.fire({icon: 'error', title: 'Error', text: 'Failed: ' + err.message});
            }
        }

        setInterval(pollDashboard, 5000);
        pollDashboard();
    </script>
</body>
</html>
