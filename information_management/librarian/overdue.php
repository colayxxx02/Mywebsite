<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

// HOLD ACCOUNT
if (isset($_POST['hold_account'])) {
    $user_id = $_POST['user_id'];
    $hold_until = date('Y-m-d', strtotime('+7 days'));
    $conn->query("UPDATE users SET is_on_hold=1, hold_until='$hold_until' WHERE user_id='$user_id'");
}

$overdue = $conn->query("SELECT t.*, u.fullname, u.user_id, u.is_on_hold, u.hold_until, b.title 
    FROM transactions t 
    JOIN users u ON t.user_id = u.user_id 
    JOIN books b ON t.book_id = b.book_id 
    WHERE t.return_date IS NULL AND t.due_date < CURDATE()
    ORDER BY t.due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overdue Books - BookShare</title>
    <link rel="stylesheet" href="../librarian/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="transactions.php">Transactions</a>
            <a href="overdue.php" class="active">Overdue</a>
            <a href="maintenance.php">Maintenance</a>
        </nav>
        <div class="user-info">
            Welcome, <span><?= $_SESSION['fullname'] ?></span>
            <form method="POST" action="../logout.php" style="display:inline;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="page-title">Overdue Books</div>
        <div class="page-subtitle">Members with unreturned books past their due date.</div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Account Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($overdue->num_rows == 0): ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon">✅</div>
                                <p>No overdue books!</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $overdue->fetch_assoc()):
                        $days_overdue = (strtotime(date('Y-m-d')) - strtotime($row['due_date'])) / (60 * 60 * 24);
                    ?>
                    <tr>
                        <td><?= $row['fullname'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><span class="badge badge-overdue"><?= $row['due_date'] ?></span></td>
                        <td><strong style="color:#c62828;"><?= (int)$days_overdue ?> days</strong></td>
                        <td>
                            <?php if ($row['is_on_hold'] == 1): ?>
                                <span class="badge badge-hold">On Hold until <?= $row['hold_until'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-available">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['is_on_hold'] == 0): ?>
                                <button class="btn-action" onclick="openHold(
                                    '<?= $row['user_id'] ?>',
                                    '<?= addslashes($row['fullname']) ?>'
                                )">Hold Account</button>
                            <?php else: ?>
                                <span style="color:#aaa; font-size:13px;">Already on hold</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- HOLD MODAL -->
    <div id="holdModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('holdModal').style.display='none'">&times;</button>
            <h3>Hold Account</h3>
            <p class="delete-warning">
                Are you sure you want to hold the account of <strong><span id="hold_member_name"></span></strong>?
            </p>
            <p style="color:#888; font-size:14px; margin-bottom:20px;">Account will be on hold for <strong>1 week</strong>.</p>
            <form method="POST">
                <input type="hidden" name="user_id" id="hold_user_id">
                <button type="submit" name="hold_account" class="btn-save" style="background:#c62828;">Confirm Hold</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('holdModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openHold(user_id, fullname) {
            document.getElementById('hold_user_id').value = user_id;
            document.getElementById('hold_member_name').innerText = fullname;
            document.getElementById('holdModal').style.display = 'block';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>