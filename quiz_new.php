<?php
session_start();

// Check if logged in
if (!isset($_SESSION['student_matric'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/db.php';
$pdo = db();

$studentName = $_SESSION['student_name'];
$studentMatric = $_SESSION['student_matric'];

// Check student status
$statusStmt = $pdo->prepare('SELECT status, time_adjustment_seconds FROM sessions WHERE identifier = ?');
$statusStmt->execute([$studentMatric]);
$statusData = $statusStmt->fetch();

if ($statusData && in_array($statusData['status'], ['booted', 'cancelled'])) {
    echo "<!DOCTYPE html><html><head><title>Access Denied</title><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Access Denied',
            text: 'Your exam has been " . ($statusData['status'] === 'booted' ? 'terminated' : 'cancelled') . " by the administrator.',
            confirmButtonColor: '#dc2626',
            allowOutsideClick: false
        }).then(() => {
            window.location.href = 'login.php';
        });
    </script></body></html>";
    exit;
}

$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
$examMin = $cfg['exam_minutes'] ?? 60;
$count = $cfg['question_count'] ?? 40;

// Get time adjustment
$timeAdjustment = $statusData['time_adjustment_seconds'] ?? 0;
$totalSeconds = ($examMin * 60) + $timeAdjustment;

// Get or create shuffled questions for this student
$shuffleStmt = $pdo->prepare('SELECT question_ids_order FROM student_questions WHERE identifier = ?');
$shuffleStmt->execute([$studentMatric]);
$shuffledData = $shuffleStmt->fetch();

if (!$shuffledData) {
    // Generate new shuffled order
    $allQs = $pdo->query('SELECT id FROM questions ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    shuffle($allQs);
    $selectedIds = array_slice($allQs, 0, $count);
    
    $insertShuffle = $pdo->prepare('INSERT INTO student_questions (identifier, question_ids_order) VALUES (?, ?)');
    $insertShuffle->execute([$studentMatric, json_encode($selectedIds)]);
    $questionIds = $selectedIds;
} else {
    $questionIds = json_decode($shuffledData['question_ids_order'], true);
}

// Fetch questions in shuffled order
$questions = [];
foreach ($questionIds as $qid) {
    $qStmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
    $qStmt->execute([$qid]);
    $q = $qStmt->fetch();
    if ($q) $questions[] = $q;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - HTML & CSS Assessment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #eab308, #3b82f6);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 3s ease infinite;
        }

        .quiz-container {
            animation: fadeIn 0.8s ease-in-out;
        }

        .question-card {
            animation: slideUp 0.6s ease-out;
            transition: all 0.3s ease;
        }

        .question-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .timer-warning {
            animation: pulse 1s infinite;
        }

        /* Message notification */
        .message-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideUp 0.5s ease-out;
        }

        @media (max-width: 768px) {
            .question-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-white to-blue-50 min-h-screen">
    <!-- Header with Timer -->
    <div class="gradient-bg text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class='bx bxs-graduation text-3xl mr-2'></i>
                        HTML & CSS Quiz
                    </h1>
                    <p class="text-sm opacity-90 mt-1">
                        <i class='bx bx-user mr-1'></i><?php echo htmlspecialchars($studentName); ?> 
                        <span class="ml-2"><?php echo htmlspecialchars($studentMatric); ?></span>
                    </p>
                </div>

                <div class="flex items-center space-x-6">
                    <!-- Progress -->
                    <div class="text-center">
                        <div class="text-sm opacity-90">Progress</div>
                        <div class="text-xl font-bold">
                            <span id="answeredCount">0</span> / <?php echo $count; ?>
                        </div>
                    </div>

                    <!-- Timer -->
                    <div class="text-center">
                        <div class="text-sm opacity-90">Time Remaining</div>
                        <div id="timer" class="text-2xl font-bold">
                            <span id="minutes"><?php echo floor($totalSeconds / 60); ?></span>:<span id="seconds">00</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button onclick="submitQuiz()" class="bg-white text-purple-600 font-semibold px-6 py-2 rounded-lg hover:bg-purple-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class='bx bx-check-circle text-xl mr-1'></i>
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Notification Area -->
    <div id="messageNotification" class="message-notification hidden"></div>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <span><i class='bx bx-info-circle mr-1'></i>Answer all questions before submitting</span>
                <span id="cameraStatus" class="flex items-center">
                    <i class='bx bx-video mr-1'></i>
                    <span class="text-green-600 font-medium">Monitoring Active</span>
                </span>
            </div>
        </div>

        <!-- Questions -->
        <div id="questionsContainer" class="space-y-6">
            <?php foreach ($questions as $idx => $q): ?>
            <div class="question-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500" data-qid="<?php echo $q['id']; ?>">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-semibold mr-3">
                                Q<?php echo ($idx + 1); ?>
                            </span>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                <?php echo htmlspecialchars($q['category']); ?>
                            </span>
                        </div>
                        <p class="text-lg font-medium text-gray-800 leading-relaxed">
                            <?php echo htmlspecialchars($q['prompt']); ?>
                        </p>
                    </div>
                </div>

                <div class="space-y-3 mt-4">
                    <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-purple-400 hover:bg-purple-50 transition-all duration-200 group">
                        <input 
                            type="radio" 
                            name="q<?php echo $q['id']; ?>" 
                            value="<?php echo strtoupper($opt); ?>"
                            class="mt-1 w-4 h-4 text-purple-600 focus:ring-purple-500"
                            onchange="updateProgress(<?php echo $q['id']; ?>)"
                        >
                        <span class="ml-3 text-gray-700 group-hover:text-gray-900 flex-1">
                            <?php echo htmlspecialchars($q["option_$opt"]); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 text-center">
            <button onclick="submitQuiz()" class="bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-4 px-12 rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                <i class='bx bx-check-circle text-2xl mr-2'></i>
                Submit Quiz
            </button>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">
                <span class="text-gray-600">&copy; Web Dev </span>
                <span class="text-lg font-bold gradient-text">Group 1</span>
            </p>
        </div>
    </footer>

    <!-- Hidden camera element -->
    <video id="camera" style="display:none;" autoplay></video>
    <canvas id="snapshot" style="display:none;"></canvas>

    <script>
        const API = '/Quiz-App/api';
        const identifier = '<?php echo $studentMatric; ?>';
        const studentName = '<?php echo htmlspecialchars($studentName); ?>';
        const totalQuestions = <?php echo count($questions); ?>;
        const questionIds = <?php echo json_encode(array_column($questions, 'id')); ?>;
        
        let timeLeft = <?php echo $totalSeconds; ?>;
        let answeredQuestions = new Set();
        let answers = {};
        let timings = {};
        let startTime = Date.now();
        let tabSwitchCount = 0;
        let lastTabSwitch = 0;
        let cameraStream = null;
        let audioContext = null;
        let audioAnalyser = null;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initCamera();
            initAudioMonitoring();
            startTimer();
            autoSave();
            checkMessages();
            monitorTabSwitches();
        });
        
        // Timer
        function startTimer() {
            setInterval(function() {
                if (timeLeft <= 0) {
                    submitQuiz(true);
                    return;
                }
                
                timeLeft--;
                const mins = Math.floor(timeLeft / 60);
                const secs = timeLeft % 60;
                document.getElementById('minutes').textContent = mins;
                document.getElementById('seconds').textContent = secs.toString().padStart(2, '0');
                
                // Warning when 5 minutes left
                if (timeLeft === 300) {
                    Swal.fire({
                        icon: 'warning',
                        title: '5 Minutes Remaining',
                        text: 'Please ensure all questions are answered',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    document.getElementById('timer').classList.add('timer-warning');
                }
            }, 1000);
        }
        
        // Update progress
        function updateProgress(qid) {
            answeredQuestions.add(qid);
            const selected = document.querySelector(`input[name="q${qid}"]:checked`);
            if (selected) {
                answers[qid] = selected.value;
                timings[qid] = Math.floor((Date.now() - startTime) / 1000);
            }
            document.getElementById('answeredCount').textContent = answeredQuestions.size;
        }
        
        // Auto-save every 5 seconds
        function autoSave() {
            setInterval(async function() {
                if (Object.keys(answers).length > 0) {
                    try {
                        await fetch(`${API}/sessions.php`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                identifier: identifier,
                                name: studentName,
                                answers: answers,
                                timings: timings,
                                question_ids: questionIds
                            })
                        });
                    } catch (e) {
                        console.error('Auto-save failed:', e);
                    }
                }
            }, 5000);
        }
        
        // Camera monitoring
        async function initCamera() {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({video: true, audio: true});
                document.getElementById('camera').srcObject = cameraStream;
                
                // Smart snapshot - only when multiple faces detected
                setInterval(checkForMultipleFaces, 3000);
            } catch (e) {
                console.warn('Camera access denied:', e);
                document.getElementById('cameraStatus').innerHTML = 
                    '<i class="bx bx-video-off mr-1"></i><span class="text-red-600">Camera Disabled</span>';
            }
        }
        
        async function checkForMultipleFaces() {
            // This is a placeholder - in production, you'd use face-api.js or similar
            // For now, capture snapshot at intervals
            const canvas = document.getElementById('snapshot');
            const video = document.getElementById('camera');
            const ctx = canvas.getContext('2d');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
            
            // Send to server
            try {
                await fetch(`${API}/snapshot.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        image: dataUrl
                    })
                });
            } catch (e) {
                console.error('Snapshot failed:', e);
            }
        }
        
        // Audio monitoring
        function initAudioMonitoring() {
            if (!cameraStream) return;
            
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = audioContext.createMediaStreamSource(cameraStream);
                audioAnalyser = audioContext.createAnalyser();
                audioAnalyser.fftSize = 256;
                source.connect(audioAnalyser);
                
                const dataArray = new Uint8Array(audioAnalyser.frequencyBinCount);
                
                setInterval(function() {
                    audioAnalyser.getByteFrequencyData(dataArray);
                    const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
                    
                    // Loud voice detected (threshold: 100)
                    if (average > 100) {
                        logAudioDetection(Math.floor(average));
                    }
                }, 1000);
            } catch (e) {
                console.warn('Audio monitoring failed:', e);
            }
        }
        
        async function logAudioDetection(volume) {
            try {
                await fetch(`${API}/violations.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        type: 'loud_audio',
                        severity: 2,
                        message: `Loud audio detected (volume: ${volume})`
                    })
                });
            } catch (e) {
                console.error('Audio log failed:', e);
            }
        }
        
        // Tab switch monitoring
        function monitorTabSwitches() {
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    const now = Date.now();
                    if (now - lastTabSwitch < 5000) {
                        tabSwitchCount++;
                        logViolation('tab_switch', `Tab switched (count: ${tabSwitchCount})`);
                        
                        if (tabSwitchCount >= 3) {
                            submitQuiz(true);
                        }
                    }
                    lastTabSwitch = now;
                }
            });
        }
        
        async function logViolation(type, message) {
            try {
                await fetch(`${API}/violations.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        type: type,
                        severity: 3,
                        message: message
                    })
                });
            } catch (e) {
                console.error('Violation log failed:', e);
            }
        }
        
        // Check for messages from admin
        function checkMessages() {
            setInterval(async function() {
                try {
                    const response = await fetch(`${API}/messages.php?a=${identifier}`);
                    const data = await response.json();
                    
                    if (data.unread_count > 0) {
                        // Fetch actual messages
                        const messagesResponse = await fetch(`${API}/messages.php?a=${identifier}&b=admin`);
                        const messages = await messagesResponse.json();
                        const latestMessage = messages[messages.length - 1];
                        
                        if (latestMessage && latestMessage.sender === 'admin') {
                            showMessageNotification(latestMessage.text);
                        }
                    }
                } catch (e) {
                    console.error('Message check failed:', e);
                }
            }, 5000);
        }
        
        function showMessageNotification(message) {
            const notification = document.getElementById('messageNotification');
            notification.className = 'message-notification bg-blue-500 text-white px-6 py-4 rounded-lg shadow-xl';
            notification.innerHTML = `
                <div class="flex items-start">
                    <i class='bx bx-message-dots text-2xl mr-3'></i>
                    <div>
                        <div class="font-bold mb-1">Admin Message</div>
                        <div>${message}</div>
                    </div>
                </div>
            `;
            
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 10000);
        }
        
        // Submit quiz
        async function submitQuiz(autoSubmit = false) {
            if (!autoSubmit && answeredQuestions.size < totalQuestions) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Quiz',
                    text: `You have only answered ${answeredQuestions.size} out of ${totalQuestions} questions. Submit anyway?`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'Continue Quiz',
                    confirmButtonColor: '#7c3aed'
                });
                
                if (!result.isConfirmed) return;
            }
            
            try {
                // Final save
                const response = await fetch(`${API}/sessions.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        identifier: identifier,
                        name: studentName,
                        answers: answers,
                        timings: timings,
                        question_ids: questionIds,
                        submitted: true
                    })
                });
                
                if (response.ok) {
                    if (cameraStream) {
                        cameraStream.getTracks().forEach(track => track.stop());
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Quiz Submitted!',
                        text: 'Redirecting to results...',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'result.php';
                    });
                } else {
                    throw new Error('Submit failed');
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to submit quiz. Please try again.',
                    confirmButtonColor: '#dc2626'
                });
            }
        }
    </script>
</body>
</html>
