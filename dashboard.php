<?php
session_start();

// Check if logged in
if (!isset($_SESSION['student_matric'])) {
    header('Location: login.php');
    exit;
}

$studentName = $_SESSION['student_name'];
$studentMatric = $_SESSION['student_matric'];
$studentPhone = $_SESSION['student_phone'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-slideIn {
            animation: slideIn 0.8s ease-out;
        }

        .animate-pulse-custom {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 769px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Custom SweetAlert2 styling */
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
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="animate-slideIn">
                    <h1 class="text-2xl sm:text-3xl font-bold">Student Dashboard</h1>
                    <p class="text-white/90 text-sm sm:text-base mt-1">Welcome, <?php echo htmlspecialchars($studentName); ?>!</p>
                </div>
                <button onclick="logout()" class="px-6 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-300 text-sm sm:text-base font-semibold">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Student Info Card -->
        <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8 animate-fadeIn">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
                Your Information
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-purple-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Name</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentName); ?></p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Matric Number</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentMatric); ?></p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Phone</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentPhone); ?></p>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="dashboard-grid grid gap-6">
            <!-- Start Quiz Card -->
            <div class="card-hover bg-white rounded-2xl p-8 shadow-lg transition-all duration-300 animate-fadeIn" style="animation-delay: 0.1s;">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-full mb-6 animate-pulse-custom">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Take Quiz</h3>
                <p class="text-gray-600 mb-6">Start your HTML & CSS quiz assessment. Make sure you have a stable internet connection.</p>
                <ul class="text-sm text-gray-600 space-y-2 mb-6">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        40 Questions
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        60 Minutes Duration
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Proctored Exam
                    </li>
                </ul>
                <button onclick="startQuiz()" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                    Start Quiz Now
                </button>
            </div>

            <!-- View Results Card -->
            <div class="card-hover bg-white rounded-2xl p-8 shadow-lg transition-all duration-300 animate-fadeIn" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">View Results</h3>
                <p class="text-gray-600 mb-6">Check your quiz performance and detailed results after submission.</p>
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-1">Status</p>
                    <p class="font-semibold text-gray-800">No quiz attempt yet</p>
                </div>
                <button onclick="viewResults()" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                    View My Results
                </button>
            </div>

            <!-- Instructions Card -->
            <div class="card-hover bg-white rounded-2xl p-8 shadow-lg transition-all duration-300 animate-fadeIn sm:col-span-2" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Quiz Instructions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-2 text-purple-600 text-sm">1</span>
                            Before Starting
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li>• Ensure stable internet connection</li>
                            <li>• Allow camera access for proctoring</li>
                            <li>• Use a laptop or desktop for best experience</li>
                            <li>• Find a quiet, well-lit environment</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <span class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center mr-2 text-purple-600 text-sm">2</span>
                            During Quiz
                        </h4>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li>• Do not switch tabs or windows</li>
                            <li>• Answer all questions before time runs out</li>
                            <li>• Your progress is auto-saved</li>
                            <li>• 3 violations will terminate the quiz</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function startQuiz() {
            Swal.fire({
                title: 'Ready to Start?',
                html: `
                    <div class="text-left space-y-3">
                        <p class="text-gray-700">Please confirm that you:</p>
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li>✓ Have a stable internet connection</li>
                            <li>✓ Are in a quiet environment</li>
                            <li>✓ Will allow camera access</li>
                            <li>✓ Understand the quiz rules</li>
                        </ul>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Start Quiz',
                cancelButtonText: 'Not Yet',
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'bg-gray-500 text-white px-6 py-3 rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'quiz_new.php';
                }
            });
        }

        function viewResults() {
            window.location.href = 'result.php';
        }

        function logout() {
            Swal.fire({
                title: 'Logout',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'bg-gray-500 text-white px-6 py-3 rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>
</html>
