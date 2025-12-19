<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App Demo - Features Showcase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .feature-card {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <h1 class="text-4xl sm:text-5xl font-bold mb-4 animate-fadeIn">🎓 Quiz App Demo</h1>
                <p class="text-xl text-white/90 mb-6 animate-fadeIn" style="animation-delay: 0.2s;">Modern, Responsive, Feature-Rich Quiz System</p>
                <div class="flex flex-wrap justify-center gap-4 animate-fadeIn" style="animation-delay: 0.4s;">
                    <a href="setup.php" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
                        Setup Database
                    </a>
                    <a href="login.php" class="bg-purple-800 text-white px-8 py-3 rounded-lg font-bold hover:bg-purple-900 transition">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Features Grid -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">✨ Features Implemented</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-purple-500" style="animation-delay: 0.1s;">
                    <div class="text-4xl mb-4">🎨</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Modern UI/UX</h3>
                    <p class="text-gray-600 text-sm">React-inspired design with smooth animations, gradient backgrounds, and glass-morphism effects.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-blue-500" style="animation-delay: 0.2s;">
                    <div class="text-4xl mb-4">📱</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Fully Responsive</h3>
                    <p class="text-gray-600 text-sm">Works perfectly on mobile, tablet, and desktop devices with adaptive layouts.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-green-500" style="animation-delay: 0.3s;">
                    <div class="text-4xl mb-4">🔔</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">SweetAlert2</h3>
                    <p class="text-gray-600 text-sm">Beautiful styled alerts with custom purple theme throughout the application.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-red-500" style="animation-delay: 0.4s;">
                    <div class="text-4xl mb-4">🔐</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Authentication</h3>
                    <p class="text-gray-600 text-sm">Only authorized students can access. Includes test account (TEST001) for testing.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-yellow-500" style="animation-delay: 0.5s;">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Results Page</h3>
                    <p class="text-gray-600 text-sm">Comprehensive results with charts, score breakdown, and question-by-question review.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-xl p-6 shadow-lg border-t-4 border-indigo-500" style="animation-delay: 0.6s;">
                    <div class="text-4xl mb-4">📸</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Proctoring</h3>
                    <p class="text-gray-600 text-sm">Camera monitoring with snapshots and tab-switch detection for exam integrity.</p>
                </div>
            </div>
        </div>

        <!-- Test Account Info -->
        <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-2xl p-8 shadow-xl mb-12 border-l-4 border-green-500">
            <h2 class="text-2xl font-bold text-green-800 mb-4 flex items-center">
                <span class="text-3xl mr-3">🧪</span>
                Test Account
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-green-700 mb-1">Matric Number</p>
                    <p class="text-2xl font-bold text-green-900 bg-white rounded-lg px-4 py-2 inline-block">TEST001</p>
                </div>
                <div>
                    <p class="text-sm text-green-700 mb-1">Name</p>
                    <p class="text-2xl font-bold text-green-900">Test Student</p>
                </div>
            </div>
            <p class="text-sm text-green-700 mt-4">Use this account to test all features without affecting real student data.</p>
        </div>

        <!-- Setup Instructions -->
        <div class="bg-white rounded-2xl p-8 shadow-xl mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="text-3xl mr-3">🚀</span>
                Quick Setup Guide
            </h2>
            
            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold text-lg">1</div>
                    <div>
                        <h3 class="font-bold text-gray-800 mb-1">Start XAMPP Services</h3>
                        <p class="text-gray-600 text-sm">Open XAMPP Control Panel and start Apache and MySQL services.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold text-lg">2</div>
                    <div>
                        <h3 class="font-bold text-gray-800 mb-1">Setup Database</h3>
                        <p class="text-gray-600 text-sm">Click "Setup Database" button above or visit setup.php to create tables and seed questions.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold text-lg">3</div>
                    <div>
                        <h3 class="font-bold text-gray-800 mb-1">Login and Test</h3>
                        <p class="text-gray-600 text-sm">Use TEST001 to login and explore all features including quiz, dashboard, and results.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Authorized Students -->
        <div class="bg-white rounded-2xl p-8 shadow-xl mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="text-3xl mr-3">👥</span>
                Authorized Students
            </h2>
            <div class="bg-blue-50 rounded-lg p-4 mb-4">
                <p class="text-blue-800 font-semibold">14 authorized students + 1 test account</p>
                <p class="text-sm text-blue-600 mt-1">Only students on the authorized list can access the quiz system.</p>
            </div>
            <p class="text-gray-600 text-sm">See SETUP_GUIDE.md for the complete list of authorized students.</p>
        </div>

        <!-- Technical Details -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-8 shadow-xl">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="text-3xl mr-3">⚙️</span>
                Technical Stack
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-purple-800 mb-3">Backend</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✓ PHP 7.4+</li>
                        <li>✓ MySQL Database</li>
                        <li>✓ RESTful API</li>
                        <li>✓ Session Management</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-bold text-purple-800 mb-3">Frontend</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li>✓ HTML5 & CSS3</li>
                        <li>✓ Tailwind CSS</li>
                        <li>✓ JavaScript (ES6+)</li>
                        <li>✓ SweetAlert2</li>
                        <li>✓ Chart.js</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="gradient-bg text-white mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-lg font-semibold mb-2">Web Development Students 100 Level</p>
            <p class="text-white/80">Tutor: Akintunde Dolapo Elisha - 07082184560</p>
            <p class="text-white/60 text-sm mt-4">&copy; 2025 Quiz App System</p>
        </div>
    </footer>

    <!-- Floating decoration -->
    <div class="fixed bottom-10 right-10 w-20 h-20 bg-purple-200 rounded-full opacity-50 animate-float" style="animation-delay: 0s;"></div>
    <div class="fixed top-20 right-20 w-16 h-16 bg-blue-200 rounded-full opacity-50 animate-float" style="animation-delay: 1s;"></div>
</body>
</html>
