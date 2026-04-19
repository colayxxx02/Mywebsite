<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id']);
    $item_name = trim($_POST['item_name']);
    $status = trim($_POST['status']);
    
    // Validation
    if (empty($student_id) || empty($item_name) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
    
    // Check if clearance item already exists for this student
    $check_dup = "SELECT id FROM clearances WHERE student_id = ? AND item_name = ?";
    $stmt_dup = $conn->prepare($check_dup);
    $stmt_dup->bind_param("is", $student_id, $item_name);
    $stmt_dup->execute();
    $result_dup = $stmt_dup->get_result();
    
    if ($result_dup->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This clearance item already exists for this student']);
        $stmt_dup->close();
        exit();
    }
    $stmt_dup->close();
    
    // Insert clearance
    $sql = "INSERT INTO clearances (student_id, item_name, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $student_id, $item_name, $status);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clearance item added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding clearance']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>