<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $student_id = intval($_POST['student_id']);
    $item_name = trim($_POST['item_name']);
    $status = trim($_POST['status']);
    
    // Validation
    if (empty($id) || empty($student_id) || empty($item_name) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Update clearance
    $sql = "UPDATE clearances SET student_id=?, item_name=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $student_id, $item_name, $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clearance updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating clearance']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>