<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Quiz App Setup</h1>
            
            <div class="space-y-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <h2 class="font-bold text-blue-800 mb-2">Database Setup Instructions:</h2>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-blue-900">
                        <li>Make sure XAMPP MySQL is running</li>
                        <li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>
                        <li>Click on "SQL" tab</li>
                        <li>Copy the SQL from setup_database.sql and run it</li>
                        <li>Run init_db.php to seed questions</li>
                    </ol>
                </div>

                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <h2 class="font-bold text-green-800 mb-2">Quick Setup (Alternative):</h2>
                    <button onclick="setupDatabase()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                        Run Database Setup
                    </button>
                    <p class="text-xs text-green-700 mt-2">This will create the database and tables automatically</p>
                </div>

                <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                    <h2 class="font-bold text-purple-800 mb-2">Test Account:</h2>
                    <p class="text-sm text-purple-900">
                        <strong>Matric Number:</strong> TEST001<br>
                        <strong>Name:</strong> Test Student
                    </p>
                </div>

                <div class="mt-6">
                    <a href="login.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                        Go to Login Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function setupDatabase() {
            Swal.fire({
                title: 'Setting up database...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('setup_db_ajax.php', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: data.message,
                        confirmButtonText: 'Go to Login'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Setup Failed',
                        text: data.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Setup failed: ' + error.message
                });
            }
        }
    </script>
</body>
</html>
