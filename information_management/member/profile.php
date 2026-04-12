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
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="catalog.php">Browse Books</a>
            <a href="history.php">My History</a>
            <a href="profile.php" class="active">My Profile</a>
        </nav>
        <div class="user-info">
            Welcome, <span><?= $_SESSION['fullname'] ?></span>
            <form method="POST" action="../logout.php" style="display:inline;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="page-title">My Profile</div>
        <div class="page-subtitle">Manage your account information and password.</div>

        <!-- ALERTS -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <!-- ACCOUNT STATUS -->
        <?php if ($user['is_on_hold'] == 1): ?>
            <div class="on-hold-notice">
                Account Status: <strong>On Hold</strong> until <strong><?= $user['hold_until'] ?></strong>
            </div>
        <?php else: ?>
            <div class="active-notice">
                Account Status: <strong>Active</strong>
            </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_borrowed ?></div>
                    <div class="stat-label">Active Borrowed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_returned ?></div>
                    <div class="stat-label">Returned</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color:#c62828;"><?= $total_overdue ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>

        <!-- PROFILE INFO -->
        <div class="profile-box">
            <div class="profile-row">
                <span class="profile-label">User ID</span>
                <span class="profile-value"><?= $user['user_id'] ?></span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Full Name</span>
                <span class="profile-value"><?= $user['fullname'] ?></span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Email</span>
                <span class="profile-value"><?= $user['email'] ?></span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Role</span>
                <span class="profile-value"><?= ucfirst($user['role']) ?></span>
            </div>
            <div class="profile-row">
                <span class="profile-label">Member Since</span>
                <span class="profile-value"><?= $user['created_at'] ?></span>
            </div>
        </div>

        <button class="btn-edit-profile" onclick="document.getElementById('editModal').style.display='block'">Edit Profile</button>
        <button class="btn-change-pass" onclick="document.getElementById('passwordModal').style.display='block'">Change Password</button>
    </div>

    <!-- EDIT PROFILE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
            <h3>Edit Profile</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= $user['fullname'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $user['email'] ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
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
                <button type="submit" name="change_password" class="btn-save">Change Password</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('passwordModal').style.display='none'">Cancel</button>
            </form>
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