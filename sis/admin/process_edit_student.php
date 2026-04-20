<?php
require_once('../config/db.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_sid = $_POST['old_student_id'];
    $new_sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name    = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course  = mysqli_real_escape_string($conn, $_POST['course']);
    $major   = mysqli_real_escape_string($conn, $_POST['major']);
    $gender  = $_POST['gender'];
    $bday    = $_POST['birthday'];
    $rel     = mysqli_real_escape_string($conn, $_POST['religion']);
    $con     = mysqli_real_escape_string($conn, $_POST['contact']);

    $sql = "UPDATE students SET 
            student_id = '$new_sid', 
            fullname = '$name', 
            course = '$course', 
            major = '$major', 
            gender = '$gender', 
            birthday = '$bday', 
            religion = '$rel', 
            contact = '$con' 
            WHERE student_id = '$old_sid'";

    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Student Information Updated!'); window.location='manage_students.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>