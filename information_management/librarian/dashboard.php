<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

// Count statistics
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
</head>
<body>
    <h2>Welcome, <?= $_SESSION['fullname'] ?>!</h2>
    <a href="../logout.php">Logout</a>

    <hr>
    <h3>Dashboard</h3>

    <p>Total Books: <strong><?= $total_books ?></strong></p>
    <p>Available: <strong><?= $available ?></strong></p>
    <p>Borrowed: <strong><?= $borrowed ?></strong></p>
    <p>Damaged: <strong><?= $damaged ?></strong></p>
    <p>Overdue: <strong><?= $overdue ?></strong></p>
    <p>Total Members: <strong><?= $total_members ?></strong></p>

    <hr>
    <h3>Navigation</h3>
    <ul>
        <li><a href="books.php">Manage Books</a></li>
        <li><a href="transactions.php">Transactions</a></li>
        <li><a href="overdue.php">Overdue Books</a></li>
        <li><a href="maintenance.php">Maintenance Log</a></li>
    </ul>
</body>
</html>