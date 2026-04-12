<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// SEARCH & FILTER
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

$query = "SELECT t.*, b.title, b.author, b.category 
          FROM transactions t 
          JOIN books b ON t.book_id = b.book_id 
          WHERE t.user_id = '$user_id'";

if (!empty($search)) {
    $query .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
}
if ($filter_status == 'active') {
    $query .= " AND t.return_date IS NULL AND t.due_date >= CURDATE()";
} elseif ($filter_status == 'returned') {
    $query .= " AND t.return_date IS NOT NULL";
} elseif ($filter_status == 'overdue') {
    $query .= " AND t.return_date IS NULL AND t.due_date < CURDATE()";
}

$query .= " ORDER BY t.borrow_date DESC";
$history = $conn->query($query);

// COUNT
$total = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id'")->fetch_assoc()['count'];
$active = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NULL AND due_date >= CURDATE()")->fetch_assoc()['count'];
$returned = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NOT NULL")->fetch_assoc()['count'];
$overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id='$user_id' AND return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrowing History - BookShare</title>
    <link rel="stylesheet" href="../member/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="catalog.php">Browse Books</a>
            <a href="history.php" class="active">My History</a>
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
        <div class="page-title">Borrowing History</div>
        <div class="page-subtitle">View all your past and current borrowed books.</div>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $active ?></div>
                    <div class="stat-label">Active</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?= $returned ?></div>
                    <div class="stat-label">Returned</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <div class="stat-number" style="color:#c62828;"><?= $overdue ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>

        <!-- SEARCH & FILTER -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search by title or author..." value="<?= htmlspecialchars($search) ?>">

            <select name="filter_status">
                <option value="">-- All Status --</option>
                <option value="active" <?= $filter_status == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="returned" <?= $filter_status == 'returned' ? 'selected' : '' ?>>Returned</option>
                <option value="overdue" <?= $filter_status == 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>

            <button type="submit" class="btn-search">Search</button>
            <a href="history.php"><button type="button" class="btn-reset">Reset</button></a>
        </form>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history->num_rows == 0): ?>
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <p>No records found.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $history->fetch_assoc()):
                        $today = date('Y-m-d');
                        $is_returned = $row['return_date'] != null;
                        $is_overdue = !$is_returned && $today > $row['due_date'];
                        $status_class = $is_returned ? 'returned' : ($is_overdue ? 'overdue' : 'active');
                        $status_text = $is_returned ? 'Returned' : ($is_overdue ? 'Overdue' : 'Active');
                    ?>
                    <tr>
                        <td><?= $row['transaction_id'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['author'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['due_date'] ?></td>
                        <td><?= $row['return_date'] ?? '—' ?></td>
                        <td>
                            <span class="badge badge-<?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>