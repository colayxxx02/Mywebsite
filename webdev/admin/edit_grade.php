<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $student_id = intval($_POST['student_id']);
    $subject_name = trim($_POST['subject_name']);
    $grade_value = floatval($_POST['grade_value']);
    $status = trim($_POST['status']);
    $semester = trim($_POST['semester']);
    
    // Validation
    if (empty($id) || empty($student_id) || empty($subject_name) || empty($grade_value) || empty($status) || empty($semester)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if ($grade_value < 1 || $grade_value > 4) {
        echo json_encode(['success' => false, 'message' => 'Grade value must be between 1.0 and 4.0']);
        exit();
    }
    
    // Update grade
    $sql = "UPDATE grades SET student_id=?, subject_name=?, grade_value=?, status=?, semester=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    
    // Corrected bind_param: i=integer, s=string, d=double, i=integer
    // Order: student_id(i), subject_name(s), grade_value(d), status(s), semester(s), id(i)
    $stmt->bind_param("isdssi", $student_id, $subject_name, $grade_value, $status, $semester, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grade updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating grade: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>