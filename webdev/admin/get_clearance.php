<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT c.*, s.student_id_number, s.fullname, s.course FROM clearances c 
            JOIN students s ON c.student_id = s.id WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $clearance = $result->fetch_assoc();
        echo json_encode(['success' => true, 'clearance' => $clearance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Clearance not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>