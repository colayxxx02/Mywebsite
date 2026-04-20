<?php
require_once('../config/db.php');

// 1. Security Check: Kinahanglan naay naka-login
if (!isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

// ---------------------------------------------------------
// CASE A: ADMIN IS ADDING/EDITING A SUBJECT IN THE CURRICULUM
// ---------------------------------------------------------
if ($_SESSION['role'] === 'admin' && isset($_POST['subject_id']) && $_POST['subject_id'] === 'CHECK_ADMIN') {
    
    $id         = mysqli_real_escape_string($conn, $_POST['id']);
    $day        = mysqli_real_escape_string($conn, $_POST['day']);
    $start      = mysqli_real_escape_string($conn, $_POST['start']);
    $end        = mysqli_real_escape_string($conn, $_POST['end']);
    $room       = mysqli_real_escape_string($conn, $_POST['room']);
    $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);

    // Logic: I-check kung naay laing subject sa samang adlaw ug oras 
    // nga naggamit sa samang ROOM o samang INSTRUCTOR.
    $admin_check = mysqli_query($conn, "
        SELECT subject_code, room, instructor 
        FROM subjects 
        WHERE sched_day = '$day' 
        AND ('$start' < sched_time_end AND '$end' > sched_time_start) 
        AND (room = '$room' OR instructor = '$instructor')
        AND id != '$id' 
        LIMIT 1
    ");

    if (mysqli_num_rows($admin_check) > 0) {
        $conflict = mysqli_fetch_assoc($admin_check);
        $reason = ($conflict['room'] === $room) ? "Room $room is already occupied" : "Instructor $instructor is already scheduled";
        
        echo json_encode([
            'status' => 'conflict',
            'message' => "Schedule Conflict! $reason by " . $conflict['subject_code'] . "."
        ]);
    } else {
        echo json_encode(['status' => 'clear']);
    }
    exit();
}

// ---------------------------------------------------------
// CASE B: STUDENT (OR ADMIN) IS CHECKING STUDENT SCHEDULE OVERLAP
// ---------------------------------------------------------
$student_id = ($_SESSION['role'] === 'admin' && isset($_POST['student_id'])) 
              ? mysqli_real_escape_string($conn, $_POST['student_id']) 
              : $_SESSION['student_id'];

$new_subject_id = mysqli_real_escape_string($conn, $_POST['subject_id']);

if (!$new_subject_id || !$student_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data.']);
    exit();
}

// 1. Kuhaon ang schedule sa bag-ong subject nga i-add
$new_sub_query = mysqli_query($conn, "SELECT * FROM subjects WHERE id = '$new_subject_id'");
$new_sub = mysqli_fetch_assoc($new_sub_query);

if (!$new_sub) {
    echo json_encode(['status' => 'error', 'message' => 'Subject not found.']);
    exit();
}

$new_day   = $new_sub['sched_day'];
$new_start = $new_sub['sched_time_start'];
$new_end   = $new_sub['sched_time_end'];

// 2. Conflict Logic para sa schedule sa estudyante
// Gi-check sa 'enrollments' table (Cart/Enrolled) kung naay overlap
$conflict_query = mysqli_query($conn, "
    SELECT s.subject_name, s.subject_code 
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.student_id = '$student_id' 
    AND s.sched_day = '$new_day'
    AND ('$new_start' < s.sched_time_end AND '$new_end' > s.sched_time_start)
    LIMIT 1
");

if (mysqli_num_rows($conflict_query) > 0) {
    $conflict = mysqli_fetch_assoc($conflict_query);
    echo json_encode([
        'status' => 'conflict',
        'message' => "Schedule Conflict! This overlaps with " . $conflict['subject_code'] . " (" . $conflict['subject_name'] . ")."
    ]);
} else {
    echo json_encode(['status' => 'clear']);
}