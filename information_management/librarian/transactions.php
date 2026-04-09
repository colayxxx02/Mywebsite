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

    // Check kung on hold ang member
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

    // Kung overdue, auto-hold ang account
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

// GET TRANSACTIONS
$transactions = $conn->query("SELECT t.*, u.fullname, u.is_on_hold, u.hold_until, b.title 
    FROM transactions t 
    JOIN users u ON t.user_id = u.user_id 
    JOIN books b ON t.book_id = b.book_id 
    ORDER BY t.borrow_date DESC");

// GET AVAILABLE BOOKS
$available_books = $conn->query("SELECT * FROM books WHERE status='available'");

// GET MEMBERS
$members = $conn->query("SELECT * FROM users WHERE role='member'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - BookShare</title>
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
        .modal-content { background:#fff; margin:8% auto; padding:20px; width:420px; border-radius:8px; }
        .close { float:right; cursor:pointer; font-size:20px; }
        .overdue { color:red; font-weight:bold; }
        .active { color:green; }
        .returned { color:gray; }
    </style>
</head>
<body>
    <h2>Transactions</h2>
    <a href="dashboard.php">Back to Dashboard</a> |
    <a href="../logout.php">Logout</a>

    <br><br>
    <button onclick="document.getElementById('borrowModal').style.display='block'">+ Borrow Book</button>

    <?php if (isset($borrow_error)): ?>
        <p style="color:red;"><?= $borrow_error ?></p>
    <?php endif; ?>

    <br><br>
    <table border="1" cellpadding="8">
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
        <?php while ($row = $transactions->fetch_assoc()):
            $today = date('Y-m-d');
            $is_overdue = $row['return_date'] == null && $today > $row['due_date'];
            $is_returned = $row['return_date'] != null;
        ?>
        <tr>
            <td><?= $row['transaction_id'] ?></td>
            <td><?= $row['fullname'] ?></td>
            <td><?= $row['title'] ?></td>
            <td><?= $row['borrow_date'] ?></td>
            <td><?= $row['due_date'] ?></td>
            <td><?= $row['return_date'] ?? '—' ?></td>
            <td class="<?= $is_returned ? 'returned' : ($is_overdue ? 'overdue' : 'active') ?>">
                <?= $is_returned ? 'Returned' : ($is_overdue ? 'Overdue' : 'Active') ?>
            </td>
            <td>
                <?php if (!$is_returned): ?>
                    <button onclick="openReturn(
                        '<?= $row['transaction_id'] ?>',
                        '<?= $row['user_id'] ?>',
                        '<?= $row['book_id'] ?>',
                        '<?= $row['title'] ?>',
                        '<?= $row['fullname'] ?>'
                    )">Return</button>
                <?php endif; ?>

                <?php if ($is_overdue && $row['is_on_hold'] == 0): ?>
                    <button style="color:orange;" onclick="openHold(
                        '<?= $row['user_id'] ?>',
                        '<?= $row['fullname'] ?>'
                    )">Hold Account</button>
                <?php elseif ($row['is_on_hold'] == 1): ?>
                    <span style="color:red;">On Hold until <?= $row['hold_until'] ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- BORROW MODAL -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('borrowModal').style.display='none'">&times;</span>
            <h3>Borrow Book</h3>
            <form method="POST">
                Transaction ID: <input type="number" name="transaction_id" required><br><br>
                Member:
                <select name="user_id" required>
                    <option value="">-- Select Member --</option>
                    <?php while ($m = $members->fetch_assoc()): ?>
                        <option value="<?= $m['user_id'] ?>"><?= $m['fullname'] ?></option>
                    <?php endwhile; ?>
                </select><br><br>
                Book:
                <select name="book_id" required>
                    <option value="">-- Select Book --</option>
                    <?php while ($b = $available_books->fetch_assoc()): ?>
                        <option value="<?= $b['book_id'] ?>"><?= $b['title'] ?></option>
                    <?php endwhile; ?>
                </select><br><br>
                Borrow Date: <input type="date" name="borrow_date" required><br><br>
                Due Date: <input type="date" name="due_date" required><br><br>
                <button type="submit" name="borrow_book">Confirm Borrow</button>
            </form>
        </div>
    </div>

    <!-- RETURN MODAL -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('returnModal').style.display='none'">&times;</span>
            <h3>Return Book</h3>
            <p>Member: <strong><span id="return_member"></span></strong></p>
            <p>Book: <strong><span id="return_book_title"></span></strong></p>
            <p>Return Date: <strong><?= date('Y-m-d') ?></strong></p>
            <form method="POST">
                <input type="hidden" name="transaction_id" id="return_transaction_id">
                <input type="hidden" name="user_id" id="return_user_id">
                <input type="hidden" name="book_id" id="return_book_id">
                <button type="submit" name="return_book">Confirm Return</button>
                <button type="button" onclick="document.getElementById('returnModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- HOLD MODAL -->
    <div id="holdModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('holdModal').style.display='none'">&times;</span>
            <h3>Hold Account</h3>
            <p>Are you sure you want to hold the account of <strong><span id="hold_member_name"></span></strong>?</p>
            <p>Account will be on hold for <strong>1 week</strong>.</p>
            <form method="POST">
                <input type="hidden" name="user_id" id="hold_user_id">
                <button type="submit" name="hold_account" style="color:red;">Confirm Hold</button>
                <button type="button" onclick="document.getElementById('holdModal').style.display='none'">Cancel</button>
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