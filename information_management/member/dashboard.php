<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// CHECK ON HOLD
$user = $conn->query("SELECT * FROM users WHERE user_id='$user_id'")->fetch_assoc();
if ($user['is_on_hold'] == 1 && strtotime(date('Y-m-d')) <= strtotime($user['hold_until'])) {
    $on_hold = true;
    $hold_until = $user['hold_until'];
} else {
    $on_hold = false;
    if ($user['is_on_hold'] == 1) {
        $conn->query("UPDATE users SET is_on_hold=0, hold_until=NULL WHERE user_id='$user_id'");
    }
}

// BORROWED BOOKS
$borrowed = $conn->query("SELECT t.*, b.title, b.author 
    FROM transactions t 
    JOIN books b ON t.book_id = b.book_id 
    WHERE t.user_id = '$user_id' AND t.return_date IS NULL");

// COUNTS
$total_borrowed = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NULL")->fetch_assoc()['count'];
$total_returned = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NOT NULL")->fetch_assoc()['count'];
$total_overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND due_date < CURDATE() AND return_date IS NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Dashboard - BookShare</title>
    <link rel="stylesheet" href="../member/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="catalog.php">Browse Books</a>
            <a href="history.php">My History</a>
            <a href="profile.php">My Profile</a>
        </nav>
        <div class="user-info">
            Welcome, <span><?= $_SESSION['fullname'] ?></span>
            <form method="POST" action="../logout.php" style="display:inline;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Welcome back, <?= $_SESSION['fullname'] ?>!</div>

        <!-- ON HOLD / ACTIVE NOTICE -->
        <?php if ($on_hold): ?>
            <div class="on-hold-notice">
                Your account is currently <strong>On Hold</strong> until <strong><?= $hold_until ?></strong>.
                You cannot borrow books during this period.
            </div>
        <?php else: ?>
            <div class="active-notice">
                Account Status: <strong>Active</strong> — You can borrow books freely.
            </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_borrowed ?></div>
                    <div class="stat-label">Currently Borrowed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_returned ?></div>
                    <div class="stat-label">Total Returned</div>
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

        <!-- CURRENTLY BORROWED TABLE -->
        <div class="page-title" style="font-size:18px; margin-bottom:15px;">Currently Borrowed Books</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($borrowed->num_rows == 0): ?>
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">📚</div>
                                <p>No currently borrowed books.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $borrowed->fetch_assoc()):
                        $is_overdue = strtotime($row['due_date']) < time();
                    ?>
                    <tr>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['author'] ?></td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['due_date'] ?></td>
                        <td>
                            <span class="badge <?= $is_overdue ? 'badge-overdue' : 'badge-active' ?>">
                                <?= $is_overdue ? 'Overdue' : 'Active' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- QUICK NAV -->
        <div class="page-title" style="font-size:18px; margin:25px 0 15px;">Quick Navigation</div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:15px;">
            <a href="catalog.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">📖</div>
                    <div style="font-weight:600; color:#1a1a2e;">Browse Books</div>
                </div>
            </a>
            <a href="history.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">📋</div>
                    <div style="font-weight:600; color:#1a1a2e;">My History</div>
                </div>
            </a>
            <a href="profile.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">👤</div>
                    <div style="font-weight:600; color:#1a1a2e;">My Profile</div>
                </div>
            </a>
        </div>
    </div>

</body>
</html>