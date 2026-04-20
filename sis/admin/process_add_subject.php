<?php
require_once('../config/db.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code       = mysqli_real_escape_string($conn, $_POST['code']);
    $name       = mysqli_real_escape_string($conn, $_POST['name']);
    $units      = $_POST['units'];
    $day        = mysqli_real_escape_string($conn, $_POST['day']);
    $room       = mysqli_real_escape_string($conn, $_POST['room']);
    $start      = $_POST['start'];
    $end        = $_POST['end'];
    $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);

    $sql = "INSERT INTO subjects (subject_code, subject_name, units, sched_day, sched_time_start, sched_time_end, instructor, room) 
            VALUES ('$code', '$name', '$units', '$day', '$start', '$end', '$instructor', '$room')";

    if(mysqli_query($conn, $sql)) {
        header("Location: course_sched.php?success=SubjectAdded");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>