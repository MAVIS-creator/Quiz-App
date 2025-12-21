<?php
session_start();

// Check if logged in
if (!isset($_SESSION['student_matric'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$pdo = db();

$studentName = $_SESSION['student_name'];
$studentMatric = $_SESSION['student_matric'];
$studentGroup = isset($_SESSION['student_group']) ? (int)$_SESSION['student_group'] : 1;

// Resolve student group from database if not set
if (!$studentGroup || $studentGroup <= 0) {
    $gstmt = $pdo->prepare('SELECT group_id FROM students WHERE identifier = ? LIMIT 1');
    $gstmt->execute([$studentMatric]);
    $gRow = $gstmt->fetch();
    if ($gRow && isset($gRow['group_id'])) {
        $studentGroup = (int)$gRow['group_id'];
        $_SESSION['student_group'] = $studentGroup;
    } else {
        $studentGroup = 1;
    }
}
$isTestAccount = (strpos(strtolower($studentMatric), 'test') === 0 || strtolower($studentMatric) === 'test');

// Check for existing session TODAY
$todaySessionStmt = $pdo->prepare('SELECT session_id, submitted, status, time_adjustment_seconds, created_at FROM sessions WHERE identifier = ? AND `group` = ? AND DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 1');
$todaySessionStmt->execute([$studentMatric, $studentGroup]);
$existingSession = $todaySessionStmt->fetch();

// If there's an existing session today
if ($existingSession) {
    // Check if already submitted (redirect to results instead of blocking)
    if (!$isTestAccount && $existingSession['submitted'] == 1) {
        // Redirect directly to results page
        header('Location: result.php');
        exit;
    }

    // Check if booted or cancelled by admin
    if (in_array($existingSession['status'], ['booted', 'cancelled'])) {
        echo "<!DOCTYPE html><html><head><title>Access Denied</title><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'Your exam has been " . ($existingSession['status'] === 'booted' ? 'terminated' : 'cancelled') . " by the administrator.',
                confirmButtonColor: '#dc2626',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = 'login.php';
            });
        </script></body></html>";
        exit;
    }

    // Resume existing session (not submitted, not cancelled/booted)
    $sessionId = $existingSession['session_id'];
    $timeAdjustment = $existingSession['time_adjustment_seconds'] ?? 0;

    // Calculate elapsed time since session creation
    $sessionStart = new DateTime($existingSession['created_at']);
    $now = new DateTime();
    $elapsedSeconds = $now->getTimestamp() - $sessionStart->getTimestamp();

    $isResuming = true;
} else {
    // Create new session ID for first-time attempt today
    $sessionId = $studentMatric . '_' . date('YmdHis') . '_' . uniqid();
    $timeAdjustment = 0;
    $elapsedSeconds = 0;
    $isResuming = false;
}

$_SESSION['quiz_session_id'] = $sessionId;

$cfgStmt = $pdo->prepare('SELECT exam_minutes, question_count FROM config WHERE id=?');
$cfgStmt->execute([$studentGroup]);
$cfg = $cfgStmt->fetch();
if (!$cfg) {
    $cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch() ?: ['exam_minutes' => 60, 'question_count' => 40];
}
$examMin = $cfg['exam_minutes'] ?? 60;
$count = $cfg['question_count'] ?? 40;

// Calculate remaining time (total time + adjustments - elapsed)
$totalSeconds = ($examMin * 60) + $timeAdjustment - $elapsedSeconds;
// Ensure we don't go negative
if ($totalSeconds < 0) $totalSeconds = 0;

// Get or create shuffled questions for this student
$shuffleStmt = $pdo->prepare('SELECT question_ids_order FROM student_questions WHERE identifier = ? AND group_id = ?');
$shuffleStmt->execute([$studentMatric, $studentGroup]);
$shuffledData = $shuffleStmt->fetch();

if (!$shuffledData) {
    // Generate new shuffled order (respect current configured question count)
    $qsStmt = $pdo->prepare('SELECT id FROM questions WHERE `group` = ? ORDER BY id');
    $qsStmt->execute([$studentGroup]);
    $allQs = $qsStmt->fetchAll(PDO::FETCH_COLUMN);
    $count = min($count, count($allQs));
    shuffle($allQs);
    $selectedIds = array_slice($allQs, 0, $count);

    $insertShuffle = $pdo->prepare('INSERT INTO student_questions (identifier, group_id, question_ids_order) VALUES (?, ?, ?)');
    $insertShuffle->execute([$studentMatric, $studentGroup, json_encode($selectedIds)]);
    $questionIds = $selectedIds;
} else {
    $questionIds = json_decode($shuffledData['question_ids_order'], true) ?: [];
    $storedCount = count($questionIds);

    // If the configured question count changed, regenerate the shuffled list
    if ($storedCount !== $count) {
        $qsStmt = $pdo->prepare('SELECT id FROM questions WHERE `group` = ? ORDER BY id');
        $qsStmt->execute([$studentGroup]);
        $allQs = $qsStmt->fetchAll(PDO::FETCH_COLUMN);
        $count = min($count, count($allQs));
        shuffle($allQs);
        $selectedIds = array_slice($allQs, 0, $count);

        $updateShuffle = $pdo->prepare('UPDATE student_questions SET question_ids_order = ? WHERE identifier = ? AND group_id = ?');
        $updateShuffle->execute([json_encode($selectedIds), $studentMatric, $studentGroup]);
        $questionIds = $selectedIds;
    }
}

// Fetch questions in shuffled order
$questions = [];
foreach ($questionIds as $qid) {
    $qStmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? AND `group` = ?');
    $qStmt->execute([$qid, $studentGroup]);
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
    <script defer src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.min.js"></script>
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
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

        /* Question Navigator */
        .question-navigator {
            position: relative;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 1rem;
            z-index: 10;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            display: block;
        }

        .navigator-title {
            font-weight: bold;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #374151;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-buttons-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }

        .nav-btn {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            border: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-btn.unanswered {
            background: #e5e7eb;
            color: #6b7280;
        }

        .nav-btn.answered {
            background: #10b981;
            color: white;
        }

        .nav-btn.current {
            background: #7c3aed;
            color: white;
            border: 2px solid #5b21b6;
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.5);
            animation: pulse 2s infinite;
        }

        .nav-btn:hover {
            transform: scale(1.1);
        }

        .nav-btn.answered:hover {
            background: #059669;
        }

        .nav-btn.unanswered:hover {
            background: #d1d5db;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 10px rgba(124, 58, 237, 0.5);
            }

            50% {
                box-shadow: 0 0 20px rgba(124, 58, 237, 0.8);
            }
        }

        @media (max-width: 768px) {
            .question-card {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 1024px) {
            .nav-buttons-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .question-navigator {
                position: sticky;
                top: 110px;
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
                        <div id="timer" class="text-2xl font-bold flex items-center justify-center gap-2">
                            <span id="minutes"><?php echo floor($totalSeconds / 60); ?></span>:<span id="seconds">00</span>
                            <span id="timeAdjBadge" class="hidden text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200"></span>
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
    <!-- Scrollable Message Feed -->
    <div id="messageFeed" class="fixed bottom-6 right-6 w-80 max-h-64 overflow-y-auto bg-white/90 backdrop-blur rounded-xl shadow-lg border border-gray-200 hidden">
        <div class="px-4 py-2 border-b text-sm font-semibold text-gray-700 flex items-center justify-between">
            <span><i class='bx bx-message-square-dots mr-1'></i>Admin Messages</span>
            <button onclick="document.getElementById('messageFeed').classList.add('hidden')" class="text-gray-500 hover:text-gray-700"><i class='bx bx-x'></i></button>
        </div>
        <div id="messageFeedBody" class="p-3 space-y-2 text-sm"></div>
    </div>

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

        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <div id="questionNavigator" class="question-navigator w-full lg:w-56">
                <div class="navigator-title">
                    <span>Questions</span>
                </div>
                <div class="nav-buttons-grid" id="navigatorButtons"></div>
            </div>

            <!-- Questions -->
            <div id="questionsContainer" class="flex-1 space-y-6">
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
                                        onchange="updateProgress(<?php echo $q['id']; ?>)">
                                    <span class="ml-3 text-gray-700 group-hover:text-gray-900 flex-1">
                                        <?php echo htmlspecialchars($q["option_$opt"]); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
                <span class="text-lg font-bold gradient-text">Group <?php echo (int)$studentGroup; ?></span>
            </p>
        </div>
    </footer>

    <!-- Hidden camera element -->
    <video id="camera" style="opacity: 0; position: absolute; pointer-events: none;" autoplay playsinline></video>
    <canvas id="snapshot" style="display:none;"></canvas>

    <script>
        const API = 'api';
        const identifier = '<?php echo $studentMatric; ?>';
        const studentName = '<?php echo htmlspecialchars($studentName); ?>';
        const studentGroup = <?php echo (int)$studentGroup; ?>;
        const totalQuestions = <?php echo count($questions); ?>;
        const questionIds = <?php echo json_encode(array_column($questions, 'id')); ?>;
        const sessionId = '<?php echo $_SESSION['quiz_session_id']; ?>';
        const examMinutesConfig = <?php echo (int)$examMin; ?>;

        let timeLeft = <?php echo $totalSeconds; ?>;
        let appliedAdjustment = <?php echo (int)($timeAdjustment ?? 0); ?>;
        let answeredQuestions = new Set();
        let answers = {};
        let timings = {};
        let startTime = Date.now();
        let tabSwitchCount = 0;
        let lastTabSwitch = 0;
        let cameraStream = null;
        let audioContext = null;
        let audioAnalyser = null;
        let mediaRecorder = null;
        let audioChunks = [];
        let isResuming = <?php echo $isResuming ? 'true' : 'false'; ?>;
        let faceApiModelsLoaded = false;
        let lastFaceDetectionTime = 0;
        const FACE_DETECTION_INTERVAL = 10000; // 10 seconds
        
        // PeerJS for live video streaming
        let peer = null;
        let currentCall = null;
        
        // Audio monitoring thresholds
        const NOISE_THRESHOLD_MEDIUM = 0.3; // 30% volume
        const NOISE_THRESHOLD_LOUD = 0.6; // 60% volume
        let lastNoiseLevel = 0;
        let lastAudioUpload = 0;
        const AUDIO_UPLOAD_COOLDOWN = 10000; // 10 seconds between uploads

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Show resumption notification if applicable
            if (isResuming) {
                Swal.fire({
                    icon: 'info',
                    title: 'Resuming Your Exam',
                    text: 'Your previous answers have been restored. Continue from where you left off.',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }

            if (!isResuming) {
                ensureSessionRecord();
            }
            loadSavedAnswers(); // Load previous answers first
            initCamera();
            initAudioMonitoring();
            startTimer();
            autoSave();
            checkMessages();
            monitorTabSwitches();
            pollStatusAdjustments();
        });

        // Ensure a session record exists immediately (so created_at tracks real start time)
        async function ensureSessionRecord() {
            if (isResuming) return; // do not overwrite saved answers when resuming
            try {
                await fetch(`${API}/sessions.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        session_id: sessionId,
                        name: studentName,
                        answers: answers,
                        timings: timings,
                        question_ids: questionIds,
                        examMinutes: examMinutesConfig,
                        group: studentGroup
                    })
                });
            } catch (e) {
                console.error('Initial session save failed:', e);
            }
        }

        function renderTimer() {
            const mins = Math.floor(timeLeft / 60);
            const secs = timeLeft % 60;
            document.getElementById('minutes').textContent = mins;
            document.getElementById('seconds').textContent = secs.toString().padStart(2, '0');
        }

        // Timer
        function startTimer() {
            renderTimer();
            const timerInterval = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    submitQuiz(true);
                    return;
                }

                timeLeft--;
                renderTimer();

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

        // Load saved answers from server
        async function loadSavedAnswers() {
            try {
                const response = await fetch(`${API}/sessions.php`);
                const sessions = await response.json();
                const mySession = sessions
                    .filter(s => s.identifier === identifier && Number(s.group) === studentGroup)
                    .sort((a, b) => new Date(b.last_saved || b.created_at || 0) - new Date(a.last_saved || a.created_at || 0))[0];

                if (mySession && mySession.answers_json) {
                    const savedAnswers = JSON.parse(mySession.answers_json);
                    const savedTimings = mySession.timings_json ? JSON.parse(mySession.timings_json) : {};

                    // Restore answers
                    Object.keys(savedAnswers).forEach(qid => {
                        const answerValue = savedAnswers[qid];
                        const radio = document.querySelector(`input[name="q${qid}"][value="${answerValue}"]`);
                        if (radio) {
                            radio.checked = true;
                            answers[qid] = answerValue;
                            answeredQuestions.add(parseInt(qid));
                        }
                    });

                    // Restore timings
                    Object.assign(timings, savedTimings);

                    // Update progress display
                    document.getElementById('answeredCount').textContent = answeredQuestions.size;
                }
            } catch (e) {
                console.error('Failed to load saved answers:', e);
            }
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

        // Auto-save every 30 seconds (reduced from 5s to prevent query overload)
        function autoSave() {
            setInterval(async function() {
                if (Object.keys(answers).length > 0) {
                    try {
                        await fetch(`${API}/sessions.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                identifier: identifier,
                                session_id: sessionId,
                                name: studentName,
                                answers: answers,
                                timings: timings,
                                question_ids: questionIds,
                                examMinutes: examMinutesConfig,
                                group: studentGroup
                            })
                        });
                    } catch (e) {
                        console.error('Auto-save failed:', e);
                    }
                }
            }, 30000);
        }

        // Poll for status changes and time adjustments every 30 seconds (reduced from 5s to prevent query overload)
        function pollStatusAdjustments() {
            setInterval(async function() {
                try {
                    const response = await fetch(`${API}/sessions.php`);
                    const sessions = await response.json();
                    const latest = sessions
                        .filter(s => s.identifier === identifier && Number(s.group) === studentGroup)
                        .sort((a, b) => new Date(b.last_saved || b.created_at || 0) - new Date(a.last_saved || a.created_at || 0))[0];
                    if (!latest) return;

                    // Handle mid-exam termination
                    const status = String(latest.status || '').toLowerCase();
                    if (status === 'booted' || status === 'cancelled') {
                        if (cameraStream) {
                            cameraStream.getTracks().forEach(t => t.stop());
                        }
                        Swal.fire({
                            icon: 'error',
                            title: status === 'booted' ? 'Exam Terminated' : 'Exam Cancelled',
                            text: 'You have been removed from the exam by the administrator.',
                            confirmButtonColor: '#dc2626',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = 'login.php';
                        });
                        return;
                    }

                    // If submitted elsewhere, finish here
                    if (Number(latest.submitted) === 1) {
                        if (cameraStream) {
                            cameraStream.getTracks().forEach(t => t.stop());
                        }
                        Swal.fire({
                                icon: 'success',
                                title: 'Quiz Submitted!',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => {
                                window.location.href = 'result.php';
                            });
                        return;
                    }

                    // Apply time adjustment delta
                    const newAdj = Number(latest.time_adjustment_seconds || 0);
                    if (!Number.isNaN(newAdj) && newAdj !== appliedAdjustment) {
                        const delta = newAdj - appliedAdjustment;
                        appliedAdjustment = newAdj;
                        timeLeft = Math.max(0, timeLeft + delta);
                        const added = delta > 0;
                        Swal.fire({
                            icon: added ? 'info' : 'warning',
                            title: added ? 'Time Added' : 'Time Deducted',
                            text: `${Math.abs(Math.round(delta/60))} minute(s) ${added ? 'added' : 'deducted'} by admin`,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        // Update adjustment badge
                        const badge = document.getElementById('timeAdjBadge');
                        if (badge) {
                            badge.textContent = `${added ? '+' : '-'}${Math.abs(Math.round(delta/60))}m`;
                            badge.classList.remove('hidden');
                            badge.classList.toggle('bg-indigo-100', added);
                            badge.classList.toggle('bg-red-100', !added);
                            badge.classList.toggle('text-indigo-700', added);
                            badge.classList.toggle('text-red-700', !added);
                            badge.classList.toggle('border-indigo-200', added);
                            badge.classList.toggle('border-red-200', !added);
                            // Hide after a short while
                            setTimeout(() => {
                                badge.classList.add('hidden');
                            }, 6000);
                        }
                    }
                } catch (e) {
                    console.error('Status/time polling failed:', e);
                }
            }, 5000);
        }

        // Camera monitoring
        async function initCamera() {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                const video = document.getElementById('camera');
                video.srcObject = cameraStream;
                video.muted = true; // MUTE video element so audio doesn't play

                // Initialize PeerJS for live streaming
                initPeerConnection();

                // Load face-api.js models
                await loadFaceApiModels();

                // Start smart audio monitoring (not constant recording)
                startSmartAudioMonitoring();

                // Smart snapshot with real face detection (only on violations)
                setInterval(checkForMultipleFaces, FACE_DETECTION_INTERVAL);
            } catch (e) {
                console.warn('Camera access denied:', e);
                document.getElementById('cameraStatus').innerHTML =
                    '<i class="bx bx-video-off mr-1"></i><span class="text-red-600">Camera Disabled</span>';
            }
        }
        
        // Initialize PeerJS connection for live video streaming
        function initPeerConnection() {
            try {
                // Create peer using PeerJS Cloud (production-safe, HTTPS/443)
                peer = new Peer('student_' + identifier, {
                    host: '0.peerjs.com',
                    port: 443,
                    path: '/',
                    secure: true,
                    config: {
                        iceServers: [
                            { urls: ['stun:stun.l.google.com:19302'] },
                            { urls: ['stun:stun1.l.google.com:19302'] },
                            { urls: ['stun:stun2.l.google.com:19302'] },
                            { urls: ['stun:stun3.l.google.com:19302'] },
                            { urls: ['stun:stun4.l.google.com:19302'] }
                        ]
                    }
                });
                
                peer.on('open', (id) => {
                    console.log('PeerJS connected with ID:', id);
                });
                
                // Answer incoming calls from proctor
                peer.on('call', (call) => {
                    console.log('Receiving call from proctor');
                    call.answer(cameraStream); // Answer with camera stream
                    currentCall = call;
                    
                    // Show notification to student
                    showNotification('Proctor is viewing your camera', 'info');
                });
                
                peer.on('error', (err) => {
                    console.error('PeerJS error:', err);
                });
            } catch (e) {
                console.error('PeerJS initialization failed:', e);
            }
        }

        // Load face-api.js models from CDN
        async function loadFaceApiModels() {
            try {
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model';
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(MODEL_URL)
                ]);
                
                faceApiModelsLoaded = true;
                console.log('Face-API models loaded successfully');
            } catch (e) {
                console.error('Failed to load face-api models:', e);
                faceApiModelsLoaded = false;
            }
        }

        // Background audio recording
        function startBackgroundRecording() {
            try {
                // Create audio-only stream for recording
                const audioStream = new MediaStream(cameraStream.getAudioTracks());
                mediaRecorder = new MediaRecorder(audioStream, {
                    mimeType: 'audio/webm;codecs=opus'
                });

                mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };

                mediaRecorder.onstop = async () => {
                    if (audioChunks.length > 0) {
                        const audioBlob = new Blob(audioChunks, {
                            type: 'audio/webm'
                        });
                        await uploadAudioClip(audioBlob);
                        audioChunks = [];
                    }
                };

                // Start recording (will create 10-second clips)
                mediaRecorder.start();

            } catch (e) {
                console.warn('Audio recording failed:', e);
            }
        }
        
        // Smart audio monitoring - only uploads when noise detected or requested
        function startSmartAudioMonitoring() {
            try {
                if (!cameraStream) return;
                
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                audioAnalyser = audioContext.createAnalyser();
                const source = audioContext.createMediaStreamSource(cameraStream);
                source.connect(audioAnalyser);
                audioAnalyser.fftSize = 256;
                
                const bufferLength = audioAnalyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);
                
                // Monitor audio levels
                function checkAudioLevel() {
                    audioAnalyser.getByteFrequencyData(dataArray);
                    
                    // Calculate average volume
                    let sum = 0;
                    for (let i = 0; i < bufferLength; i++) {
                        sum += dataArray[i];
                    }
                    const average = sum / bufferLength / 255; // Normalize to 0-1
                    lastNoiseLevel = average;
                    
                    // Only record if medium/loud noise detected
                    const now = Date.now();
                    if ((average > NOISE_THRESHOLD_MEDIUM) && (now - lastAudioUpload > AUDIO_UPLOAD_COOLDOWN)) {
                        console.log('Noise detected:', (average * 100).toFixed(1) + '%');
                        captureAndUploadAudio('noise_detected');
                        lastAudioUpload = now;
                    }
                    
                    requestAnimationFrame(checkAudioLevel);
                }
                
                checkAudioLevel();
            } catch (e) {
                console.error('Smart audio monitoring failed:', e);
            }
        }
        
        // Capture and upload audio clip
        async function captureAndUploadAudio(reason = 'manual') {
            try {
                if (!cameraStream) return;
                
                const audioStream = new MediaStream(cameraStream.getAudioTracks());
                const recorder = new MediaRecorder(audioStream, {
                    mimeType: 'audio/webm;codecs=opus'
                });
                
                const chunks = [];
                recorder.ondataavailable = (e) => chunks.push(e.data);
                
                recorder.onstop = async () => {
                    const blob = new Blob(chunks, { type: 'audio/webm' });
                    const reader = new FileReader();
                    reader.onloadend = async () => {
                        await fetch(`${API}/audio_save.php`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                identifier: identifier,
                                audio: reader.result,
                                duration: 5,
                                reason: reason
                            })
                        });
                        console.log('Audio uploaded:', reason);
                    };
                    reader.readAsDataURL(blob);
                };
                
                recorder.start();
                setTimeout(() => recorder.stop(), 5000); // 5 second clip
            } catch (e) {
                console.error('Audio capture failed:', e);
            }
        }

        // Upload audio clip to server
        async function uploadAudioClip(audioBlob) {
            try {
                const reader = new FileReader();
                reader.onloadend = async () => {
                    const base64Audio = reader.result;
                    const duration = mediaRecorder ? Math.round(audioChunks.length * 10 / 1000) : 0; // Rough estimate
                    await fetch(`${API}/audio_save.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            identifier: identifier,
                            audio: base64Audio,
                            duration: duration
                        })
                    });
                };
                reader.readAsDataURL(audioBlob);
            } catch (e) {
                console.error('Audio upload failed:', e);
            }
        }

        async function checkForMultipleFaces() {
            // Throttle face detection to avoid performance issues
            const now = Date.now();
            if (now - lastFaceDetectionTime < FACE_DETECTION_INTERVAL) {
                return;
            }
            lastFaceDetectionTime = now;

            const video = document.getElementById('camera');
            const canvas = document.getElementById('snapshot');
            const ctx = canvas.getContext('2d');

            // Always capture canvas for potential upload
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);

            let faceCount = 1; // Default assume 1 face
            let shouldCapture = false;

            // Use face-api.js if models are loaded
            if (faceApiModelsLoaded && typeof faceapi !== 'undefined') {
                try {
                    const detections = await faceapi.detectAllFaces(
                        video,
                        new faceapi.TinyFaceDetectorOptions({
                            inputSize: 224,
                            scoreThreshold: 0.5
                        })
                    );

                    faceCount = detections.length;

                    // Capture snapshot ONLY if:
                    // 1. No face detected (student may have left)
                    // 2. Multiple faces detected (potential cheating)
                    // NO MORE random preview captures - only violations!
                    if (faceCount === 0) {
                        console.warn('No face detected - capturing evidence');
                        shouldCapture = true;
                        logViolation('no_face', 'No face detected in camera view');
                    } else if (faceCount > 1) {
                        console.warn(`Multiple faces detected (${faceCount}) - capturing evidence`);
                        shouldCapture = true;
                        logViolation('multiple_faces', `${faceCount} faces detected in camera view`);
                    }
                    // If exactly 1 face: DO NOTHING (live video stream available via PeerJS)
                } catch (e) {
                    console.error('Face detection failed:', e);
                    // No fallback capture - rely on PeerJS live stream
                }
            } else {
                // Fallback mode without face-api: Don't capture constantly
                // Only capture if random check (very low frequency)
                if (Math.random() < 0.05) { // 5% chance = ~every 60 seconds
                    shouldCapture = true;
                }
            }

            // Send snapshot to server
            if (shouldCapture) {
                const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                const snapshotType = (faceCount === 0 || faceCount > 1) ? 'violation' : 'preview';

                try {
                    await fetch(`${API}/snapshot.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            identifier: identifier,
                            image: dataUrl,
                            type: snapshotType,
                            faceCount: faceCount
                        })
                    });
                } catch (e) {
                    console.error('Snapshot upload failed:', e);
                }
            }
        }

        // Capture violation snapshot on demand
        async function sendViolationSnapshot() {
            try {
                const canvas = document.getElementById('snapshot');
                const video = document.getElementById('camera');
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);
                const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                await fetch(`${API}/snapshot.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        image: dataUrl,
                        type: 'violation'
                    })
                });
            } catch (e) {
                console.error('Violation snapshot failed:', e);
            }
        }

        // Audio monitoring
        function initAudioMonitoring() {
            if (!cameraStream) return;

            try {
                audioContext = new(window.AudioContext || window.webkitAudioContext)();
                const source = audioContext.createMediaStreamSource(cameraStream);
                audioAnalyser = audioContext.createAnalyser();
                audioAnalyser.fftSize = 256;
                source.connect(audioAnalyser);

                const dataArray = new Uint8Array(audioAnalyser.frequencyBinCount);
                let isRecordingLoudSound = false;

                setInterval(function() {
                    audioAnalyser.getByteFrequencyData(dataArray);
                    const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
                    const vol = Math.floor(average);
                    let severity = 0;
                    let levelLabel = '';
                    // Audio levels: medium (>90), high (>140)
                    if (vol > 140) {
                        severity = 3;
                        levelLabel = 'high';
                    } else if (vol > 90) {
                        severity = 2;
                        levelLabel = 'medium';
                    }

                    if (severity > 0) {
                        logAudioDetection(vol, severity, levelLabel);
                        // Capture violation snapshot alongside audio violation
                        sendViolationSnapshot();

                        // Start recording 5-10 second clip when loud sound detected
                        if (!isRecordingLoudSound && mediaRecorder && mediaRecorder.state === 'recording') {
                            isRecordingLoudSound = true;

                            // Stop current recording after 5-10 seconds
                            setTimeout(() => {
                                if (mediaRecorder && mediaRecorder.state === 'recording') {
                                    mediaRecorder.stop();
                                    // Restart recording for next clip
                                    setTimeout(() => {
                                        if (mediaRecorder) {
                                            audioChunks = [];
                                            mediaRecorder.start();
                                            isRecordingLoudSound = false;
                                        }
                                    }, 100);
                                }
                            }, 7000); // 7 second clip
                        }
                    }
                }, 30000); // Check audio every 30 seconds
            } catch (e) {
                console.warn('Audio monitoring failed:', e);
            }
        }

        async function logAudioDetection(volume, severity = 2, levelLabel = 'medium') {
            try {
                await fetch(`${API}/violations.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        type: 'loud_audio',
                        severity: severity,
                        message: `Detected ${levelLabel} audio (volume: ${volume})`
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
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        identifier: identifier,
                        type: type,
                        severity: 3,
                        message: message
                    })
                });
                // Also capture a violation snapshot
                sendViolationSnapshot();
            } catch (e) {
                console.error('Violation log failed:', e);
            }
        }

        // Check for messages from admin
        let seenMessageKeys = new Set();

        function checkMessages() {
            setInterval(async function() {
                try {
                    const response = await fetch(`${API}/messages.php?a=${identifier}&b=admin`);
                    const messages = await response.json();
                    if (Array.isArray(messages) && messages.length) {
                        const feed = document.getElementById('messageFeed');
                        const feedBody = document.getElementById('messageFeedBody');
                        let newCount = 0;
                        messages.forEach(msg => {
                            const key = (msg.id ?? '') + '|' + (msg.created_at ?? '') + '|' + (msg.text ?? '');
                            if (!seenMessageKeys.has(key) && String(msg.sender).toLowerCase() === 'admin') {
                                seenMessageKeys.add(key);
                                newCount++;
                                
                                // Handle admin commands
                                const msgText = (msg.text || '').toUpperCase();
                                
                                // If admin requested audio, record on demand
                                if (msgText.includes('REQUEST_AUDIO') || msgText.includes('[REQUEST_AUDIO]')) {
                                    console.log('Admin requested audio');
                                    captureAndUploadAudio('admin_request');
                                }
                                
                                // If admin requested snapshot, capture on demand
                                if (msgText.includes('REQUEST_SNAPSHOT') || msgText.includes('[REQUEST_SNAPSHOT]')) {
                                    console.log('Admin requested snapshot');
                                    sendViolationSnapshot();
                                }
                                
                                // Append to feed
                                const item = document.createElement('div');
                                item.className = 'bg-blue-50 border border-blue-200 rounded p-2';
                                item.innerHTML = `<div class="flex items-start"><i class='bx bx-message-rounded-detail text-blue-600 mr-2'></i><div><div class="text-xs text-gray-500">${msg.created_at || ''}</div><div class="text-gray-800">${escapeHtml(msg.text || '')}</div></div></div>`;
                                feedBody.appendChild(item);
                                // SweetAlert toast
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'info',
                                    title: 'Admin Message',
                                    text: msg.text || '',
                                    timer: 4000,
                                    showConfirmButton: false
                                });
                            }
                        });
                        if (newCount > 0) {
                            feed.classList.remove('hidden');
                        }
                    }
                } catch (e) {
                    console.error('Message check failed:', e);
                }
            }, 30000); // Check messages every 30 seconds
        }

        // Record an on-demand audio clip upon admin request
        function requestAudioClip() {
            try {
                if (!cameraStream) return;
                const audioStream = new MediaStream(cameraStream.getAudioTracks());
                const recorder = new MediaRecorder(audioStream, {
                    mimeType: 'audio/webm;codecs=opus'
                });
                const chunks = [];
                recorder.ondataavailable = (e) => {
                    if (e.data.size > 0) chunks.push(e.data);
                };
                recorder.onstop = async () => {
                    const blob = new Blob(chunks, {
                        type: 'audio/webm'
                    });
                    await uploadAudioClip(blob);
                };
                recorder.start();
                setTimeout(() => {
                    if (recorder.state === 'recording') recorder.stop();
                }, 8000);
            } catch (e) {
                console.error('On-demand audio failed:', e);
            }
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
                // Final save with submission flag
                const submitPayload = {
                    identifier: identifier,
                    session_id: sessionId,
                    name: studentName,
                    answers: answers,
                    timings: timings,
                    question_ids: questionIds,
                    submitted: true,
                    group: studentGroup
                };

                let response;
                let data;
                let retries = 3;
                let lastError;

                // Retry logic for network resilience
                while (retries > 0) {
                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 30000);

                        response = await fetch(`${API}/sessions.php`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(submitPayload),
                            // Add timeout for ngrok
                            signal: controller.signal
                        });

                        clearTimeout(timeoutId);
                        data = await response.json();
                        break; // Success, exit retry loop
                    } catch (err) {
                        lastError = err;
                        retries--;
                        if (retries > 0) {
                            await new Promise(r => setTimeout(r, 1000)); // Wait 1s before retry
                        }
                    }
                }

                if (retries === 0) {
                    throw lastError || new Error('Failed to submit after 3 attempts');
                }

                if (response.ok && (data.ok || data.success)) {
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
                    throw new Error(data.error || 'Submit failed with unknown error');
                }
            } catch (e) {
                console.error('Submit error:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to submit quiz. Please try again.\n\nDetails: ' + e.message,
                    confirmButtonColor: '#dc2626'
                });
            }
        }

        // Question Navigator Functions
        function initializeNavigator() {
            const container = document.getElementById('navigatorButtons');
            for (let i = 1; i <= totalQuestions; i++) {
                const btn = document.createElement('button');
                btn.className = 'nav-btn unanswered';
                btn.textContent = i;
                btn.onclick = () => goToQuestion(i);
                btn.id = `navbtn-${i}`;
                container.appendChild(btn);
            }
            updateNavigatorButtons();
        }

        function goToQuestion(qNum) {
            const qId = questionIds[qNum - 1];
            const card = document.querySelector(`[data-qid="${qId}"]`);
            if (card) {
                card.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                card.classList.add('highlight-pulse');
                setTimeout(() => card.classList.remove('highlight-pulse'), 1000);
            }
        }

        function updateNavigatorButtons() {
            for (let i = 1; i <= totalQuestions; i++) {
                const btn = document.getElementById(`navbtn-${i}`);
                const qId = questionIds[i - 1];

                if (answeredQuestions.has(qId)) {
                    btn.className = 'nav-btn answered';
                } else {
                    btn.className = 'nav-btn unanswered';
                }
            }
        }

        function toggleNavigator() {
            const nav = document.getElementById('questionNavigator');
            nav.classList.toggle('active');
        }

        // Update navigator when answer is selected
        const originalUpdateProgress = updateProgress;
        updateProgress = function(qId) {
            originalUpdateProgress(qId);
            updateNavigatorButtons();
        };

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeNavigator();
        });
    </script>
</body>

</html>