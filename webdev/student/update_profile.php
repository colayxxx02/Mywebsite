<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    
    $sql = "UPDATE students SET fullname=?, email=?, contact_number=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $fullname, $email, $contact_number, $id);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?success=Profile updated successfully');
    } else {
        header('Location: dashboard.php?error=Error updating profile');
    }
    $stmt->close();
}
?>