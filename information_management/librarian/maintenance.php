<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

// ADD LOG
if (isset($_POST['add_log'])) {
    $log_id = $_POST['log_id'];
    $book_id = $_POST['book_id'];
    $reported_by = $_POST['reported_by'];
    $issue = $_POST['issue'];
    $report_date = $_POST['report_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $conn->query("INSERT INTO maintenance_log (log_id, book_id, reported_by, issue, report_date, status, notes) 
                  VALUES ('$log_id', '$book_id', '$reported_by', '$issue', '$report_date', '$status', '$notes')");
    $conn->query("UPDATE books SET status='damaged', maintenance_tag='$issue' WHERE book_id='$book_id'");
}

// UPDATE LOG
if (isset($_POST['update_log'])) {
    $log_id = $_POST['log_id'];
    $issue = $_POST['issue'];
    $status = $_POST['status'];
    $resolved_date = $_POST['resolved_date'];
    $notes = $_POST['notes'];

    $conn->query("UPDATE maintenance_log SET issue='$issue', status='$status', 
                  resolved_date='$resolved_date', notes='$notes' WHERE log_id='$log_id'");

    if ($status == 'resolved') {
        $log = $conn->query("SELECT book_id FROM maintenance_log WHERE log_id='$log_id'")->fetch_assoc();
        $conn->query("UPDATE books SET status='available', maintenance_tag=NULL WHERE book_id='{$log['book_id']}'");
    }
}

// DELETE LOG
if (isset($_POST['delete_log'])) {
    $log_id = $_POST['log_id'];
    $conn->query("DELETE FROM maintenance_log WHERE log_id='$log_id'");
}

$logs = $conn->query("SELECT m.*, b.title, u.fullname 
    FROM maintenance_log m 
    JOIN books b ON m.book_id = b.book_id 
    JOIN users u ON m.reported_by = u.user_id 
    ORDER BY m.report_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Log - BookShare</title>
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
            <a href="overdue.php">Overdue</a>
            <a href="maintenance.php" class="active">Maintenance</a>
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
        <div class="page-title">Maintenance Log</div>
        <div class="page-subtitle">Track and manage damaged or under-repair books.</div>

        <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add Maintenance Log</button>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Book</th>
                        <th>Reported By</th>
                        <th>Issue</th>
                        <th>Report Date</th>
                        <th>Resolved Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs->num_rows == 0): ?>
                        <tr><td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">🛠️</div>
                                <p>No maintenance logs found.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                    <?php while ($row = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['log_id'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><?= $row['fullname'] ?></td>
                        <td><?= $row['issue'] ?></td>
                        <td><?= $row['report_date'] ?></td>
                        <td><?= $row['resolved_date'] ?? '—' ?></td>
                        <td>
                            <span class="badge badge-<?= $row['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                            </span>
                        </td>
                        <td><?= $row['notes'] ?? '—' ?></td>
                        <td>
                            <button class="btn-edit" onclick="openEdit(
                                '<?= $row['log_id'] ?>',
                                '<?= addslashes($row['issue']) ?>',
                                '<?= $row['status'] ?>',
                                '<?= $row['resolved_date'] ?>',
                                '<?= addslashes($row['notes']) ?>'
                            )">Edit</button>
                            <button class="btn-delete" onclick="openDelete('<?= $row['log_id'] ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
            <h3>Add Maintenance Log</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Log ID</label>
                    <input type="number" name="log_id" required>
                </div>
                <div class="form-group">
                    <label>Book</label>
                    <select name="book_id" required>
                        <option value="">-- Select Book --</option>
                        <?php 
                        $books_copy = $conn->query("SELECT * FROM books");
                        while ($b = $books_copy->fetch_assoc()): ?>
                            <option value="<?= $b['book_id'] ?>"><?= $b['title'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reported By</label>
                    <select name="reported_by" required>
                        <option value="">-- Select User --</option>
                        <?php 
                        $users_copy = $conn->query("SELECT * FROM users");
                        while ($u = $users_copy->fetch_assoc()): ?>
                            <option value="<?= $u['user_id'] ?>"><?= $u['fullname'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Issue</label>
                    <input type="text" name="issue" required>
                </div>
                <div class="form-group">
                    <label>Report Date</label>
                    <input type="date" name="report_date" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes"></textarea>
                </div>
                <button type="submit" name="add_log" class="btn-save">Save Log</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
            <h3>Edit Maintenance Log</h3>
            <form method="POST">
                <input type="hidden" name="log_id" id="edit_log_id">
                <div class="form-group">
                    <label>Issue</label>
                    <input type="text" name="issue" id="edit_issue" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Resolved Date</label>
                    <input type="date" name="resolved_date" id="edit_resolved_date">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="edit_notes"></textarea>
                </div>
                <button type="submit" name="update_log" class="btn-save">Update Log</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('deleteModal').style.display='none'">&times;</button>
            <h3>Delete Log</h3>
            <p class="delete-warning">Are you sure you want to delete this maintenance log?</p>
            <form method="POST">
                <input type="hidden" name="log_id" id="delete_log_id">
                <button type="submit" name="delete_log" class="btn-save" style="background:#c62828;">Confirm Delete</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openEdit(log_id, issue, status, resolved_date, notes) {
            document.getElementById('edit_log_id').value = log_id;
            document.getElementById('edit_issue').value = issue;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_resolved_date').value = resolved_date;
            document.getElementById('edit_notes').value = notes;
            document.getElementById('editModal').style.display = 'block';
        }

        function openDelete(log_id) {
            document.getElementById('delete_log_id').value = log_id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>