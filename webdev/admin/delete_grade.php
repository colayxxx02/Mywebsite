<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid grade ID']);
        exit();
    }
    
    // Delete grade
    $sql = "DELETE FROM grades WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grade deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting grade']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>