<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id_number = trim($_POST['student_id_number']);
    $fullname = trim($_POST['fullname']);
    $course = trim($_POST['course']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    
    if ($password !== $confirm_password) {
        header('Location: dashboard.php?error=Passwords do not match');
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'student')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Insert student
        $sql2 = "INSERT INTO students (user_id, student_id_number, fullname, course, email, contact_number) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("isssss", $user_id, $student_id_number, $fullname, $course, $email, $contact_number);
        
        if ($stmt2->execute()) {
            header('Location: dashboard.php?success=Student added successfully');
        } else {
            // Delete user if student insert fails
            $conn->query("DELETE FROM users WHERE id = $user_id");
            header('Location: dashboard.php?error=Error adding student');
        }
        $stmt2->close();
    } else {
        header('Location: dashboard.php?error=Username already exists');
    }
    $stmt->close();
}
?>