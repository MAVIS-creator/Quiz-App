<?php
session_start();
require __DIR__ . '/db.php';
$pdo = db();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Get violations sorted by student name
$violCounts = $pdo->query('
    SELECT v.identifier, COUNT(*) as count, s.name 
    FROM violations v
    LEFT JOIN sessions s ON v.identifier = s.identifier
    GROUP BY v.identifier
    ORDER BY s.name ASC, v.identifier ASC
')->fetchAll();

// Get specific student violations if requested
$studentFilter = $_GET['student'] ?? null;
$detailedViolations = [];
if ($studentFilter) {
    $stmt = $pdo->prepare('SELECT * FROM violations WHERE identifier = ? ORDER BY created_at DESC');
    $stmt->execute([$studentFilter]);
    $detailedViolations = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proctor Dashboard</title>
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
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class='bx bx-video text-4xl mr-3'></i>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold">Proctor Dashboard</h1>
                        <p class="text-white/80 text-sm">Real-time Monitoring & Violations</p>
                    </div>
                </div>
                <a href="admin.php" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition font-semibold flex items-center">
                    <i class='bx bx-arrow-back text-xl mr-2'></i>
                    Back to Admin
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Violations Summary -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-error text-2xl mr-2 text-red-600'></i>
                Violations by Student (Sorted by Name)
            </h2>
            
            <?php if (empty($violCounts)): ?>
                <div class="text-center py-8">
                    <i class='bx bx-check-circle text-6xl text-green-500 mb-4'></i>
                    <p class="text-gray-600">No violations recorded yet. All students are following the rules!</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Student Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Matric/ID</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Total Violations</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($violCounts as $v): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($v['name'] ?? 'Unknown'); ?></td>
                                <td class="py-3 px-4 font-mono text-xs"><?php echo htmlspecialchars($v['identifier']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="text-2xl font-bold <?php echo $v['count'] >= 3 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                        <?php echo intval($v['count']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($v['count'] >= 3): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                            <i class='bx bx-error-circle'></i> Critical
                                        </span>
                                    <?php elseif ($v['count'] >= 2): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                            <i class='bx bx-error'></i> Warning
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            <i class='bx bx-info-circle'></i> Minor
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="?student=<?php echo urlencode($v['identifier']); ?>" class="text-purple-600 hover:text-purple-800 font-semibold text-xs flex items-center">
                                        <i class='bx bx-detail text-lg mr-1'></i>
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($studentFilter && !empty($detailedViolations)): ?>
        <!-- Detailed Violations -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class='bx bx-list-ul text-2xl mr-2 text-purple-600'></i>
                    Detailed Violations for: <?php echo htmlspecialchars($studentFilter); ?>
                </h2>
                <a href="proctor.php" class="text-sm text-gray-600 hover:text-gray-800">
                    <i class='bx bx-x'></i> Clear Filter
                </a>
            </div>
            
            <div class="space-y-3">
                <?php foreach($detailedViolations as $violation): ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition <?php echo $violation['severity'] >= 2 ? 'border-red-200 bg-red-50' : 'border-yellow-200 bg-yellow-50'; ?>">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class='bx bx-error-circle text-xl mr-2 <?php echo $violation['severity'] >= 2 ? 'text-red-600' : 'text-yellow-600'; ?>'></i>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($violation['type']); ?></span>
                            </div>
                            <p class="text-sm text-gray-700 mb-2"><?php echo htmlspecialchars($violation['message'] ?? 'No message'); ?></p>
                            <div class="flex items-center text-xs text-gray-500">
                                <i class='bx bx-time mr-1'></i>
                                <?php echo htmlspecialchars($violation['created_at']); ?>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $violation['severity'] >= 2 ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800'; ?>">
                            Severity: <?php echo $violation['severity']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Live Snapshot Viewer -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-camera text-2xl mr-2 text-purple-600'></i>
                Live Camera Snapshot
            </h2>
            
            <form id="snapForm" class="mb-4">
                <div class="flex gap-4">
                    <input 
                        type="text" 
                        id="snapId" 
                        placeholder="Enter Student Matric/ID"
                        class="flex-1 px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none"
                    >
                    <button 
                        type="button" 
                        id="loadSnap"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold rounded-lg hover:from-purple-700 hover:to-purple-900 transition flex items-center"
                    >
                        <i class='bx bx-camera text-xl mr-2'></i>
                        Load Snapshot
                    </button>
                </div>
                <div class="mt-2">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="checkbox" id="autoRefresh" class="mr-2">
                        Auto-refresh every 2 seconds
                    </label>
                </div>
            </form>
            
            <div id="snapResult" class="text-center text-gray-500 py-8">
                <i class='bx bx-image-alt text-6xl mb-4'></i>
                <p>Enter a student ID and click "Load Snapshot" to view</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">
                <span class="text-gray-600">&copy; Web Dev </span>
                <span class="text-lg font-bold gradient-text">Group 1</span>
            </p>
        </div>
    </footer>

    <script>
        const API = '/Quiz-App/api';
        let refreshInterval = null;

        document.getElementById('loadSnap').onclick = async () => {
            const id = document.getElementById('snapId').value.trim();
            if (!id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing ID',
                    text: 'Please enter a student ID'
                });
                return;
            }

            try {
                const res = await fetch(API+'/snapshot.php?identifier='+encodeURIComponent(id));
                const data = await res.json();
                
                if (data.image) {
                    document.getElementById('snapResult').innerHTML = `
                        <div class="rounded-lg overflow-hidden inline-block shadow-lg">
                            <img src="${data.image}" class="max-w-full" alt="Student Snapshot">
                            <div class="bg-gray-100 px-4 py-2 text-sm text-gray-600 flex items-center justify-center">
                                <i class='bx bx-time mr-2'></i>
                                ${data.timestamp || 'N/A'}
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('snapResult').innerHTML = `
                        <div class="text-center py-8">
                            <i class='bx bx-image-alt text-6xl text-gray-400 mb-4'></i>
                            <p class="text-gray-600">No snapshot available for this student</p>
                        </div>
                    `;
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load snapshot'
                });
            }
        };

        document.getElementById('autoRefresh').onchange = (e) => {
            if (e.target.checked) {
                refreshInterval = setInterval(() => {
                    if (document.getElementById('snapId').value.trim()) {
                        document.getElementById('loadSnap').click();
                    }
                }, 2000);
            } else {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            }
        };
    </script>
</body>
</html>
