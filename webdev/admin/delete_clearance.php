<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid clearance ID']);
        exit();
    }
    
    // Delete clearance
    $sql = "DELETE FROM clearances WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clearance deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting clearance']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>