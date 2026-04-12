<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

// ADD BOOK
if (isset($_POST['add_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $status = $_POST['status'];
    $conn->query("INSERT INTO books (book_id, title, author, category, status) 
                  VALUES ('$book_id', '$title', '$author', '$category', '$status')");
}

// UPDATE BOOK
if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $status = $_POST['status'];
    $conn->query("UPDATE books SET title='$title', author='$author', 
                  category='$category', status='$status' WHERE book_id='$book_id'");
}

// DELETE BOOK
if (isset($_POST['delete_book'])) {
    $book_id = $_POST['book_id'];
    $conn->query("DELETE FROM books WHERE book_id='$book_id'");
}

// SEARCH & FILTER
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_category = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';

$query = "SELECT * FROM books WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
}
if (!empty($filter_status)) {
    $query .= " AND status = '$filter_status'";
}
if (!empty($filter_category)) {
    $query .= " AND category LIKE '%$filter_category%'";
}

$books = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books - BookShare</title>
    <link rel="stylesheet" href="../librarian/style.css">
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="logo">📚 BookShare</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php" class="active">Books</a>
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
        <div class="page-title">Manage Books</div>
        <div class="page-subtitle">Add, edit, or remove books from the library collection.</div>

        <button class="btn-add" onclick="document.getElementById('addModal').style.display='block'">+ Add Book</button>

        <!-- SEARCH & FILTER -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search by title or author..." value="<?= htmlspecialchars($search) ?>">

            <select name="filter_status">
                <option value="">-- All Status --</option>
                <option value="available" <?= $filter_status == 'available' ? 'selected' : '' ?>>Available</option>
                <option value="borrowed" <?= $filter_status == 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                <option value="damaged" <?= $filter_status == 'damaged' ? 'selected' : '' ?>>Damaged</option>
            </select>

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
            <a href="books.php"><button type="button" class="btn-reset">Reset</button></a>
        </form>

        <p class="total-results">Total Results: <strong><?= $books->num_rows ?></strong></p>

        <!-- TABLE -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($books->num_rows == 0): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <p>No books found.</p>
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
                            <span class="badge badge-<?= $row['status'] ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-edit" onclick="openEdit(
                                '<?= $row['book_id'] ?>',
                                '<?= addslashes($row['title']) ?>',
                                '<?= addslashes($row['author']) ?>',
                                '<?= addslashes($row['category']) ?>',
                                '<?= $row['status'] ?>'
                            )">Edit</button>
                            <button class="btn-delete" onclick="openDelete('<?= $row['book_id'] ?>', '<?= addslashes($row['title']) ?>')">Delete</button>
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
            <h3>Add Book</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Book ID</label>
                    <input type="number" name="book_id" required>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <button type="submit" name="add_book" class="btn-save">Save Book</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
            <h3>Edit Book</h3>
            <form method="POST">
                <input type="hidden" name="book_id" id="edit_book_id">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" id="edit_author" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="edit_category">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
                <button type="submit" name="update_book" class="btn-save">Update Book</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="document.getElementById('deleteModal').style.display='none'">&times;</button>
            <h3>Delete Book</h3>
            <p class="delete-warning">Are you sure you want to delete "<span id="delete_title"></span>"?</p>
            <form method="POST">
                <input type="hidden" name="book_id" id="delete_book_id">
                <button type="submit" name="delete_book" class="btn-save" style="background:#c62828;">Confirm Delete</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openEdit(id, title, author, category, status) {
            document.getElementById('edit_book_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_author').value = author;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'block';
        }

        function openDelete(id, title) {
            document.getElementById('delete_book_id').value = id;
            document.getElementById('delete_title').innerText = title;
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