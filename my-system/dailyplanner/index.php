<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['avatar']    = $user['avatar'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – Daily Planner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            min-height: 100vh;
            background: #0b0f19;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-wrap {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* LEFT PANEL */
        .auth-left {
            flex: 1;
            background: #0d1322;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            border-right: 1px solid rgba(255,255,255,0.06);
        }
        .auth-left .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 3rem;
            text-decoration: none;
        }
        .auth-left .brand-icon {
            width: 44px; height: 44px;
            background: #5b67f7;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(91,103,247,0.45);
        }
        .auth-left .brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }
        .auth-left .hero-text {
            text-align: center;
            max-width: 320px;
        }
        .auth-left .hero-text h1 {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.25;
            margin: 0 0 0.75rem;
            color: #fff;
        }
        .auth-left .hero-text h1 span { color: #5b67f7; }
        .auth-left .hero-text p {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }
        .auth-left .features {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
            max-width: 300px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .feature-item .fi-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
        }
        .feature-item span {
            font-size: 0.875rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        /* RIGHT PANEL */
        .auth-right {
            width: 480px;
            min-width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
        }
        .auth-card .card-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 0.3rem;
        }
        .auth-card .card-sub {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0 0 2rem;
        }

        /* ERROR ALERT */
        .auth-error {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        /* FORM */
        .auth-field { margin-bottom: 1rem; }
        .auth-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.35rem;
        }
        .auth-field input {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.09);
            background: rgba(255,255,255,0.04);
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .auth-field input:focus {
            border-color: #5b67f7;
            background: rgba(91,103,247,0.06);
        }
        .auth-field input::placeholder { color: #475569; }

        .auth-submit {
            width: 100%;
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            background: #5b67f7;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s, transform 0.1s;
            position: relative;
        }
        .auth-submit:hover { background: #4c56e0; transform: translateY(-1px); }

        .auth-footer {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.85rem;
            color: #64748b;
        }
        .auth-footer a {
            color: #818cf8;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover { color: #a5b4fc; }

        /* Responsive */
        @media (max-width: 820px) {
            .auth-left { display: none; }
            .auth-right { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
<div class="auth-wrap">

    <!-- LEFT -->
    <div class="auth-left">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M12 23C16.1421 23 19.5 19.6421 19.5 15.5C19.5 11.5 16 8.5 14.5 5C14 7.5 12 9 10.5 10C9 11 8 12.5 8 15.5C8 16.5 8.3 17.5 9 18.2C8.5 18 8 17.5 7.5 16.8C6.5 15.3 6.5 13.5 7 12C5.2 13.8 4.5 16.3 5 18.8C5.6 21.3 8.2 23 12 23Z"/></svg>
            </div>
            <span class="brand-name">DailyPlanner</span>
        </a>
        <div class="hero-text">
            <h1>Start Your Day<br>Be <span>Productive</span></h1>
            <p>Organise your tasks, track your progress, and stay on top of your daily goals.</p>
        </div>
        <div class="features">
            <div class="feature-item">
                <div class="fi-icon">✅</div>
                <span>Create and manage tasks with priorities</span>
            </div>
            <div class="feature-item">
                <div class="fi-icon">🔔</div>
                <span>Get notified when tasks are due</span>
            </div>
            <div class="feature-item">
                <div class="fi-icon">📊</div>
                <span>Track completion history and progress</span>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="auth-right">
        <div class="auth-card">
            <h2 class="card-title">Welcome back</h2>
            <p class="card-sub">Sign in to continue to your planner</p>

            <?php if ($error): ?>
            <div class="auth-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-field">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="auth-field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" id="loginBtn" class="auth-submit"><span class="btn-text">Sign In</span></button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </p>
        </div>
    </div>

</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    btn.classList.add('btn-loading');
    btn.disabled = true;
});
</script>
</body>
</html>
