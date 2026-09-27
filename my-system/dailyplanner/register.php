<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name']);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "That username is already taken.";
        } else {
            $avatar_name     = 'man1';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, avatar) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $username, $hashed_password, $avatar_name])) {
                $success = "Account created! You can now sign in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – Daily Planner</title>
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
        .brand-icon {
            width: 44px; height: 44px;
            background: #5b67f7;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(91,103,247,0.45);
        }
        .brand-name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }
        .hero-text {
            text-align: center;
            max-width: 320px;
        }
        .hero-text h1 {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.25;
            margin: 0 0 0.75rem;
            color: #fff;
        }
        .hero-text h1 span { color: #5b67f7; }
        .hero-text p {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }
        .steps {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            width: 100%;
            max-width: 300px;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .step-num {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(91,103,247,0.2);
            border: 1px solid rgba(91,103,247,0.35);
            color: #818cf8;
            font-size: 0.8rem;
            font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .step-item span {
            font-size: 0.82rem;
            color: #94a3b8;
        }

        /* RIGHT PANEL */
        .auth-right {
            width: 500px;
            min-width: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
        }
        .card-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 0.3rem;
        }
        .card-sub {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0 0 2rem;
        }

        /* ALERTS */
        .auth-alert {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .auth-alert.error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
        }
        .auth-alert.success {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.25);
            color: #34d399;
        }

        /* FORM */
        .auth-field { margin-bottom: 0.9rem; }
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

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

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

        @media (max-width: 860px) {
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
            <h1>Join & Start<br>Being <span>Productive</span></h1>
            <p>Create your free account and take control of your daily schedule.</p>
        </div>
        <div class="steps">
            <div class="step-item">
                <div class="step-num">1</div>
                <span>Fill in your details below</span>
            </div>
            <div class="step-item">
                <div class="step-num">2</div>
                <span>Sign in with your new account</span>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <span>Start adding and tracking tasks</span>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="auth-right">
        <div class="auth-card">
            <h2 class="card-title">Create account</h2>
            <p class="card-sub">It's free and only takes a minute</p>

            <?php if ($error): ?>
            <div class="auth-alert error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="auth-alert success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <?= htmlspecialchars($success) ?>
                <a href="index.php" style="margin-left:0.5rem;color:#34d399;font-weight:700;">Sign in →</a>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-field">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="e.g. Juan dela Cruz" required autofocus value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="auth-field">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="auth-field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Min. 6 characters" required>
                    </div>
                    <div class="auth-field">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirmPw" placeholder="Repeat password" required oninput="checkMatch()">
                    </div>
                </div>
                <p id="pwMatchHint" style="font-size:0.78rem;margin:-0.4rem 0 0.75rem;display:none;"></p>

                <button type="submit" id="registerBtn" class="auth-submit"><span class="btn-text">Create Account</span></button>
            </form>

            <p class="auth-footer">
                Already have an account? <a href="index.php">Sign in</a>
            </p>
        </div>
    </div>

</div>

<script>
function checkMatch() {
    var hint = document.getElementById('pwMatchHint');
    var pw   = document.querySelector('input[name="password"]').value;
    var cf   = document.getElementById('confirmPw').value;
    if (!cf) { hint.style.display = 'none'; return; }
    if (pw === cf) {
        hint.style.display = 'block';
        hint.style.color   = '#34d399';
        hint.textContent   = '✓ Passwords match';
    } else {
        hint.style.display = 'block';
        hint.style.color   = '#f87171';
        hint.textContent   = '✗ Passwords do not match';
    }
}

document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('registerBtn');
    btn.classList.add('btn-loading');
    btn.disabled = true;
});
</script>
</body>
</html>
