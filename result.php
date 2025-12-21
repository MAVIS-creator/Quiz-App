<?php
session_start();

// Check if logged in
if (!isset($_SESSION['student_matric'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$pdo = db();

$studentMatric = $_SESSION['student_matric'];
$studentName = $_SESSION['student_name'];

// Fetch student's session data
$session = $pdo->prepare('SELECT * FROM sessions WHERE identifier = ? ORDER BY created_at DESC LIMIT 1');
$session->execute([$studentMatric]);
$sessionData = $session->fetch();

// Calculate score if session exists
$score = 0;
$totalQuestions = 0;
$answeredCount = 0;
$correctAnswers = 0;
$wrongAnswers = 0;
$answersDetail = [];

if ($sessionData && $sessionData['submitted'] == 1) {
    $answers = json_decode($sessionData['answers_json'], true) ?? [];
    $questionIds = json_decode($sessionData['question_ids_json'], true) ?? [];
    $totalQuestions = count($questionIds);
    $answeredCount = count($answers);
    
    // Get correct answers
    if (!empty($questionIds)) {
        $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, prompt, answer, category FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($questionIds);
        $questions = $stmt->fetchAll();
        
        foreach ($questions as $q) {
            $studentAnswer = $answers[$q['id']] ?? null;
            $isCorrect = ($studentAnswer === $q['answer']);
            
            if ($isCorrect) {
                $correctAnswers++;
            } else if ($studentAnswer !== null) {
                $wrongAnswers++;
            }
            
            $answersDetail[] = [
                'question' => $q['prompt'],
                'category' => $q['category'],
                'student_answer' => $studentAnswer,
                'correct_answer' => $q['answer'],
                'is_correct' => $isCorrect
            ];
        }
    }
    
    $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
}

$hasAttempt = $sessionData && $sessionData['submitted'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes progressBar {
            from { width: 0%; }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-slideIn {
            animation: slideIn 0.8s ease-out;
        }

        .animate-scaleIn {
            animation: scaleIn 0.5s ease-out;
        }

        .progress-animate {
            animation: progressBar 1.5s ease-out;
        }

        .score-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .score-circle {
                width: 150px;
                height: 150px;
            }
        }

        /* SweetAlert2 styling */
        .swal2-popup {
            border-radius: 20px;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 10px;
        }

        /* Animated gradient text for footer */
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="animate-slideIn">
                    <h1 class="text-2xl sm:text-3xl font-bold">Quiz Results</h1>
                    <p class="text-white/90 text-sm sm:text-base mt-1"><?php echo htmlspecialchars($studentName); ?></p>
                </div>
                <a href="login.php" class="px-6 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-300 text-sm sm:text-base font-semibold">
                    <span class="flex items-center">
                        <i class='bx bx-home text-xl mr-2'></i>
                        Back to Login
                    </span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($hasAttempt): ?>
            <!-- Score Overview -->
            <div class="glass-effect rounded-2xl p-8 shadow-xl mb-8 animate-fadeIn">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <!-- Score Circle -->
                    <div class="flex justify-center">
                        <div class="score-circle animate-scaleIn" style="background: conic-gradient(#667eea 0% <?php echo $score; ?>%, #e5e7eb <?php echo $score; ?>% 100%);">
                            <div class="bg-white rounded-full w-[85%] h-[85%] flex flex-col items-center justify-center">
                                <div class="text-5xl sm:text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-purple-800">
                                    <?php echo $score; ?>%
                                </div>
                                <div class="text-gray-600 font-semibold mt-2">Your Score</div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                            <div class="text-3xl sm:text-4xl font-bold text-green-600 mb-2"><?php echo $correctAnswers; ?></div>
                            <div class="text-sm text-gray-600 font-semibold">Correct Answers</div>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
                            <div class="text-3xl sm:text-4xl font-bold text-red-600 mb-2"><?php echo $wrongAnswers; ?></div>
                            <div class="text-sm text-gray-600 font-semibold">Wrong Answers</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                            <div class="text-3xl sm:text-4xl font-bold text-blue-600 mb-2"><?php echo $totalQuestions; ?></div>
                            <div class="text-sm text-gray-600 font-semibold">Total Questions</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                            <div class="text-3xl sm:text-4xl font-bold text-purple-600 mb-2"><?php echo $sessionData['violations'] ?? 0; ?></div>
                            <div class="text-sm text-gray-600 font-semibold">Violations</div>
                        </div>
                    </div>
                </div>

                <!-- Performance Bar -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-semibold text-gray-700">Performance</span>
                        <span class="text-sm font-bold text-purple-600"><?php echo $score >= 70 ? 'Excellent!' : ($score >= 50 ? 'Good' : 'Needs Improvement'); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                        <div class="progress-animate h-full rounded-full <?php echo $score >= 70 ? 'bg-gradient-to-r from-green-500 to-green-600' : ($score >= 50 ? 'bg-gradient-to-r from-yellow-500 to-yellow-600' : 'bg-gradient-to-r from-red-500 to-red-600'); ?>" 
                             style="width: <?php echo $score; ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Share Actions -->
            <div class="flex flex-wrap justify-center gap-4 mb-8 animate-fadeIn" style="animation-delay: 0.15s;">
                <button onclick="shareToWhatsApp()" class="flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <i class='bx bxl-whatsapp text-2xl mr-2'></i>
                    Share to WhatsApp
                </button>
                <button onclick="window.print()" class="flex items-center px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg">
                    <i class='bx bx-printer text-xl mr-2'></i>
                    Print Results
                </button>
            </div>

            <!-- Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-lg animate-fadeIn" style="animation-delay: 0.2s;">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Answer Distribution</h3>
                    <canvas id="answerChart"></canvas>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-lg animate-fadeIn" style="animation-delay: 0.3s;">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Performance Overview</h3>
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- Detailed Answers -->
            <div class="bg-white rounded-2xl p-6 shadow-lg animate-fadeIn" style="animation-delay: 0.4s;">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Detailed Answers</h3>
                    <button onclick="toggleDetails()" class="text-purple-600 hover:text-purple-800 font-semibold text-sm flex items-center">
                        <span id="toggleText">Show All</span>
                        <svg id="toggleIcon" class="w-5 h-5 ml-1 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="answersContainer" class="space-y-4 max-h-96 overflow-y-auto" style="display: none;">
                    <?php foreach ($answersDetail as $idx => $detail): ?>
                        <div class="border rounded-lg p-4 <?php echo $detail['is_correct'] ? 'border-green-200 bg-green-50' : ($detail['student_answer'] ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50'); ?>">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $detail['is_correct'] ? 'bg-green-200 text-green-800' : ($detail['student_answer'] ? 'bg-red-200 text-red-800' : 'bg-gray-200 text-gray-800'); ?>">
                                    <?php echo htmlspecialchars($detail['category']); ?>
                                </span>
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $detail['is_correct'] ? 'bg-green-600 text-white' : ($detail['student_answer'] ? 'bg-red-600 text-white' : 'bg-gray-600 text-white'); ?>">
                                    <?php echo $detail['is_correct'] ? '✓ Correct' : ($detail['student_answer'] ? '✗ Wrong' : 'Not Answered'); ?>
                                </span>
                            </div>
                            <p class="text-gray-800 font-semibold mb-3"><?php echo ($idx + 1) . '. ' . htmlspecialchars($detail['question']); ?></p>
                            
                            <?php if ($detail['student_answer']): ?>
                                <div class="mb-2">
                                    <span class="text-xs text-gray-600 font-semibold">Your Answer:</span>
                                    <p class="text-sm text-gray-800 mt-1"><?php echo htmlspecialchars($detail['student_answer']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$detail['is_correct']): ?>
                                <div>
                                    <span class="text-xs text-gray-600 font-semibold">Correct Answer:</span>
                                    <p class="text-sm text-green-700 font-semibold mt-1"><?php echo htmlspecialchars($detail['correct_answer']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- No Attempt Message -->
            <div class="glass-effect rounded-2xl p-12 shadow-xl text-center animate-fadeIn">
                <div class="inline-block p-6 bg-purple-100 rounded-full mb-6">
                    <svg class="w-20 h-20 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">No Quiz Attempt Yet</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    You haven't taken the quiz yet. Click the button below to start your assessment.
                </p>
                <a href="dashboard.php" class="inline-block bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                    Go to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        <?php if ($hasAttempt): ?>
        // Answer Distribution Chart
        const answerCtx = document.getElementById('answerChart').getContext('2d');
        new Chart(answerCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Wrong', 'Unanswered'],
                datasets: [{
                    data: [
                        <?php echo $correctAnswers; ?>,
                        <?php echo $wrongAnswers; ?>,
                        <?php echo $totalQuestions - $answeredCount; ?>
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // Performance Chart
        const perfCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(perfCtx, {
            type: 'bar',
            data: {
                labels: ['Your Score', 'Pass Mark'],
                datasets: [{
                    label: 'Percentage',
                    data: [<?php echo $score; ?>, 50],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(156, 163, 175, 0.5)'
                    ],
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        function toggleDetails() {
            const container = document.getElementById('answersContainer');
            const toggleText = document.getElementById('toggleText');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (container.style.display === 'none') {
                container.style.display = 'block';
                toggleText.textContent = 'Hide All';
                toggleIcon.style.transform = 'rotate(180deg)';
            } else {
                container.style.display = 'none';
                toggleText.textContent = 'Show All';
                toggleIcon.style.transform = 'rotate(0deg)';
            }
        }

        function shareToWhatsApp() {
            const score = <?php echo $score; ?>;
            const correct = <?php echo $correctAnswers; ?>;
            const total = <?php echo $totalQuestions; ?>;
            const name = '<?php echo addslashes($studentName); ?>';
            
            const message = `🎓 Quiz Results 🎓%0A%0A` +
                          `Name: ${name}%0A` +
                          `Score: ${score}%25%0A` +
                          `Correct Answers: ${correct}/${total}%0A%0A` +
                          `Performance: ${score >= 70 ? 'Excellent! ⭐' : (score >= 50 ? 'Good 👍' : 'Keep Practicing 💪')}%0A%0A` +
                          `Web Development Students 100 Level`;
            
            const whatsappUrl = `https://wa.me/?text=${message}`;
            window.open(whatsappUrl, '_blank');
        }
        <?php endif; ?>
    </script>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">
                <span class="text-gray-600">&copy; Web Dev </span>
                <span class="text-lg font-bold gradient-text">Group 1</span>
            </p>
        </div>
    </footer>
</body>
</html>
