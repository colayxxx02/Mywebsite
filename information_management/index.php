<?php
session_start();

// PROCESS LOGIN DIRI SA TAAS — para mo-work ang redirect
$error = "";
if (isset($_POST['login'])) {
    include 'db.php';
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user = $conn->query("SELECT * FROM users WHERE email='$email'")->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'librarian') {
            header("Location: librarian/dashboard.php");
        } else {
            header("Location: member/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}

// Kung naka-login na, i-redirect dayon
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'librarian') {
        header("Location: librarian/dashboard.php");
    } else {
        header("Location: member/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookShare - Library Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- NAVBAR -->
    <nav>
        <div class="logo">
            <span>📚</span> BookShare
        </div>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <button class="btn-login" onclick="document.getElementById('loginModal').style.display='block'">Login</button>
            <button class="btn-signup" onclick="window.location='signup.php'">Sign Up</button>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <h1>Smart Library<br><span>Management</span></h1>
        <p>BookShare makes borrowing, returning, and managing library books easier than ever — for members and librarians alike.</p>
        <div class="hero-btns">
            <button class="btn-primary" onclick="document.getElementById('loginModal').style.display='block'">Get Started</button>
            <button class="btn-secondary" onclick="document.getElementById('about').scrollIntoView({behavior:'smooth'})">Learn More</button>
        </div>
    </section>

    <!-- APP PREVIEW -->
    <div class="preview">
        <div class="preview-bar">
            <div class="dot" style="background:#ff5f57;"></div>
            <div class="dot" style="background:#febc2e;"></div>
            <div class="dot" style="background:#28c840;"></div>
            <span>BookShare Library System</span>
        </div>
        <div class="preview-content">
            <div class="preview-sidebar">
                <div style="font-weight:700; color:#1a1a2e; margin-bottom:12px; font-size:15px;">📚 BookShare</div>
                <div class="menu-item active">📖 Browse Books</div>
                <div class="menu-item">🕐 My Borrows</div>
                <div class="menu-item">📋 History</div>
                <div class="menu-item">👤 My Profile</div>
            </div>
            <div class="preview-main">
                <div style="font-weight:700; font-size:16px; color:#1a1a2e;">Available Books <span style="background:#4e6ef2;color:#fff;padding:2px 10px;border-radius:10px;font-size:12px;">24</span></div>
                <div class="book-grid">
                    <div class="book-card"><span class="book-icon">📗</span>Science Fiction</div>
                    <div class="book-card"><span class="book-icon">📘</span>History</div>
                    <div class="book-card"><span class="book-icon">📙</span>Technology</div>
                    <div class="book-card"><span class="book-icon">📕</span>Philosophy</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="features" id="features">
        <h2>Everything You Need</h2>
        <p class="sub">Designed for both members and librarians</p>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon">📖</div>
                <h3>Easy Borrowing</h3>
                <p>Members can browse, search, and borrow available books with just a few clicks.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🔔</div>
                <h3>Overdue Tracking</h3>
                <p>Automatic overdue detection with account hold system to manage late returns.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🛠️</div>
                <h3>Maintenance Logs</h3>
                <p>Track damaged books and log maintenance status from pending to resolved.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📊</div>
                <h3>Librarian Dashboard</h3>
                <p>Full control over books, members, transactions, and overdue records.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📋</div>
                <h3>Borrow History</h3>
                <p>Members can view their complete borrowing history with status tracking.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🔒</div>
                <h3>Role-Based Access</h3>
                <p>Separate portals for Members and Librarians with secure login system.</p>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section id="about" class="about-section">
        <h2>About BookShare</h2>
        <p>
            BookShare is a web-based Library Management System built to simplify the borrowing and 
            returning of library books. It features a dual-role system — <strong>Members</strong> 
            can browse and borrow books, while <strong>Librarians</strong> have full control over 
            the library's inventory, transactions, overdue tracking, and maintenance records.
        </p>
    </section>

    <!-- CONTACT -->
    <section id="contact" class="contact-section">
        <h2>Need Help?</h2>
        <p>Contact your librarian for account setup and assistance.</p>
    </section>

    <!-- FOOTER -->
    <footer>
        &copy; <?= date('Y') ?> BookShare Library Management System. All rights reserved.
    </footer>

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="modal" <?= $error ? 'style="display:block;"' : '' ?>>
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'">&times;</span>
            <h2>Welcome Back</h2>
            <p>Login to your BookShare account</p>

            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login" class="btn-submit">Login</button>
            </form>
            <div class="modal-footer">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>