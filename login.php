<?php
session_start();

// List of authorized students from the image
$authorizedStudents = [
    ['matric' => '2025000831', 'name' => 'SANNI Olayinka', 'phone' => '8084343242'],
    ['matric' => '2025002070', 'name' => 'Shobodun Faridat Tolulope', 'phone' => '9128823922'],
    ['matric' => '2025000776', 'name' => 'DIPEOLU AMAL TITILOPE', 'phone' => '8063966934'],
    ['matric' => 'NIL', 'name' => 'Jamiu Abdullahi Olalekan', 'phone' => '7073247811'],
    ['matric' => '2025003523', 'name' => 'LIGALI OLUWASEGUN OLUMAYOWA', 'phone' => '8126479848'],
    ['matric' => '2025002782', 'name' => 'Adekanye seyi semilore', 'phone' => '9115660920'],
    ['matric' => '2025007581', 'name' => 'Adepetu Peter taiwo', 'phone' => '8077923006'],
    ['matric' => '2025001994', 'name' => 'Taofeeq uthman Timilehin', 'phone' => '8122069891'],
    ['matric' => '2025007041', 'name' => 'Oluwafemi Daniel Iyiola', 'phone' => '8128711370'],
    ['matric' => '2025003519', 'name' => 'Alagbe Michael Kehinde', 'phone' => '8128972860'],
    ['matric' => '2025006425', 'name' => 'Ojeabi-Champion Praise Erinayo', 'phone' => '9069380243'],
    ['matric' => '2025003870', 'name' => 'Obiye Isaac Osareemen', 'phone' => '9114220817'],
    ['matric' => '2025003210', 'name' => 'ADEMOLA BOLUWATIFE JEREMIAH', 'phone' => '8025073532'],
    ['matric' => '2025002074', 'name' => 'Olatunji Testimony Israel', 'phone' => '9015037316'],
    // Test account
    ['matric' => 'TEST001', 'name' => 'Test Student', 'phone' => '1234567890']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['matric_no'] ?? '');
    
    // Check if student is authorized by matric number OR phone number
    $found = false;
    foreach ($authorizedStudents as $student) {
        if (strtoupper($student['matric']) === strtoupper($input) || 
            $student['phone'] === $input) {
            $_SESSION['student_matric'] = $student['matric'];
            $_SESSION['student_name'] = $student['name'];
            $_SESSION['student_phone'] = $student['phone'];
            $found = true;
            break;
        }
    }
    
    if ($found) {
        // Try to fetch group from students table; default to Group 1
        $group = 1;
        try {
            require __DIR__ . '/db.php';
            $pdo = db();
            $gstmt = $pdo->prepare('SELECT group_id FROM students WHERE identifier = ? LIMIT 1');
            $gstmt->execute([$_SESSION['student_matric']]);
            $gRow = $gstmt->fetch();
            if ($gRow && isset($gRow['group_id'])) {
                $group = (int)$gRow['group_id'];
            }
        } catch (Exception $e) {
            $group = 1;
        }
        $_SESSION['student_group'] = $group;
        echo json_encode(['success' => true, 'redirect' => 'quiz_new.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to take this quiz. Please contact your instructor.']);
    }
    exit;
}

// Check if already logged in
if (isset($_SESSION['student_matric'])) {
    header('Location: quiz_new.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Login - Web Development Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-slideIn {
            animation: slideIn 0.8s ease-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.6);
        }

        /* Custom SweetAlert2 styling */
        .swal2-popup {
            border-radius: 20px;
            padding: 2rem;
        }

        .swal2-title {
            color: #667eea;
            font-weight: 700;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 600;
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <!-- Floating decoration elements -->
    <div class="fixed top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-float" style="animation-delay: 0s;"></div>
    <div class="fixed bottom-10 right-10 w-32 h-32 bg-white/10 rounded-full animate-float" style="animation-delay: 1s;"></div>
    <div class="fixed top-1/2 right-20 w-16 h-16 bg-white/10 rounded-full animate-float" style="animation-delay: 2s;"></div>

    <!-- Main Login Container -->
    <div class="w-full max-w-md login-container animate-fadeIn">
        <!-- Logo/Header Section -->
        <div class="text-center mb-8 animate-slideIn">
            <div class="inline-block p-4 bg-white/20 rounded-full mb-4">
                <i class='bx bxs-book-open text-white text-6xl'></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">Quiz Portal</h1>
            <p class="text-white/90 text-lg">Web Development Students 100 Level</p>
            <p class="text-white/75 text-sm mt-1">Tutor: Akintunde Dolapo Elisha</p>
        </div>

        <!-- Login Form -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl animate-slideIn" style="animation-delay: 0.2s;">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Student Login</h2>
            
            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="matric_no" class="block text-sm font-semibold text-gray-700 mb-2">
                        Matriculation Number or Phone Number
                    </label>
                    <input 
                        type="text" 
                        id="matric_no" 
                        name="matric_no" 
                        required
                        placeholder="Enter matric number or phone number"
                        class="input-focus w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-purple-500 focus:outline-none transition-all duration-300 text-gray-800 font-medium"
                    >
                    <p class="text-xs text-gray-500 mt-1">You can use either your matric number or phone number</p>
                </div>

                <button 
                    type="submit" 
                    class="btn-hover w-full bg-gradient-to-r from-purple-600 to-purple-800 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 transform"
                >
                    <span class="flex items-center justify-center">
                        <i class='bx bx-log-in text-xl mr-2'></i>
                        Login to Quiz
                    </span>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-sm">
                <span class="text-white/80">&copy; Web Dev </span>
                <span class="text-lg font-bold gradient-text">Group 1</span>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const matricNo = document.getElementById('matric_no').value.trim();
            
            if (!matricNo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Please enter your matriculation number',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Verifying...',
                text: 'Please wait while we verify your credentials',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const formData = new FormData();
                formData.append('matric_no', matricNo);
                
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Welcome!',
                        text: 'Login successful. Starting your quiz...',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'animate-fadeIn'
                        }
                    }).then(() => {
                        window.location.href = data.redirect || 'quiz_new.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: data.message,
                        confirmButtonText: 'Try Again',
                        customClass: {
                            confirmButton: 'swal2-confirm'
                        }
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'swal2-confirm'
                    }
                });
            }
        });
    </script>
</body>
</html>
