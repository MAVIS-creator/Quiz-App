<?php
session_start();
require __DIR__ . '/db.php';
$pdo = db();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Get admin's current group
$adminGroup = intval($_SESSION['admin_group'] ?? 1);

// Get all active sessions (students who have started the quiz) - FILTERED BY GROUP
$activeSessions = $pdo->prepare('
    SELECT s.identifier, s.name, s.submitted, s.created_at, s.last_saved, s.violations,
           COUNT(v.id) as violation_count
    FROM sessions s
    LEFT JOIN violations v ON s.identifier = v.identifier
    WHERE s.`group` = ?
    GROUP BY s.identifier, s.name, s.submitted, s.created_at, s.last_saved, s.violations
    ORDER BY s.name ASC, s.identifier ASC
');
$activeSessions->execute([$adminGroup]);
$activeSessions = $activeSessions->fetchAll();

// Get violations sorted by student name - FILTERED BY GROUP AND DATE
$dateFilter = $_GET['date'] ?? date('Y-m-d'); // default to today
$violCounts = $pdo->prepare('
    SELECT v.identifier, COUNT(*) as count, s.name 
    FROM violations v
    LEFT JOIN sessions s ON v.identifier = s.identifier
    WHERE s.`group` = ? AND DATE(v.created_at) = ?
    GROUP BY v.identifier
    ORDER BY s.name ASC, v.identifier ASC
');
$violCounts->execute([$adminGroup, $dateFilter]);
$violCounts = $violCounts->fetchAll();

// Get specific student violations if requested
$studentFilter = $_GET['student'] ?? null;
$detailedViolations = [];
if ($studentFilter) {
    // Verify the student belongs to this group before showing their violations
    $studentCheck = $pdo->prepare('SELECT identifier FROM sessions WHERE identifier = ? AND `group` = ? LIMIT 1');
    $studentCheck->execute([$studentFilter, $adminGroup]);
    
    if ($studentCheck->fetch()) {
        $stmt = $pdo->prepare('SELECT * FROM violations WHERE identifier = ? ORDER BY created_at DESC');
        $stmt->execute([$studentFilter]);
        $detailedViolations = $stmt->fetchAll();
    }
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
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
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
        <!-- Active Students Section -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-user-check text-2xl mr-2 text-blue-600'></i>
                Active Students (Started Quiz)
            </h2>
            
            <?php if (empty($activeSessions)): ?>
                <div class="text-center py-8">
                    <i class='bx bx-user-x text-6xl text-gray-400 mb-4'></i>
                    <p class="text-gray-600">No students have started the quiz yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Student Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Matric/ID</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Violations</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Last Activity</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($activeSessions as $session): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($session['name'] ?? 'Unknown'); ?></td>
                                <td class="py-3 px-4 font-mono text-xs"><?php echo htmlspecialchars($session['identifier']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($session['submitted']): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            <i class='bx bx-check-circle'></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            <i class='bx bx-time'></i> In Progress
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php 
                                    $vcount = intval($session['violation_count']);
                                    if ($vcount > 0): ?>
                                        <span class="text-xl font-bold <?php echo $vcount >= 3 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                            <?php echo $vcount; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-xs text-gray-600">
                                    <i class='bx bx-time mr-1'></i>
                                    <?php echo htmlspecialchars($session['last_saved'] ?? $session['created_at'] ?? 'N/A'); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="sendMessage('<?php echo htmlspecialchars($session['identifier']); ?>', '<?php echo htmlspecialchars($session['name'] ?? 'Unknown'); ?>')" class="text-blue-600 hover:text-blue-800 font-semibold text-xs flex items-center px-2 py-1 border border-blue-300 rounded hover:bg-blue-50">
                                            <i class='bx bx-message text-lg mr-1'></i>
                                            Message
                                        </button>
                                        <button onclick="addTime('<?php echo htmlspecialchars($session['identifier']); ?>', '<?php echo htmlspecialchars($session['name'] ?? 'Unknown'); ?>')" class="text-green-600 hover:text-green-800 font-semibold text-xs flex items-center px-2 py-1 border border-green-300 rounded hover:bg-green-50">
                                            <i class='bx bx-time-five text-lg mr-1'></i>
                                            Add Time
                                        </button>
                                        <button onclick="showActionMenu('<?php echo htmlspecialchars($session['identifier']); ?>', '<?php echo htmlspecialchars($session['name'] ?? 'Unknown'); ?>')" class="text-purple-600 hover:text-purple-800 font-semibold text-xs flex items-center px-2 py-1 border border-purple-300 rounded hover:bg-purple-50">
                                            <i class='bx bx-cog text-lg mr-1'></i>
                                            More
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Violations Summary -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class='bx bx-error text-2xl mr-2 text-red-600'></i>
                    Violations by Student (Sorted by Name)
                </h2>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-gray-600">Date:</label>
                    <input 
                        type="date" 
                        id="violationDate" 
                        value="<?php echo htmlspecialchars($dateFilter); ?>"
                        onchange="window.location.href='proctor.php?date='+this.value"
                        class="px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-purple-500 focus:outline-none text-sm"
                    >
                    <button 
                        onclick="window.location.href='proctor.php?date=<?php echo date('Y-m-d'); ?>'"
                        class="px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-sm font-semibold"
                    >
                        Today
                    </button>
                </div>
            </div>
            
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
                                    <div class="flex items-center space-x-2">
                                        <a href="?student=<?php echo urlencode($v['identifier']); ?>" class="text-purple-600 hover:text-purple-800 font-semibold text-xs flex items-center px-2 py-1 border border-purple-300 rounded hover:bg-purple-50">
                                            <i class='bx bx-detail text-lg mr-1'></i>
                                            Details
                                        </a>
                                        <button onclick="showActionMenu('<?php echo htmlspecialchars($v['identifier']); ?>', '<?php echo htmlspecialchars($v['name'] ?? 'Unknown'); ?>')" class="text-red-600 hover:text-red-800 font-semibold text-xs flex items-center px-2 py-1 border border-red-300 rounded hover:bg-red-50">
                                            <i class='bx bx-shield-x text-lg mr-1'></i>
                                            Actions
                                        </button>
                                        <button onclick="sendMessage('<?php echo htmlspecialchars($v['identifier']); ?>', '<?php echo htmlspecialchars($v['name'] ?? 'Unknown'); ?>')" class="text-blue-600 hover:text-blue-800 font-semibold text-xs flex items-center px-2 py-1 border border-blue-300 rounded hover:bg-blue-50">
                                            <i class='bx bx-message text-lg mr-1'></i>
                                            Message
                                        </button>
                                    </div>
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
                Live Video & Snapshot Monitoring
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
                        id="connectLive"
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-800 text-white font-bold rounded-lg hover:from-green-700 hover:to-green-900 transition flex items-center"
                    >
                        <i class='bx bx-video text-xl mr-2'></i>
                        View Live
                    </button>
                    <button 
                        type="button" 
                        id="requestSnapshot"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold rounded-lg hover:from-purple-700 hover:to-purple-900 transition flex items-center"
                    >
                        <i class='bx bx-camera text-xl mr-2'></i>
                        Request Snapshot
                    </button>
                    <button 
                        type="button" 
                        id="loadSnap"
                        class="px-6 py-3 bg-gradient-to-r from-yellow-600 to-yellow-800 text-white font-bold rounded-lg hover:from-yellow-700 hover:to-yellow-900 transition flex items-center"
                    >
                        <i class='bx bx-image text-xl mr-2'></i>
                        Load Snapshots
                    </button>
                    <button 
                        type="button" 
                        id="requestAudio"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center"
                    >
                        <i class='bx bx-microphone text-xl mr-2'></i>
                        Request Audio
                    </button>
                </div>
            </form>
            
            <!-- Live Video Container -->
            <div id="liveVideoContainer" class="hidden mb-4">
                <div class="bg-gray-900 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-white font-bold flex items-center">
                            <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse mr-2"></span>
                            Live Feed: <span id="liveStudentId" class="ml-2 text-green-400"></span>
                        </h3>
                        <button id="disconnectLive" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            <i class='bx bx-x'></i> Disconnect
                        </button>
                    </div>
                    <video id="liveVideo" autoplay playsinline class="w-full max-w-3xl mx-auto rounded border-2 border-green-500"></video>
                    <p class="text-gray-400 text-xs mt-2 text-center">Real-time peer-to-peer video stream via PeerJS</p>
                </div>
            </div>
            
            <div id="snapResult" class="text-center text-gray-500 py-8">
                <i class='bx bx-image-alt text-6xl mb-4'></i>
                <p>Enter a student ID and click "View Live" for real-time monitoring</p>
                <p class="text-sm mt-2">Or click "Load Snapshots" to view saved evidence</p>
            </div>

            <!-- Violation Snapshots Gallery -->
            <div id="violationGallery" class="mt-6 hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center"><i class='bx bx-photo-album text-xl mr-2 text-red-600'></i>Violation Snapshots</h3>
                <div id="violationItems" class="grid grid-cols-2 md:grid-cols-3 gap-3"></div>
            </div>
        </div>

        <!-- Audio Recordings Viewer -->
        <div class="bg-white rounded-2xl p-6 shadow-lg mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class='bx bx-headphone text-2xl mr-2 text-blue-600'></i>
                Audio Recordings
            </h2>
            <form id="audioForm" class="mb-4">
                <div class="flex gap-4">
                    <input 
                        type="text" 
                        id="audioId" 
                        placeholder="Enter Student Matric/ID"
                        class="flex-1 px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none"
                    >
                    <button 
                        type="button" 
                        id="loadAudio"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center"
                    >
                        <i class='bx bx-download text-xl mr-2'></i>
                        Load Recordings
                    </button>
                </div>
            </form>
            <div id="audioResult" class="text-center text-gray-500 py-8">
                <i class='bx bx-music text-6xl mb-4'></i>
                <p>Enter a student ID and click "Load Recordings" to view</p>
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
        
        // PeerJS for live video streaming
        let peer = null;
        let currentCall = null;
        let connectedStudentId = null;
        
        // Initialize PeerJS
        function initPeerJS() {
            try {
                // Use default PeerJS cloud (most reliable)
                peer = new Peer('proctor_' + Date.now());
                
                peer.on('open', (id) => {
                    console.log('Proctor PeerJS connected:', id);
                });
                
                peer.on('error', (err) => {
                    console.error('PeerJS error:', err);
                });
            } catch (e) {
                console.error('Failed to initialize PeerJS:', e);
            }
        }
        
        // Initialize on page load
        initPeerJS();
        
        // Connect to student's live video
        document.getElementById('connectLive').onclick = async () => {
            const id = document.getElementById('snapId').value.trim();
            if (!id) {
                Swal.fire({ icon: 'warning', title: 'Missing ID', text: 'Please enter a student ID' });
                return;
            }
            
            // Check if student is online
            try {
                const checkRes = await fetch(`${API}/sessions.php?identifier=${encodeURIComponent(id)}`);
                const checkData = await checkRes.json();
                
                if (!checkData || checkData.submitted || checkData.cancelled) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'User Not Online', 
                        text: 'This student is not currently taking the quiz.' 
                    });
                    return;
                }
            } catch (err) {
                console.error('Status check failed:', err);
            }
            
            if (!peer) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'PeerJS not initialized' });
                return;
            }
            
            // Disconnect existing call if any
            if (currentCall) {
                currentCall.close();
            }
            
            // Call the student
            const studentPeerId = 'student_' + id;
            console.log('Calling student:', studentPeerId);
            
            try {
                currentCall = peer.call(studentPeerId, new MediaStream()); // Empty stream
                
                currentCall.on('stream', (remoteStream) => {
                    console.log('Received stream from student');
                    const liveVideo = document.getElementById('liveVideo');
                    liveVideo.srcObject = remoteStream;
                    document.getElementById('liveVideoContainer').classList.remove('hidden');
                    document.getElementById('liveStudentId').textContent = id;
                    connectedStudentId = id;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Connected',
                        text: 'Now viewing student live camera',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
                
                currentCall.on('close', () => {
                    disconnectLiveVideo();
                });
                
                currentCall.on('error', (err) => {
                    console.error('Call error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Failed',
                        text: 'Could not connect to student. They may not be online or PeerJS may be blocked.'
                    });
                });
            } catch (err) {
                console.error('Failed to call student:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to connect: ' + err.message });
            }
        };
        
        // Disconnect live video
        document.getElementById('disconnectLive').onclick = disconnectLiveVideo;
        
        function disconnectLiveVideo() {
            if (currentCall) {
                currentCall.close();
                currentCall = null;
            }
            const liveVideo = document.getElementById('liveVideo');
            if (liveVideo.srcObject) {
                liveVideo.srcObject.getTracks().forEach(track => track.stop());
                liveVideo.srcObject = null;
            }
            document.getElementById('liveVideoContainer').classList.add('hidden');
            connectedStudentId = null;
        }
        
        // Request snapshot from student
        document.getElementById('requestSnapshot').onclick = async () => {
            const id = document.getElementById('snapId').value.trim();
            if (!id) {
                Swal.fire({ icon: 'warning', title: 'Missing ID', text: 'Please enter a student ID' });
                return;
            }
            
            try {
                // Check if student is online
                const checkRes = await fetch(`${API}/sessions.php?identifier=${encodeURIComponent(id)}`);
                const checkData = await checkRes.json();
                
                if (!checkData || checkData.submitted || checkData.cancelled) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'User Not Online', 
                        text: 'This student is not currently taking the quiz.' 
                    });
                    return;
                }
                
                // Send snapshot request via messages
                const response = await fetch(API + '/messages.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ sender: 'admin', receiver: id, text: '[REQUEST_SNAPSHOT] Please capture a snapshot.' })
                });
                const data = await response.json();
                
                if (data.ok) {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Request Sent', 
                        text: 'Snapshot will be captured shortly. Refresh to view.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to request snapshot.' });
            }
        };

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
                // Load latest preview snapshot
                const res = await fetch(API+'/snapshot.php?identifier='+encodeURIComponent(id)+'&type=preview');
                const data = await res.json();
                
                if (data.filename && data.url) {
                    document.getElementById('snapResult').innerHTML = `
                        <div class="rounded-lg overflow-hidden inline-block shadow-lg">
                            <img src="${data.url}" class="max-w-2xl max-h-96 object-contain" alt="Student Snapshot"
                                 onerror="this.onerror=null; document.getElementById('snapResult').innerHTML = '<div class=\'text-center py-8\'><i class=\'bx bx-image-alt text-6xl text-gray-400 mb-4\'></i><p class=\'text-gray-600\'>Snapshot failed to load. Please verify uploads path/filename.</p></div>';">
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

                // Load recent violation snapshots (limit 6)
                const violRes = await fetch(API + '/snapshot.php?identifier='+encodeURIComponent(id)+'&type=violation&limit=6');
                const violData = await violRes.json();
                const items = (violData.items || []);
                const gallery = document.getElementById('violationGallery');
                const grid = document.getElementById('violationItems');
                if (items.length > 0) {
                    gallery.classList.remove('hidden');
                    grid.innerHTML = items.map(it => `
                        <div class="border rounded-lg overflow-hidden bg-white">
                            <img src="${it.url}" class="w-full h-40 object-cover" alt="Violation Snapshot">
                            <div class="px-2 py-1 text-xs text-gray-600 flex items-center"><i class='bx bx-time mr-1'></i>${it.timestamp || ''}</div>
                        </div>
                    `).join('');
                } else {
                    gallery.classList.add('hidden');
                    grid.innerHTML = '';
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load snapshot'
                });
            }
        };

        // Request audio from student (sends a command via messages API)
        document.getElementById('requestAudio').onclick = async () => {
            const id = document.getElementById('snapId').value.trim();
            if (!id) {
                Swal.fire({ icon: 'warning', title: 'Missing ID', text: 'Please enter a student ID' });
                return;
            }
            
            try {
                // First check if student is online (has active session)
                const checkRes = await fetch(`${API}/sessions.php?identifier=${encodeURIComponent(id)}`);
                const checkData = await checkRes.json();
                
                if (!checkData || checkData.submitted || checkData.cancelled) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'User Not Online', 
                        text: 'This student is not currently taking the quiz.' 
                    });
                    return;
                }
                
                // Send audio request
                const response = await fetch(API + '/messages.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ sender: 'admin', receiver: id, text: '[REQUEST_AUDIO] Please send an audio clip.' })
                });
                const data = await response.json();
                if (data.ok) {
                    // Show success message and automatically load audio after a few seconds
                    await Swal.fire({ 
                        icon: 'success', 
                        title: 'Request Sent', 
                        text: 'The student will upload an audio clip shortly. Auto-loading audio in 5 seconds...', 
                        timer: 5000, 
                        timerProgressBar: true,
                        showConfirmButton: false 
                    });
                    
                    // Auto-fill audio ID and trigger load
                    document.getElementById('audioId').value = id;
                    document.getElementById('loadAudio').click();
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to send audio request.' });
            }
        };

        // Load Audio Recordings
        document.getElementById('loadAudio').onclick = async () => {
            const studentId = document.getElementById('audioId').value.trim();
            if (!studentId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing ID',
                    text: 'Please enter a student ID'
                });
                return;
            }

            try {
                const res = await fetch(API + '/audio_save.php?identifier=' + encodeURIComponent(studentId));
                const data = await res.json();
                const clips = data.clips || [];
                
                if (Array.isArray(clips) && clips.length > 0) {
                    let html = `<div class="space-y-3">`;
                    clips.forEach((clip, idx) => {
                        const audioUrl = clip.url || (clip.audio_data ? `data:audio/webm;base64,${clip.audio_data}` : '');
                        const duration = clip.duration ? `(${clip.duration}s)` : '';
                        // Guess mime type
                        let mimeType = 'audio/webm';
                        if (audioUrl.endsWith('.wav')) mimeType = 'audio/wav';
                        else if (audioUrl.endsWith('.mp3') || audioUrl.startsWith('data:audio/mp3')) mimeType = 'audio/mpeg';
                        else if (audioUrl.endsWith('.webm') || audioUrl.startsWith('data:audio/webm')) mimeType = 'audio/webm';

                        html += `
                            <div class="border rounded-lg p-4 bg-blue-50 hover:shadow-md transition">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800">Recording ${idx + 1} ${duration}</span>
                                    <span class="text-xs text-gray-500">${clip.created_at || 'N/A'}</span>
                                </div>
                                <audio controls class="w-full" style="max-width: 500px;">
                                    ${audioUrl ? `<source src="${audioUrl}" type="${mimeType}">` : ''}
                                    Your browser does not support audio playback.
                                </audio>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    document.getElementById('audioResult').innerHTML = html;
                } else {
                    document.getElementById('audioResult').innerHTML = `
                        <div class="text-center py-8">
                            <i class='bx bx-music text-6xl text-gray-400 mb-4'></i>
                            <p class="text-gray-600">No audio recordings available for this student</p>
                        </div>
                    `;
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load audio recordings: ' + error.message
                });
            }
        };

        // Admin Action Menu
        async function showActionMenu(identifier, studentName) {
            const { value: action } = await Swal.fire({
                title: `Admin Actions for ${studentName}`,
                html: `
                    <div class="text-left space-y-3">
                        <p class="text-sm text-gray-600 mb-4">Select an action to apply:</p>
                    </div>
                `,
                input: 'select',
                inputOptions: {
                    'time_add': '⏰ Add Time (5 minutes)',
                    'time_penalty': '⏱️ Time Penalty (5 minutes)',
                    'point_deduction': '📉 Deduct Points (10 points)',
                    'warning': '⚠️ Send Warning',
                    'boot_out': '🚪 Boot Out (Terminate Exam)',
                    'exam_cancelled': '❌ Cancel Exam'
                },
                inputPlaceholder: 'Select action',
                showCancelButton: true,
                confirmButtonText: 'Apply Action',
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#6b7280'
            });

            if (action) {
                applyAction(identifier, studentName, action);
            }
        }

        async function applyAction(identifier, studentName, actionType) {
            let value = 0;
            let apiEndpoint = '/Quiz-App/api/admin_actions.php';
            
            // Get reason from user
            const { value: reason } = await Swal.fire({
                title: 'Enter Reason',
                input: 'textarea',
                inputLabel: `Reason for ${actionType}`,
                inputPlaceholder: 'Enter the reason for this action...',
                inputAttributes: {
                    'aria-label': 'Reason'
                },
                showCancelButton: true,
                confirmButtonColor: '#7c3aed'
            });

            if (!reason) return;

            // Get custom value for time/point adjustments
            if (actionType === 'time_add' || actionType === 'time_penalty') {
                const { value: timeInput } = await Swal.fire({
                    title: `${actionType === 'time_add' ? 'Add Time' : 'Deduct Time'}`,
                    input: 'number',
                    inputLabel: 'Enter duration in minutes',
                    inputValue: '5',
                    inputAttributes: {
                        'min': '1',
                        'max': '120'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed'
                });
                
                if (!timeInput) return;
                value = parseInt(timeInput) * 60; // Convert to seconds
                
                if (actionType === 'time_add') {
                    apiEndpoint = '/Quiz-App/api/time_control.php';
                } else {
                    // For time_penalty, negate the value
                    value = -value;
                }
            } else if (actionType === 'point_deduction') {
                const { value: pointInput } = await Swal.fire({
                    title: 'Deduct Points',
                    input: 'number',
                    inputLabel: 'Enter number of points to deduct',
                    inputValue: '10',
                    inputAttributes: {
                        'min': '1',
                        'max': '100'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed'
                });
                
                if (!pointInput) return;
                value = parseInt(pointInput);
            }

            try {
                let body;
                if (apiEndpoint.includes('time_control')) {
                    body = {
                        identifier: identifier,
                        adjustment_seconds: value,
                        reason: reason,
                        admin_name: 'Admin'
                    };
                } else {
                    body = {
                        identifier: identifier,
                        action_type: actionType,
                        value: value,
                        reason: reason,
                        admin_name: 'Admin'
                    };
                }

                const response = await fetch(apiEndpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (data.success || data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Action Applied',
                        text: `Action applied to ${studentName}`,
                        confirmButtonColor: '#7c3aed'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to apply action: ' + error.message,
                    confirmButtonColor: '#dc2626'
                });
            }
        }

        // Send Message to Student
        async function sendMessage(identifier, studentName) {
            // Check if student is online first
            try {
                const checkRes = await fetch(`${API}/sessions.php?identifier=${encodeURIComponent(identifier)}`);
                const checkData = await checkRes.json();
                
                if (!checkData || checkData.submitted || checkData.cancelled) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'User Not Online', 
                        text: 'This student is not currently taking the quiz.' 
                    });
                    return;
                }
            } catch (err) {
                console.error('Failed to check online status:', err);
            }
            
            const { value: message } = await Swal.fire({
                title: `Message to ${studentName}`,
                input: 'textarea',
                inputLabel: 'Type your message',
                inputPlaceholder: 'Enter message for the student...',
                inputAttributes: {
                    'aria-label': 'Message'
                },
                showCancelButton: true,
                confirmButtonText: 'Send Message',
                confirmButtonColor: '#3b82f6',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a message';
                    }
                }
            });

            if (message) {
                try {
                    const response = await fetch('/Quiz-App/api/messages.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            sender: 'admin',
                            receiver: identifier,
                            text: message
                        })
                    });

                    const data = await response.json();

                    if (data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Message Sent',
                            text: `Message sent to ${studentName}`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.error || 'Failed');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to send message: ' + error.message,
                        confirmButtonColor: '#dc2626'
                    });
                }
            }
        }

        // Add Time to Student
        async function addTime(identifier, studentName) {
            // Check if student is online first
            try {
                const checkRes = await fetch(`${API}/sessions.php?identifier=${encodeURIComponent(identifier)}`);
                const checkData = await checkRes.json();
                
                if (!checkData || checkData.submitted || checkData.cancelled) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'User Not Online', 
                        text: 'This student is not currently taking the quiz.' 
                    });
                    return;
                }
            } catch (err) {
                console.error('Failed to check online status:', err);
            }
            
            const { value: timeInput } = await Swal.fire({
                title: `Add Time for ${studentName}`,
                input: 'number',
                inputLabel: 'Enter duration in minutes',
                inputValue: '5',
                inputAttributes: {
                    'min': '1',
                    'max': '120'
                },
                showCancelButton: true,
                confirmButtonText: 'Add Time',
                confirmButtonColor: '#10b981'
            });

            if (!timeInput) return;

            const seconds = parseInt(timeInput) * 60;

            // Ask for reason
            const { value: reason } = await Swal.fire({
                title: 'Enter Reason',
                input: 'textarea',
                inputLabel: 'Reason for adding time',
                inputPlaceholder: 'Enter the reason for this action...',
                inputAttributes: {
                    'aria-label': 'Reason'
                },
                showCancelButton: true,
                confirmButtonColor: '#10b981'
            });

            if (!reason) return;

            try {
                const response = await fetch('/Quiz-App/api/time_control.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        adjustment_seconds: seconds,
                        reason: reason,
                        admin_name: 'Admin'
                    })
                });

                const data = await response.json();

                if (data.success || data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Time Added',
                        text: `Added ${timeInput} minute(s) to ${studentName}'s exam`,
                        confirmButtonColor: '#10b981'
                    });
                } else {
                    throw new Error(data.error || 'Failed');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add time: ' + error.message,
                    confirmButtonColor: '#dc2626'
                });
            }
        }
    </script>
</body>
</html>
