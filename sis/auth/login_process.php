<?php
require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 1. Check sa ADMIN Table (Email & Password)
    $admin_query = "SELECT * FROM admins WHERE email = '$identifier' AND password = '$password' LIMIT 1";
    $admin_res = mysqli_query($conn, $admin_query);

    if (mysqli_num_rows($admin_res) > 0) {
        $admin_data = mysqli_fetch_assoc($admin_res);
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = $admin_data['id'];
        $_SESSION['name'] = $admin_data['fullname'];
        
        header("Location: ../admin/dashboard.php");
        exit();
    }

    // 2. Kon wala sa Admin, check sa STUDENT Table (Student ID & Birthday)
    $student_query = "SELECT * FROM students WHERE student_id = '$identifier' AND birthday = '$password' LIMIT 1";
    $student_res = mysqli_query($conn, $student_query);

    if (mysqli_num_rows($student_res) > 0) {
        $student_data = mysqli_fetch_assoc($student_res);
        $_SESSION['role'] = 'student';
        $_SESSION['student_id'] = $student_data['student_id'];
        $_SESSION['name'] = $student_data['fullname'];
        
        header("Location: ../student/dashboard.php");
        exit();
    }

    // 3. Kon walay match sa duha
    header("Location: ../index.php?error=Invalid credentials. Please try again.");
    exit();
}