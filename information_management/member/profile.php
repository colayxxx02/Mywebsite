<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// UPDATE PROFILE
if (isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];

    $check = $conn->query("SELECT * FROM users WHERE email='$email' AND user_id != '$user_id'");
    if ($check->num_rows > 0) {
        $error = "Email already used by another account!";
    } else {
        $conn->query("UPDATE users SET fullname='$fullname', email='$email' WHERE user_id='$user_id'");
        $_SESSION['fullname'] = $fullname;
        $success = "Profile updated successfully!";
    }
}

// CHANGE PASSWORD
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $user = $conn->query("SELECT * FROM users WHERE user_id='$user_id'")->fetch_assoc();

    if (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new != $confirm) {
        $error = "New passwords do not match!";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE user_id='$user_id'");
        $success = "Password changed successfully!";
    }
}

// GET USER INFO
$user = $conn->query("SELECT * FROM users WHERE user_id='$user_id'")->fetch_assoc();

// COUNTS
$total_borrowed = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NULL AND due_date >= CURDATE()")->fetch_assoc()['count'];
$total_returned = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NOT NULL")->fetch_assoc()['count'];
$total_overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - BookShare</title>
    <link rel="stylesheet" href="../member/style.css">
</head>
<body class="dashboard-page">

    <!-- TITLEBAR -->
    <div class="titlebar">
        <div class="dots">
            <span class="dot-red"></span>
            <span class="dot-yellow"></span>
            <span class="dot-green"></span>
        </div>
        <div class="title">📚 BookShare - Member Dashboard</div>
    </div>

    <!-- APP SHELL WITH SIDEBAR -->
    <div class="app-shell">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="logo">📚 BookShare</div>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <span class="nav-icon">📊</span>
                    Dashboard
                </a>
                <a href="catalog.php" class="nav-link">
                    <span class="nav-icon">📖</span>
                    Browse Books
                </a>
                <a href="history.php" class="nav-link">
                    <span class="nav-icon">📜</span>
                    My History
                </a>
                <a href="profile.php" class="nav-link active">
                    <span class="nav-icon">👤</span>
                    My Profile
                </a>
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="../logout.php">
                    <button type="submit">🚪 Logout</button>
                </form>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="page-header">
                <div>
                    <div class="page-title">My Profile</div>
                    <div class="page-subtitle">Manage your account information and password.</div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT STATUS -->
            <div class="profile-card">
                <div class="profile-row">
                    <span class="profile-label">Account Status</span>
                    <span>
                        <?php if ($user['is_on_hold'] == 1): ?>
                            <span class="status-badge status-onhold">
                                🔴 On Hold until <?= $user['hold_until'] ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-active">
                                🟢 Active
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="dash-stat-grid">
                <div class="dash-stat-card">
                    <div class="stat-icon">📖</div>
                    <div>
                        <div class="stat-number"><?= $total_borrowed ?></div>
                        <div class="stat-label">Active Borrowed</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">✅</div>
                    <div>
                        <div class="stat-number"><?= $total_returned ?></div>
                        <div class="stat-label">Returned</div>
                    </div>
                </div>
                <div class="dash-stat-card overdue-card" style="<?= $total_overdue > 0 ? '' : 'opacity:0.7;' ?>">
                    <div class="stat-icon">⚠️</div>
                    <div>
                        <div class="stat-number overdue-number"><?= $total_overdue ?></div>
                        <div class="stat-label" style="color:#c62828;">Overdue</div>
                    </div>
                </div>
            </div>

            <!-- PROFILE INFORMATION -->
            <div class="profile-card">
                <h3>Account Information</h3>
                <div class="profile-row">
                    <span class="profile-label">User ID</span>
                    <span class="profile-value"><?= $user['user_id'] ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Full Name</span>
                    <span class="profile-value"><?= $user['fullname'] ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Email Address</span>
                    <span class="profile-value"><?= $user['email'] ?></span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Role</span>
                    <span class="profile-value">
                        <span class="badge badge-active"><?= ucfirst($user['role']) ?></span>
                    </span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Member Since</span>
                    <span class="profile-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="btn-group">
                <button class="btn-primary" onclick="document.getElementById('editModal').style.display='flex'">✏️ Edit Profile</button>
                <button class="btn-secondary" onclick="document.getElementById('passwordModal').style.display='flex'">🔐 Change Password</button>
            </div>
        </div>
    </div>

    <!-- EDIT PROFILE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
            <h3>Edit Profile</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" name="update_profile" class="btn-primary" style="flex:1;">Save Changes</button>
                    <button type="button" class="btn-secondary" style="flex:1;" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CHANGE PASSWORD MODAL -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('passwordModal').style.display='none'">&times;</button>
            <h3>Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" name="change_password" class="btn-primary" style="flex:1;">Change Password</button>
                    <button type="button" class="btn-secondary" style="flex:1;" onclick="document.getElementById('passwordModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.onclick = function(e) {
            if (e.target.id === 'editModal' || e.target.id === 'passwordModal') {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>