<?php
session_start();

// Check if logged in
if (!isset($_SESSION['student_matric'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/db.php';
$pdo = db();
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
$examMin = $cfg['exam_minutes'] ?? 60;
$count = $cfg['question_count'] ?? 40;

// For MySQL, use RAND() instead of RANDOM()
$qs = $pdo->query('SELECT * FROM questions ORDER BY RAND() LIMIT ' . intval($count))->fetchAll();

$studentName = $_SESSION['student_name'];
$studentMatric = $_SESSION['student_matric'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - HTML & CSS Assessment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .question-card {
            animation: slideUp 0.5s ease-out;
        }

        .option-hover:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .option-selected {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-color: #667eea;
        }

        .timer-warning {
            animation: pulse 1s ease-in-out infinite;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .quiz-container {
                padding: 1rem;
            }
        }

        /* SweetAlert2 custom styling */
        .swal2-popup {
            border-radius: 20px;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 10px;
            padding: 12px 32px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">HTML & CSS Quiz</h1>
                    <p class="text-white/90 text-sm"><?php echo htmlspecialchars($studentName); ?> (<?php echo htmlspecialchars($studentMatric); ?>)</p>
                </div>
                <div class="flex items-center gap-4">
                    <div id="timer" class="bg-white/20 px-4 py-2 rounded-lg font-bold text-lg">
                        <span id="timeLeft"><?php echo $examMin*60; ?></span>s
                    </div>
                    <div id="progress" class="text-sm bg-white/20 px-3 py-2 rounded-lg">
                        <span id="answered">0</span>/<span id="total"><?php echo count($qs); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Quiz Container -->
    <main class="max-w-4xl mx-auto quiz-container py-8">
        <form id="quizForm">
            <input type="hidden" id="identifier" value="<?php echo htmlspecialchars($studentMatric); ?>">
            <input type="hidden" id="name" value="<?php echo htmlspecialchars($studentName); ?>">
            
            <!-- Camera Status -->
            <div id="cameraStatus" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                <span class="text-sm text-blue-800">Camera proctoring active</span>
            </div>

            <!-- Questions -->
            <div class="space-y-6">
                <?php foreach ($qs as $idx => $q): ?>
                    <div class="question-card bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500" style="animation-delay: <?php echo $idx * 0.05; ?>s;">
                        <!-- Question Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                            <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                                <?php echo htmlspecialchars($q['category']); ?>
                            </span>
                            <span class="text-gray-500 text-sm font-medium">Question <?php echo $idx + 1; ?> of <?php echo count($qs); ?></span>
                        </div>

                        <!-- Question Text -->
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 leading-relaxed">
                            <?php echo htmlspecialchars($q['prompt']); ?>
                        </h3>

                        <!-- Options -->
                        <div class="space-y-3">
                            <?php 
                            $opts = [$q['option_a'], $q['option_b'], $q['option_c'], $q['option_d']];
                            shuffle($opts);
                            foreach ($opts as $optIdx => $opt): 
                            ?>
                                <label class="option-hover flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all duration-200">
                                    <input 
                                        class="mt-1 mr-3 w-4 h-4 text-purple-600" 
                                        type="radio" 
                                        name="q<?php echo $q['id']; ?>" 
                                        value="<?php echo htmlspecialchars($opt); ?>"
                                        onchange="updateProgress()"
                                    >
                                    <span class="text-gray-700 flex-1"><?php echo htmlspecialchars($opt); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Submit Button -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 mt-8 -mx-4 sm:mx-0 sm:rounded-lg sm:border sm:shadow-lg">
                <button 
                    type="button" 
                    id="submitBtn" 
                    class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
                >
                    <span class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Submit Quiz
                    </span>
                </button>
            </div>
        </form>

        <!-- Hidden Video for Camera -->
        <video id="video" style="display:none;" autoplay></video>
    </main>

    <script>
        const API = '/Quiz-App/api';
        const id = document.getElementById('identifier').value;
        const name = document.getElementById('name').value;
        let violations = 0;
        let questionIds = Array.from(document.querySelectorAll('input[type=radio]'))
            .map(i => i.name.replace('q','')).filter((v,i,a)=>a.indexOf(v)===i);
        let timings = [];
        let questionStart = Date.now();
        let quizSubmitted = false;

        // Update progress counter
        function updateProgress() {
            const answered = questionIds.filter(qid => 
                document.querySelector('input[name="q'+qid+'"]:checked')
            ).length;
            document.getElementById('answered').textContent = answered;
            
            // Highlight selected options
            document.querySelectorAll('input[type=radio]:checked').forEach(radio => {
                radio.closest('label').classList.add('option-selected');
            });
            document.querySelectorAll('input[type=radio]:not(:checked)').forEach(radio => {
                radio.closest('label').classList.remove('option-selected');
            });
        }

        // Timer countdown
        let timeLeft = <?php echo $examMin * 60; ?>;
        const timerInterval = setInterval(() => {
            timeLeft--;
            document.getElementById('timeLeft').textContent = timeLeft;
            
            if (timeLeft <= 300 && timeLeft > 0) {
                document.getElementById('timer').classList.add('timer-warning', 'bg-red-500');
            }
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                Swal.fire({
                    icon: 'warning',
                    title: 'Time\'s Up!',
                    text: 'Your quiz will be submitted automatically.',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    submitQuiz(true);
                });
            }
        }, 1000);

        // Save session periodically
        async function saveSession(submitted=false) {
            const answers = {};
            questionIds.forEach(qid => {
                const sel = document.querySelector('input[name="q'+qid+'"]:checked');
                if (sel) answers[qid] = sel.value;
            });
            const payload = { 
                identifier: id, 
                name, 
                submitted: submitted ? 1 : 0, 
                answers, 
                questionTimings: timings, 
                questionIds, 
                violations, 
                examMinutes: <?php echo $examMin; ?> 
            };
            try {
                await fetch(API+'/sessions.php', { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify(payload)
                });
            } catch(e) {
                console.error('Save session error:', e);
            }
        }
        setInterval(() => {
            if (!quizSubmitted) saveSession();
        }, 5000);

        // Tab visibility monitoring
        let hideTimer = null;
        let wasHidden = false;
        document.addEventListener('visibilitychange', async () => {
            if (document.hidden) {
                wasHidden = true;
                hideTimer = setTimeout(async () => {
                    if (wasHidden && document.hidden && !quizSubmitted) {
                        violations = Math.min(3, violations + 1);
                        await fetch(API+'/violations.php', {
                            method: 'POST', 
                            headers: {'Content-Type': 'application/json'}, 
                            body: JSON.stringify({
                                identifier: id,
                                type: 'tab-switch',
                                severity: 1,
                                message: 'Stayed out >5s'
                            })
                        });
                        await saveSession();
                        
                        Swal.fire({
                            icon: 'warning',
                            title: 'Violation Detected!',
                            text: `Tab switch violation ${violations}/3. Please stay on this page.`,
                            confirmButtonText: 'I Understand'
                        });
                        
                        if (violations >= 3) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Quiz Terminated',
                                text: 'You have exceeded the maximum number of violations.',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                timer: 3000
                            }).then(() => {
                                submitQuiz(true);
                            });
                        }
                    }
                }, 5000);
            } else {
                wasHidden = false;
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
            }
        });

        // Camera snapshot
        (async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({video: true, audio: false});
                const video = document.getElementById('video');
                video.srcObject = stream;
                await video.play();
                
                document.getElementById('cameraStatus').innerHTML = `
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-green-800">Camera connected and monitoring</span>
                `;
                
                setInterval(async () => {
                    if (quizSubmitted) return;
                    const canvas = document.createElement('canvas');
                    canvas.width = 320;
                    canvas.height = 240;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, 320, 240);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.4);
                    await fetch(API+'/snapshot.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({identifier: id, image: dataUrl})
                    });
                }, 2000);
            } catch(e) {
                document.getElementById('cameraStatus').innerHTML = `
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm text-red-800">Camera unavailable - Please enable camera access</span>
                `;
            }
        })();

        // Track time per question
        Array.from(document.querySelectorAll('input[type=radio]')).forEach(el => {
            el.addEventListener('change', () => {
                const qid = el.name.replace('q','');
                const timeSpent = (Date.now() - questionStart) / 1000;
                timings = timings.filter(t => t.questionId != qid);
                timings.push({
                    questionId: Number(qid),
                    timeSpent,
                    timestamp: new Date().toISOString()
                });
                questionStart = Date.now();
            });
        });

        // Submit quiz
        async function submitQuiz(forced = false) {
            if (quizSubmitted) return;
            
            const answered = questionIds.filter(qid => 
                document.querySelector('input[name="q'+qid+'"]:checked')
            ).length;
            
            if (!forced && answered < questionIds.length) {
                const result = await Swal.fire({
                    icon: 'question',
                    title: 'Incomplete Quiz',
                    text: `You have answered ${answered} out of ${questionIds.length} questions. Submit anyway?`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'Review Answers'
                });
                
                if (!result.isConfirmed) return;
            }
            
            quizSubmitted = true;
            clearInterval(timerInterval);
            
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we submit your quiz',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            await saveSession(true);
            
            Swal.fire({
                icon: 'success',
                title: 'Quiz Submitted!',
                text: 'Your answers have been recorded successfully.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'result.php';
            });
        }

        document.getElementById('submitBtn').addEventListener('click', () => submitQuiz(false));

        // Prevent accidental page reload
        window.addEventListener('beforeunload', (e) => {
            if (!quizSubmitted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Prevent right-click and copy
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
    </script>
</body>
</html>
