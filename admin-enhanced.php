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
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();

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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="#" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-upload text-3xl text-blue-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Import Questions</h3>
                <p class="text-sm text-gray-600">Upload question file</p>
            </a>

            <a href="#" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-green-100 to-green-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-user-plus text-3xl text-green-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Import Students</h3>
                <p class="text-sm text-gray-600">Upload student list</p>
            </a>

            <a href="#" class="ui-card p-6 text-center cursor-pointer group">
                <div class="inline-block p-3 bg-gradient-to-br from-purple-100 to-purple-50 rounded-lg mb-3 group-hover:scale-110 transition-transform">
                    <i class='bx bx-cog text-3xl text-purple-600'></i>
                </div>
                <h3 class="font-bold text-lg mb-1">Configuration</h3>
                <p class="text-sm text-gray-600">Manage exam settings</p>
            </a>
        </div>

        <!-- Sessions Section -->
        <div class="ui-card p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class='bx bx-list-check mr-2'></i>Student Sessions
                </h2>
                <span class="text-sm bg-gray-100 px-3 py-1 rounded-full font-semibold text-gray-700">
                    <?php echo count($sessions); ?> Total
                </span>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-2">
                <a href="?filter=all" class="filter-chip <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class='bx bx-filter-alt'></i> All
                </a>
                <a href="?filter=today" class="filter-chip <?php echo $filter === 'today' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar-today'></i> Today
                </a>
                <a href="?filter=submitted" class="filter-chip <?php echo $filter === 'submitted' ? 'active' : ''; ?>">
                    <i class='bx bx-check'></i> Submitted
                </a>
                <a href="?filter=in-progress" class="filter-chip <?php echo $filter === 'in-progress' ? 'active' : ''; ?>">
                    <i class='bx bx-hourglass'></i> In Progress
                </a>
                <a href="?filter=booted" class="filter-chip <?php echo $filter === 'booted' ? 'active' : ''; ?>">
                    <i class='bx bx-x-circle'></i> Booted
                </a>
                
                <input type="date" id="dateFilter" value="<?php echo $filterDate; ?>" class="px-3 py-2 border-2 border-gray-300 rounded-lg text-sm font-medium">
            </div>

            <!-- Sessions Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Matric</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Progress</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700">Score</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700">Violations</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700">Last Saved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                        <tr class="table-row border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($session['name']); ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($session['identifier']); ?></td>
                            <td class="px-4 py-3">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: <?php echo ($session['questions_answered'] / $session['questions_total'] * 100) ?? 0; ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-600"><?php echo $session['questions_answered'] ?? 0; ?>/<?php echo $session['questions_total'] ?? 0; ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-gray-900"><?php echo $session['accuracy'] ?? '-'; ?>%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($session['violations'] > 0): ?>
                                    <span class="badge badge-danger">
                                        <i class='bx bx-error-circle'></i>
                                        <?php echo $session['violations']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class='bx bx-check'></i>
                                        0
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($session['submitted']): ?>
                                    <span class="badge badge-success">
                                        <i class='bx bx-check-circle'></i>
                                        Submitted
                                    </span>
                                <?php elseif ($session['status'] === 'booted'): ?>
                                    <span class="badge badge-danger">
                                        <i class='bx bx-x-circle'></i>
                                        Booted
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <i class='bx bx-hourglass'></i>
                                        In Progress
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo isset($session['last_saved']) ? date('H:i', strtotime($session['last_saved'])) : date('H:i', strtotime($session['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

    <script>
        document.getElementById('dateFilter').addEventListener('change', (e) => {
            window.location.href = `?filter=date&date=${e.target.value}`;
        });
    </script>
</body>
</html>
