<?php
require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eid = $_POST['eid']; // Enrollment ID
    $sid = $_POST['sid']; // Student ID (para mabalik sa view)
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);

    // I-update ang grade sa enrollments table
    $sql = "UPDATE enrollments SET grade = '$grade' WHERE id = '$eid'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Grade updated successfully!'); window.location='manage_students.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>