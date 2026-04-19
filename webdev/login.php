<?php
require_once 'config.php';

$error = '';
$success = '';
$show_register = false;

if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg']);
}

if (isset($_GET['register'])) {
    $show_register = true;
}

// LOGIN PROCESS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit();
        } else {
            $error = '❌ Invalid password!';
        }
    } else {
        $error = '❌ User not found!';
    }
    $stmt->close();
}

// ADMIN REGISTRATION PROCESS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_admin'])) {
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];
    $admin_confirm_password = $_POST['admin_confirm_password'];
    
    // Validation
    if (empty($admin_username) || empty($admin_password) || empty($admin_confirm_password)) {
        $error = '❌ All fields are required!';
        $show_register = true;
    } elseif (strlen($admin_username) < 4) {
        $error = '❌ Username must be at least 4 characters!';
        $show_register = true;
    } elseif (strlen($admin_password) < 6) {
        $error = '❌ Password must be at least 6 characters!';
        $show_register = true;
    } elseif ($admin_password !== $admin_confirm_password) {
        $error = '❌ Passwords do not match!';
        $show_register = true;
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $admin_username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = '❌ Username already exists!';
            $show_register = true;
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Hash password and create admin account
            $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
            
            $insert_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $admin_username, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = '✅ Admin account created successfully! You can now login.';
                $show_register = false;
            } else {
                $error = '❌ Error creating admin account: ' . $conn->error;
                $show_register = true;
            }
            $insert_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Information System</title>
    <link rel="stylesheet" href="css/landing.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 0;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }

        .tab-btn.active {
            background: white;
            color: #667eea;
            border-bottom-color: white;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .tab-content {
            display: none;
            animation: slideIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #999;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 11px 13px;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.2);
        }

        .btn-login, .btn-register {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover, .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .error-message {
            color: #e74c3c;
            background: #fadbd8;
            padding: 12px 13px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
            border-left: 4px solid #e74c3c;
        }

        .success-message {
            color: #27ae60;
            background: #d5f4e6;
            padding: 12px 13px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
            border-left: 4px solid #27ae60;
        }

        .demo-info {
            background: #ecf0f1;
            padding: 13px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
            border-left: 4px solid #3498db;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 13px;
        }

        .demo-cred {
            margin: 6px 0;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 6px;
            border-radius: 3px;
        }

        .register-notice {
            background: #d1ecf1;
            color: #0c5460;
            padding: 12px 13px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 12px;
            border-left: 4px solid #17a2b8;
        }

        .toggle-auth {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #555;
        }

        .toggle-auth a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .toggle-auth a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 15px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-btn {
                border-radius: 6px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- Tabs -->
            <div class="tabs">
                <button type="button" class="tab-btn <?php echo !$show_register ? 'active' : ''; ?>" onclick="switchTab('login', event)">
                    🔐 Login
                </button>
                <button type="button" class="tab-btn <?php echo $show_register ? 'active' : ''; ?>" onclick="switchTab('register', event)">
                    ➕ Register Admin
                </button>
            </div>

            <!-- Login Form -->
            <div id="login" class="tab-content <?php echo !$show_register ? 'active' : ''; ?>">
                <div class="login-container">
                    <div class="login-header">
                        <h1>📚 SIS</h1>
                        <p>Student Information System</p>
                    </div>
                    
                    <?php if ($error && !$show_register): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success && !$show_register): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required placeholder="Enter your password">
                        </div>
                        
                        <button type="submit" name="login" class="btn-login">Login</button>
                    </form>
                    
                    <div class="toggle-auth">
                        Want to create an admin account? 
                        <a onclick="switchToRegister()">Click here</a>
                    </div>
                    
                    <div class="demo-info">
                        <strong>📝 Demo Credentials:</strong>
                        <div class="demo-cred">👨‍💼 Admin: admin / admin123</div>
                        <div class="demo-cred">👨‍🎓 Student: 2024-0001 / admin123</div>
                    </div>
                </div>
            </div>

            <!-- Register Admin Form -->
            <div id="register" class="tab-content <?php echo $show_register ? 'active' : ''; ?>">
                <div class="login-container">
                    <div class="login-header">
                        <h1>➕ Register Admin</h1>
                        <p>Create a new admin account</p>
                    </div>
                    
                    <?php if ($error && $show_register): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success && $show_register): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="admin_username">Admin Username:</label>
                            <input type="text" id="admin_username" name="admin_username" required autofocus placeholder="Create username (min 4 chars)" minlength="4">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Password:</label>
                            <input type="password" id="admin_password" name="admin_password" required placeholder="Create password (min 6 chars)" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_confirm_password">Confirm Password:</label>
                            <input type="password" id="admin_confirm_password" name="admin_confirm_password" required placeholder="Confirm password">
                        </div>
                        
                        <button type="submit" name="register_admin" class="btn-register">Create Admin Account</button>
                    </form>
                    
                    <div class="toggle-auth">
                        Already have an account? 
                        <a onclick="switchToLogin()">Login here</a>
                    </div>
                    
                    <div class="register-notice">
                        <strong>⚠️ Important:</strong> This is a temporary registration for creating admin accounts. In production, restrict this access.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab, event) {
            if (event) {
                event.preventDefault();
            }
            
            // Hide all tabs
            document.getElementById('login').classList.remove('active');
            document.getElementById('register').classList.remove('active');
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab and mark button as active
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }

        function switchToRegister() {
            const registerTab = document.querySelectorAll('.tab-btn')[1];
            registerTab.click();
        }

        function switchToLogin() {
            const loginTab = document.querySelectorAll('.tab-btn')[0];
            loginTab.click();
        }
    </script>
</body>
</html>