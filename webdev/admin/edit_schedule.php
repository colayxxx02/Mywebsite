<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $student_id = intval($_POST['student_id']);
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $time_day = trim($_POST['time_day']);
    $room = trim($_POST['room']);
    
    // Validation
    if (empty($id) || empty($student_id) || empty($subject_code) || empty($subject_name) || empty($time_day) || empty($room)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Update schedule
    $sql = "UPDATE schedules SET student_id=?, subject_code=?, subject_name=?, time_day=?, room=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $student_id, $subject_code, $subject_name, $time_day, $room, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating schedule']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>