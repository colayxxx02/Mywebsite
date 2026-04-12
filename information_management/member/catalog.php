<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// CHECK KUNG ON HOLD ANG ACCOUNT
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

// BORROW BOOK
if (isset($_POST['borrow_book']) && !$on_hold) {
    $transaction_id = $_POST['transaction_id'];
    $book_id = $_POST['book_id'];
    $borrow_date = date('Y-m-d');
    $due_date = $_POST['due_date'];

    $conn->query("INSERT INTO transactions (transaction_id, user_id, book_id, borrow_date, due_date) 
                  VALUES ('$transaction_id', '$user_id', '$book_id', '$borrow_date', '$due_date')");
    $conn->query("UPDATE books SET status='borrowed' WHERE book_id='$book_id'");
}

// SEARCH & FILTER
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';

$query = "SELECT * FROM books WHERE status='available'";
if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
}
if (!empty($filter_category)) {
    $query .= " AND category LIKE '%$filter_category%'";
}

$books = $conn->query($query);

$last = $conn->query("SELECT MAX(transaction_id) as max_id FROM transactions")->fetch_assoc();
$next_transaction_id = ($last['max_id'] ?? 0) + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Books - BookShare</title>
    <link rel="stylesheet" href="../member/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="catalog.php" class="active">Browse Books</a>
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
        <div class="page-title">Browse Books</div>
        <div class="page-subtitle">Search and borrow available books from the library.</div>

        <!-- ON HOLD NOTICE -->
        <?php if ($on_hold): ?>
            <div class="on-hold-notice">
                Your account is currently <strong>On Hold</strong> until <strong><?= $hold_until ?></strong>.
                You cannot borrow books during this period.
            </div>
        <?php endif; ?>

        <!-- SEARCH & FILTER -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search by title or author..." value="<?= htmlspecialchars($search) ?>">

            <select name="filter_category">
                <option value="">-- All Categories --</option>
                <?php
                $categories = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != ''");
                while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['category'] ?>" <?= $filter_category == $cat['category'] ? 'selected' : '' ?>>
                        <?= $cat['category'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit" class="btn-search">Search</button>
            <a href="catalog.php"><button type="button" class="btn-reset">Reset</button></a>
        </form>

        <p class="total-results">Available Books: <strong><?= $books->num_rows ?></strong></p>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($books->num_rows == 0): ?>
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <p>No available books found.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $books->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['book_id'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['author'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td>
                            <?php if (!$on_hold): ?>
                                <button class="btn-borrow" onclick="openBorrow(
                                    '<?= $row['book_id'] ?>',
                                    '<?= addslashes($row['title']) ?>'
                                )">Borrow</button>
                            <?php else: ?>
                                <span class="badge badge-hold">On Hold</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- BORROW MODAL -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('borrowModal').style.display='none'">&times;</button>
            <h3>Borrow Book</h3>
            <p style="margin-bottom:15px;">Book: <strong><span id="borrow_title"></span></strong></p>
            <form method="POST">
                <input type="hidden" name="book_id" id="borrow_book_id">
                <input type="hidden" name="transaction_id" value="<?= $next_transaction_id ?>">
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <button type="submit" name="borrow_book" class="btn-save">Confirm Borrow</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('borrowModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openBorrow(book_id, title) {
            document.getElementById('borrow_book_id').value = book_id;
            document.getElementById('borrow_title').innerText = title;
            document.getElementById('borrowModal').style.display = 'block';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>