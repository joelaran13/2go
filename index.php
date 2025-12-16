<?php
require 'config.php';

// Handle Login/Register Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = (strpos($email, 'admin') !== false) ? 'admin' : 'student';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $name;
            header("Location: " . ($role == 'admin' ? 'admin.php' : 'student.php'));
            exit;
        } catch (PDOException $e) {
            $error = "Email already exists!";
        }
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            header("Location: " . ($user['role'] == 'admin' ? 'admin.php' : 'student.php'));
            exit;
        } else {
            $error = "Invalid credentials!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2Go! - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-bg {
            background-image: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="login-bg flex items-center justify-center min-h-screen relative">
    
    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>

    <!-- Info Button (Top Right) -->
    <button onclick="toggleAbout()" class="absolute top-6 right-6 z-20 text-white/80 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-3 rounded-full backdrop-blur-md border border-white/20 shadow-lg group">
        <i class="fas fa-info text-xl w-6 h-6 flex items-center justify-center"></i>
        <span class="absolute right-full mr-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-black/80 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">About Us</span>
    </button>

    <!-- Main Card -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden relative z-10 mx-4 animate-fade-in-up transition-all duration-300" id="mainCard">
        <div class="bg-orange-500 p-8 text-center">
            <div class="mx-auto w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg transform transition hover:scale-110 duration-300">
                <i class="fas fa-utensils text-2xl text-orange-500"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">2Go!</h1>
            <p class="text-orange-100 mt-2">Student Food Ordering Portal</p>
        </div>

        <div class="p-8">
            <!-- Toggle Buttons -->
            <div class="flex justify-center mb-6 bg-gray-100 p-1 rounded-lg">
                <button onclick="toggleForm('login')" id="loginBtn" class="w-1/2 py-2 rounded-md font-medium text-sm transition-all bg-white shadow text-gray-800">Login</button>
                <button onclick="toggleForm('register')" id="registerBtn" class="w-1/2 py-2 rounded-md font-medium text-sm transition-all text-gray-500 hover:text-gray-700">Register</button>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-500 text-sm p-3 rounded-lg mb-4 flex items-center border border-red-100">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="authForm" class="space-y-4">
                <div id="nameField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" name="name" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none transition-all" placeholder="John Doe">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-3 text-gray-400"></i>
                        <input type="email" name="email" required class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none transition-all" placeholder="student@university.edu">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                        <input type="password" name="password" required class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 outline-none transition-all" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" name="login" id="submitBtn" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-lg transition-all transform hover:scale-[1.02] shadow-lg mt-2">
                    Login
                </button>
            </form>
        </div>
    </div>

    <!-- About Us Modal (Overlay) -->
    <div id="aboutModal" class="hidden fixed inset-0 z-30 flex items-center justify-center p-4 animate-fade-in">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md" onclick="toggleAbout()"></div>
        
        <!-- Content -->
        <div class="bg-white rounded-3xl max-w-sm w-full p-8 relative z-40 shadow-2xl text-center transform transition-all scale-100">
            <button onclick="toggleAbout()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
            
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 ring-4 ring-orange-50">
                <i class="fas fa-info text-3xl text-orange-600"></i>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-2">About Us</h2>
            <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                2Go! is a student-run project designed to simplify food ordering on campus. We connect hungry students with the best local stalls for a seamless pickup experience.
            </p>

            <div class="space-y-3 text-left bg-gray-50 p-5 rounded-2xl mb-6 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-orange-500"><i class="fas fa-code"></i></div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wide">Version</p>
                        <p class="text-sm font-semibold text-gray-700">1.0.0 (Beta)</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-orange-500"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wide">Developed By</p>
                        <p class="text-sm font-semibold text-gray-700">Infinite Capital Group</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center text-orange-500"><i class="fas fa-university"></i></div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wide">Project</p>
                        <p class="text-sm font-semibold text-gray-700">2Go!</p>
                    </div>
                </div>
            </div>

            <button onclick="toggleAbout()" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3.5 rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>

    <script>
        // Animation config
        document.head.insertAdjacentHTML("beforeend", `<style>@keyframes fadeInUp { from { opacity: 0; transform: translate3d(0, 20px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } } .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }</style>`)

        function toggleForm(type) {
            const nameField = document.getElementById('nameField');
            const submitBtn = document.getElementById('submitBtn');
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');

            if (type === 'register') {
                nameField.classList.remove('hidden');
                submitBtn.name = 'register';
                submitBtn.innerText = 'Sign Up';
                loginBtn.classList.remove('bg-white', 'shadow', 'text-gray-800');
                registerBtn.classList.add('bg-white', 'shadow', 'text-gray-800');
            } else {
                nameField.classList.add('hidden');
                submitBtn.name = 'login';
                submitBtn.innerText = 'Login';
                registerBtn.classList.remove('bg-white', 'shadow', 'text-gray-800');
                loginBtn.classList.add('bg-white', 'shadow', 'text-gray-800');
            }
        }

        function toggleAbout() {
            const modal = document.getElementById('aboutModal');
            const mainCard = document.getElementById('mainCard');
            
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                // Push main card back visually
                mainCard.style.transform = 'scale(0.95)';
                mainCard.style.opacity = '0.5';
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                // Restore main card
                mainCard.style.transform = 'scale(1)';
                mainCard.style.opacity = '1';
            }
        }
    </script>
</body>
</html>