<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

// BORROW BOOK
if (isset($_POST['borrow_book'])) {
    $transaction_id = $_POST['transaction_id'];
    $user_id = $_POST['user_id'];
    $book_id = $_POST['book_id'];
    $borrow_date = $_POST['borrow_date'];
    $due_date = $_POST['due_date'];

    $user = $conn->query("SELECT * FROM users WHERE user_id='$user_id'")->fetch_assoc();
    if ($user['is_on_hold'] == 1 && strtotime(date('Y-m-d')) <= strtotime($user['hold_until'])) {
        $borrow_error = "Account is on hold until " . $user['hold_until'];
    } else {
        $conn->query("INSERT INTO transactions (transaction_id, user_id, book_id, borrow_date, due_date) 
                      VALUES ('$transaction_id', '$user_id', '$book_id', '$borrow_date', '$due_date')");
        $conn->query("UPDATE books SET status='borrowed' WHERE book_id='$book_id'");
    }
}

// RETURN BOOK
if (isset($_POST['return_book'])) {
    $transaction_id = $_POST['transaction_id'];
    $user_id = $_POST['user_id'];
    $book_id = $_POST['book_id'];
    $return_date = date('Y-m-d');

    $trans = $conn->query("SELECT * FROM transactions WHERE transaction_id='$transaction_id'")->fetch_assoc();
    $conn->query("UPDATE transactions SET return_date='$return_date' WHERE transaction_id='$transaction_id'");
    $conn->query("UPDATE books SET status='available' WHERE book_id='$book_id'");

    if (strtotime($return_date) > strtotime($trans['due_date'])) {
        $hold_until = date('Y-m-d', strtotime('+1 week'));
        $conn->query("UPDATE users SET is_on_hold=1, hold_until='$hold_until' WHERE user_id='$user_id'");
    }
}

// MANUAL HOLD
if (isset($_POST['hold_account'])) {
    $user_id = $_POST['user_id'];
    $hold_until = date('Y-m-d', strtotime('+1 week'));
    $conn->query("UPDATE users SET is_on_hold=1, hold_until='$hold_until' WHERE user_id='$user_id'");
}

$transactions = $conn->query("SELECT t.*, u.fullname, u.user_id as uid, u.is_on_hold, u.hold_until, b.title 
    FROM transactions t 
    JOIN users u ON t.user_id = u.user_id 
    JOIN books b ON t.book_id = b.book_id 
    ORDER BY t.borrow_date DESC");

$available_books = $conn->query("SELECT * FROM books WHERE status='available'");
$members = $conn->query("SELECT * FROM users WHERE role='member'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - BookShare</title>
    <link rel="stylesheet" href="../librarian/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="transactions.php" class="active">Transactions</a>
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

    <div class="page-wrapper">
        <div class="page-title">Transactions</div>
        <div class="page-subtitle">Manage book borrowing and return records.</div>

        <?php if (isset($borrow_error)): ?>
            <div class="alert alert-error"><?= $borrow_error ?></div>
        <?php endif; ?>

        <button class="btn-add" onclick="document.getElementById('borrowModal').style.display='block'">+ Borrow Book</button>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions->num_rows == 0): ?>
                        <tr><td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <p>No transactions found.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $transactions->fetch_assoc()):
                        $today = date('Y-m-d');
                        $is_returned = $row['return_date'] != null;
                        $is_overdue = !$is_returned && $today > $row['due_date'];
                        $status_class = $is_returned ? 'returned' : ($is_overdue ? 'overdue' : 'active');
                        $status_text = $is_returned ? 'Returned' : ($is_overdue ? 'Overdue' : 'Active');
                    ?>
                    <tr>
                        <td><?= $row['transaction_id'] ?></td>
                        <td><?= $row['fullname'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['borrow_date'] ?></td>
                        <td><?= $row['due_date'] ?></td>
                        <td><?= $row['return_date'] ?? '—' ?></td>
                        <td><span class="badge badge-<?= $status_class ?>"><?= $status_text ?></span></td>
                        <td>
                            <?php if (!$is_returned): ?>
                                <button class="btn-edit" onclick="openReturn(
                                    '<?= $row['transaction_id'] ?>',
                                    '<?= $row['user_id'] ?>',
                                    '<?= $row['book_id'] ?>',
                                    '<?= addslashes($row['title']) ?>',
                                    '<?= addslashes($row['fullname']) ?>'
                                )">Return</button>
                            <?php endif; ?>

                            <?php if ($is_overdue && $row['is_on_hold'] == 0): ?>
                                <button class="btn-action" onclick="openHold(
                                    '<?= $row['user_id'] ?>',
                                    '<?= addslashes($row['fullname']) ?>'
                                )">Hold Account</button>
                            <?php elseif ($row['is_on_hold'] == 1): ?>
                                <span class="badge badge-hold">On Hold until <?= $row['hold_until'] ?></span>
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
            <form method="POST">
                <div class="form-group">
                    <label>Transaction ID</label>
                    <input type="number" name="transaction_id" required>
                </div>
                <div class="form-group">
                    <label>Member</label>
                    <select name="user_id" required>
                        <option value="">-- Select Member --</option>
                        <?php while ($m = $members->fetch_assoc()): ?>
                            <option value="<?= $m['user_id'] ?>"><?= $m['fullname'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Book</label>
                    <select name="book_id" required>
                        <option value="">-- Select Book --</option>
                        <?php while ($b = $available_books->fetch_assoc()): ?>
                            <option value="<?= $b['book_id'] ?>"><?= $b['title'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Borrow Date</label>
                    <input type="date" name="borrow_date" required>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" required>
                </div>
                <button type="submit" name="borrow_book" class="btn-save">Confirm Borrow</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('borrowModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- RETURN MODAL -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('returnModal').style.display='none'">&times;</button>
            <h3>Return Book</h3>
            <p style="margin-bottom:8px;">Member: <strong><span id="return_member"></span></strong></p>
            <p style="margin-bottom:8px;">Book: <strong><span id="return_book_title"></span></strong></p>
            <p style="margin-bottom:20px;">Return Date: <strong><?= date('Y-m-d') ?></strong></p>
            <form method="POST">
                <input type="hidden" name="transaction_id" id="return_transaction_id">
                <input type="hidden" name="user_id" id="return_user_id">
                <input type="hidden" name="book_id" id="return_book_id">
                <button type="submit" name="return_book" class="btn-save">Confirm Return</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('returnModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- HOLD MODAL -->
    <div id="holdModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('holdModal').style.display='none'">&times;</button>
            <h3>Hold Account</h3>
            <p class="delete-warning">Are you sure you want to hold the account of <strong><span id="hold_member_name"></span></strong>?</p>
            <p style="color:#888; font-size:14px; margin-bottom:20px;">Account will be on hold for <strong>1 week</strong>.</p>
            <form method="POST">
                <input type="hidden" name="user_id" id="hold_user_id">
                <button type="submit" name="hold_account" class="btn-save" style="background:#c62828;">Confirm Hold</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('holdModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openReturn(transaction_id, user_id, book_id, title, member) {
            document.getElementById('return_transaction_id').value = transaction_id;
            document.getElementById('return_user_id').value = user_id;
            document.getElementById('return_book_id').value = book_id;
            document.getElementById('return_book_title').innerText = title;
            document.getElementById('return_member').innerText = member;
            document.getElementById('returnModal').style.display = 'block';
        }

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