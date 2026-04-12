<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$available = $conn->query("SELECT COUNT(*) as count FROM books WHERE status='available'")->fetch_assoc()['count'];
$borrowed = $conn->query("SELECT COUNT(*) as count FROM books WHERE status='borrowed'")->fetch_assoc()['count'];
$damaged = $conn->query("SELECT COUNT(*) as count FROM books WHERE status='damaged'")->fetch_assoc()['count'];
$overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE due_date < CURDATE() AND return_date IS NULL")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='member'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Dashboard - BookShare</title>
    <link rel="stylesheet" href="../librarian/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="transactions.php">Transactions</a>
            <a href="overdue.php">Overdue</a>
            <a href="maintenance.php">Maintenance</a>
        </nav>
        <div class="user-info">
            Welcome, <span><?= $_SESSION['fullname'] ?></span>
            <form method="POST" action="../logout.php" style="display:inline;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="page-wrapper">
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Library overview and quick statistics.</div>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_books ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $available ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $borrowed ?></div>
                    <div class="stat-label">Borrowed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🛠️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $damaged ?></div>
                    <div class="stat-label">Damaged</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color:#c62828;"><?= $overdue ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total_members ?></div>
                    <div class="stat-label">Total Members</div>
                </div>
            </div>
        </div>

        <!-- QUICK LINKS -->
        <div class="page-title" style="font-size:18px; margin-bottom:15px;">Quick Navigation</div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:15px;">
            <a href="books.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">📚</div>
                    <div style="font-weight:600; color:#1a1a2e;">Manage Books</div>
                </div>
            </a>
            <a href="transactions.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">🔄</div>
                    <div style="font-weight:600; color:#1a1a2e;">Transactions</div>
                </div>
            </a>
            <a href="overdue.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">⚠️</div>
                    <div style="font-weight:600; color:#1a1a2e;">Overdue Books</div>
                </div>
            </a>
            <a href="maintenance.php" style="text-decoration:none;">
                <div class="stat-card" style="cursor:pointer; flex-direction:column; text-align:center; gap:8px;">
                    <div style="font-size:28px;">🛠️</div>
                    <div style="font-weight:600; color:#1a1a2e;">Maintenance Log</div>
                </div>
            </a>
        </div>
    </div>

</body>
</html>