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

$books = $conn->query("SELECT * FROM books");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books - BookShare</title>
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
        .modal-content { background:#fff; margin:10% auto; padding:20px; width:400px; border-radius:8px; }
        .close { float:right; cursor:pointer; font-size:20px; }
    </style>
</head>
<body>
    <h2>Manage Books</h2>
    <a href="dashboard.php">Back to Dashboard</a> | 
    <a href="../logout.php">Logout</a>

    <br><br>
    <button onclick="document.getElementById('addModal').style.display='block'">+ Add Book</button>

    <br><br>
    <table border="1" cellpadding="8">
        <tr>
            <th>Book ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $books->fetch_assoc()): ?>
        <tr>
            <td><?= $row['book_id'] ?></td>
            <td><?= $row['title'] ?></td>
            <td><?= $row['author'] ?></td>
            <td><?= $row['category'] ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <button onclick="openEdit(
                    '<?= $row['book_id'] ?>',
                    '<?= $row['title'] ?>',
                    '<?= $row['author'] ?>',
                    '<?= $row['category'] ?>',
                    '<?= $row['status'] ?>'
                )">Edit</button>
                <button onclick="openDelete('<?= $row['book_id'] ?>', '<?= $row['title'] ?>')">Delete</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h3>Add Book</h3>
            <form method="POST">
                Book ID: <input type="number" name="book_id" required><br><br>
                Title: <input type="text" name="title" required><br><br>
                Author: <input type="text" name="author" required><br><br>
                Category: <input type="text" name="category"><br><br>
                Status:
                <select name="status">
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="damaged">Damaged</option>
                </select><br><br>
                <button type="submit" name="add_book">Save</button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h3>Edit Book</h3>
            <form method="POST">
                <input type="hidden" name="book_id" id="edit_book_id">
                Title: <input type="text" name="title" id="edit_title" required><br><br>
                Author: <input type="text" name="author" id="edit_author" required><br><br>
                Category: <input type="text" name="category" id="edit_category"><br><br>
                Status:
                <select name="status" id="edit_status">
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="damaged">Damaged</option>
                </select><br><br>
                <button type="submit" name="update_book">Update</button>
            </form>
        </div>
    </div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
            <h3>Delete Book</h3>
            <p>Are you sure you want to delete "<span id="delete_title"></span>"?</p>
            <form method="POST">
                <input type="hidden" name="book_id" id="delete_book_id">
                <button type="submit" name="delete_book" style="color:red;">Delete</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
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

        // Close modal kung i-click ang gawas
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>