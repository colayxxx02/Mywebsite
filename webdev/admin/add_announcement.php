<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    // Validation
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if (strlen($title) > 255) {
        echo json_encode(['success' => false, 'message' => 'Title must not exceed 255 characters']);
        exit();
    }
    
    if (strlen($content) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Content must not exceed 2000 characters']);
        exit();
    }
    
    // Insert announcement
    $sql = "INSERT INTO announcements (title, content) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $title, $content);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Announcement posted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error posting announcement']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>