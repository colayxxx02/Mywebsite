<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Borrowed books sa member
$borrowed = $conn->query("SELECT t.*, b.title, b.author 
    FROM transactions t 
    JOIN books b ON t.book_id = b.book_id 
    WHERE t.user_id = '$user_id' AND t.return_date IS NULL");

// Overdue books sa member
$overdue = $conn->query("SELECT t.*, b.title 
    FROM transactions t 
    JOIN books b ON t.book_id = b.book_id 
    WHERE t.user_id = '$user_id' AND t.due_date < CURDATE() AND t.return_date IS NULL");

// Count
$total_borrowed = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id = '$user_id' AND return_date IS NULL")->fetch_assoc()['count'];
$total_returned = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id = '$user_id' AND return_date IS NOT NULL")->fetch_assoc()['count'];
$total_overdue = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE user_id = '$user_id' AND due_date < CURDATE() AND return_date IS NULL")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Dashboard - BookShare</title>
</head>
<body>
    <h2>Welcome, <?= $_SESSION['fullname'] ?>!</h2>
    <a href="../logout.php">Logout</a>

    <hr>
    <h3>My Status</h3>
    <p>Currently Borrowed: <strong><?= $total_borrowed ?></strong></p>
    <p>Total Returned: <strong><?= $total_returned ?></strong></p>
    <p>Overdue: <strong style="color:red;"><?= $total_overdue ?></strong></p>

    <hr>
    <h3>Currently Borrowed Books</h3>
    <?php if ($borrowed->num_rows > 0): ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
            <?php while ($row = $borrowed->fetch_assoc()): ?>
            <tr>
                <td><?= $row['title'] ?></td>
                <td><?= $row['author'] ?></td>
                <td><?= $row['borrow_date'] ?></td>
                <td><?= $row['due_date'] ?></td>
                <td style="color:<?= (strtotime($row['due_date']) < time()) ? 'red' : 'green' ?>">
                    <?= (strtotime($row['due_date']) < time()) ? 'Overdue' : 'Active' ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No borrowed books.</p>
    <?php endif; ?>

    <hr>
    <h3>Navigation</h3>
    <ul>
        <li><a href="catalog.php">Browse Books</a></li>
        <li><a href="history.php">Borrowing History</a></li>
        <li><a href="profile.php">My Profile</a></li>
    </ul>
</body>
</html>