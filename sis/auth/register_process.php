<?php
require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];

    if ($type == 'admin') {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        $sql = "INSERT INTO admins (fullname, email, password) VALUES ('$fullname', '$email', '$password')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Admin Registered!'); window.location='../index.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }

    } elseif ($type == 'student') {
        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
        $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
        $course = mysqli_real_escape_string($conn, $_POST['course']);

        // Base features lang sa una (fullname, student_id, bday, course)
        $sql = "INSERT INTO students (fullname, student_id, birthday, course, status) 
                VALUES ('$fullname', '$student_id', '$birthday', '$course', 'Uncleared')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Student Registered!'); window.location='../index.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>