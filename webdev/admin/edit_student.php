<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $student_id_number = trim($_POST['student_id_number']);
    $fullname = trim($_POST['fullname']);
    $course = trim($_POST['course']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    
    $sql = "UPDATE students SET student_id_number=?, fullname=?, course=?, email=?, contact_number=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $student_id_number, $fullname, $course, $email, $contact_number, $id);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?success=Student updated successfully');
    } else {
        header('Location: dashboard.php?error=Error updating student');
    }
    $stmt->close();
}
?>