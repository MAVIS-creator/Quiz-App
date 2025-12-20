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
                    <button 
                        type="button" 
                        id="requestAudio"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-800 text-white font-bold rounded-lg hover:from-blue-700 hover:to-blue-900 transition flex items-center"
                    >
                        <i class='bx bx-microphone text-xl mr-2'></i>
                        Request Audio
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

        // Request audio from student (sends a command via messages API)
        document.getElementById('requestAudio').onclick = async () => {
            const id = document.getElementById('snapId').value.trim();
            if (!id) {
                Swal.fire({ icon: 'warning', title: 'Missing ID', text: 'Please enter a student ID' });
                return;
            }
            try {
                const response = await fetch(API + '/messages.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ sender: 'admin', receiver: id, text: '[REQUEST_AUDIO] Please send an audio clip.' })
                });
                const data = await response.json();
                if (data.ok) {
                    Swal.fire({ icon: 'success', title: 'Request Sent', text: 'The student will upload an audio clip shortly.', timer: 2000, showConfirmButton: false });
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
                        const audioUrl = clip.url;
                        const duration = clip.duration ? `(${clip.duration}s)` : '';
                        html += `
                            <div class="border rounded-lg p-4 bg-blue-50 hover:shadow-md transition">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800">Recording ${idx + 1} ${duration}</span>
                                    <span class="text-xs text-gray-500">${clip.created_at || 'N/A'}</span>
                                </div>
                                <audio controls class="w-full" style="max-width: 500px;">
                                    <source src="${audioUrl}" type="audio/wav">
                                    <source src="${audioUrl}" type="audio/webm">
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
    </script>
</body>
</html>
