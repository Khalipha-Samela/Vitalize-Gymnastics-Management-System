<?php
session_start();
include 'config.php';

// Create users table if it doesn't exist
$createTableQuery = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'coach', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
";
$pdo->exec($createTableQuery);

// Handle form submissions
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $full_name = trim($_POST['full_name']);
        
        // Validation
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (empty($password)) $errors[] = "Password is required";
        if (empty($full_name)) $errors[] = "Full name is required";
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists";
        }
        
        // If no errors, register user
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                $success = "Registration successful! You can now login.";
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    } elseif (isset($_POST['login'])) {
        // Login logic
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($username)) $errors[] = "Username is required";
        if (empty($password)) $errors[] = "Password is required";
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Redirect to main application
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        }
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitalize Gymnastics - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --dark-blue: #182341;
            --medium-blue: #273250;
            --bright-blue: #406AFF;
            --yellow: #F9E469;
            --light: rgba(255, 255, 255, 0.85);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.18);
            --glass-shadow: rgba(0, 0, 0, 0.1);
            --text-dark: #1a1a2e;
            --text-light: rgba(255, 255, 255, 0.9);
        }

        body {
            background: linear-gradient(135deg, var(--dark-blue), var(--medium-blue), var(--bright-blue));
            background-attachment: fixed;
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .acrobat_logo {
            width: 100px;
            margin-bottom: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 var(--glass-shadow);
            animation: scaleIn 0.5s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 50px;
            color: var(--yellow);
            margin-bottom: 15px;
            display: block;
        }

        .login-header h1 {
            color: var(--yellow);
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
            font-size: 1rem;
            opacity: 0.9;
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px; /* space for eye icon */
        }

        .password-wrapper .toggle-eye {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: rgba(255,255,255,0.7);
            font-size: 18px;
            transition: color 0.2s;
        }

        .password-wrapper .toggle-eye:hover {
            color: var(--yellow);
        }

        .tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 5px;
        }

        .tab {
            flex: 1;
            padding: 14px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            border-radius: 12px 12px 0 0;
            text-align: center;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .tab i {
            margin-right: 8px;
        }

        .tab.active {
            background: rgba(64, 106, 255, 0.3);
            border-color: var(--bright-blue);
            border-bottom-color: transparent;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--yellow);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            color: var(--text-light);
            backdrop-filter: blur(5px);
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--bright-blue);
            box-shadow: 0 0 0 3px rgba(64, 106, 255, 0.3);
            outline: none;
            background: rgba(39, 50, 80, 0.8);
        }

        button {
            width: 100%;
            background: linear-gradient(to right, var(--bright-blue), var(--medium-blue));
            color: white;
            border: none;
            padding: 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 10px;
        }

        button i {
            margin-right: 8px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: linear-gradient(to right, var(--medium-blue), var(--bright-blue));
        }

        .message {
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 15px;
            background: rgba(46, 204, 113, 0.2);
            color: #d1f7e3;
            border-left: 5px solid #2ecc71;
            font-weight: 500;
            display: flex;
            align-items: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.2);
            color: #ffccd5;
            border-left: 5px solid #e74c3c;
        }

        .form-toggle {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .form-toggle a {
            color: var(--yellow);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-toggle a:hover {
            text-decoration: underline;
        }

        .form {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                padding: 20px;
                max-width: 100%;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .login-header i {
                font-size: 40px;
            }
            
            .tab {
                padding: 12px;
                font-size: 0.9rem;
            }
            
            input[type="text"],
            input[type="email"],
            input[type="password"] {
                padding: 12px;
                font-size: 14px;
            }
            
            button {
                padding: 14px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .login-header h1 {
                font-size: 1.6rem;
            }
            
            .login-header p {
                font-size: 0.9rem;
            }
            
            .tab {
                min-width: 100px;
                padding: 10px;
                font-size: 0.8rem;
            }
            
            .tab i {
                margin-right: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="acrobat.png" alt="Acrobat Logo" class="acrobat_logo">
            <h1>Vitalize Gymnastics</h1>
            <p>Management System</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showForm('login')">
                <i class="fas fa-sign-in-alt"></i> Login
            </div>
            <div class="tab" onclick="showForm('register')">
                <i class="fas fa-user-plus"></i> Register
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i>
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" class="form active" method="POST" action="">
            <div class="form-group">
                <label for="loginUsername">Username</label>
                <input type="text" id="loginUsername" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="loginPassword" name="password" required>
                    <i class="fas fa-eye toggle-eye" onclick="togglePassword('loginPassword', this)"></i>
                </div>
            </div>
            
            <button type="submit" name="login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div class="form-toggle">
                Don't have an account? <a href="#" onclick="showForm('register'); return false;">Register here</a>
            </div>
        </form>
        
        <form id="registerForm" class="form" method="POST" action="">
            <div class="form-group">
                <label for="registerFullName">Full Name</label>
                <input type="text" id="registerFullName" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="registerUsername">Username</label>
                <input type="text" id="registerUsername" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="registerEmail">Email</label>
                <input type="email" id="registerEmail" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="registerPassword">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="registerPassword" name="password" required>
                    <i class="fas fa-eye toggle-eye" onclick="togglePassword('registerPassword', this)"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="registerConfirmPassword">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="registerConfirmPassword" name="confirm_password" required>
                    <i class="fas fa-eye toggle-eye" onclick="togglePassword('registerConfirmPassword', this)"></i>
                </div>
            </div>
            
            <button type="submit" name="register">
                <i class="fas fa-user-plus"></i> Register
            </button>
            
            <div class="form-toggle">
                Already have an account? <a href="#" onclick="showForm('login'); return false;">Login here</a>
            </div>
        </form>
    </div>

    <script>
        function showForm(formType) {
            // Hide all forms
            document.querySelectorAll('.form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form
            document.getElementById(formType + 'Form').classList.add('active');
            
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate the correct tab
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.textContent.toLowerCase().includes(formType)) {
                    tab.classList.add('active');
                }
            });
        }

        function togglePassword(inputId, icon) {
            var field = document.getElementById(inputId);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
        
        // Check if we should show register form based on success message
        <?php if (!empty($success)): ?>
            showForm('login');
        <?php endif; ?>
    </script>
</body>
</html>