<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id']);
    $subject_name = trim($_POST['subject_name']);
    $grade_value = floatval($_POST['grade_value']);
    $status = trim($_POST['status']);
    $semester = trim($_POST['semester']);
    
    // Validation
    if (empty($student_id) || empty($subject_name) || empty($grade_value) || empty($status) || empty($semester)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if ($grade_value < 1 || $grade_value > 4) {
        echo json_encode(['success' => false, 'message' => 'Grade value must be between 1.0 and 4.0']);
        exit();
    }
    
    // Check if student exists
    $check_sql = "SELECT id FROM students WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
    
    // Insert grade
    $sql = "INSERT INTO grades (student_id, subject_name, grade_value, status, semester) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $student_id, $subject_name, $grade_value, $status, $semester);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grade added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding grade']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>