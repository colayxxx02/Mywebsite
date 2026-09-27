<?php
require_once 'db.php';
checkLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$action = $_POST['action'] ?? null;

if ($task_id) {
    if ($action === 'dismiss') {
        // I-mark lang nga gi-notified na aron dili na mo-popup usab
        $stmt = $pdo->prepare("UPDATE tasks SET notified = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        echo json_encode(['status' => 'success']);
        exit();
    }

    if (in_array($new_status, ['pending', 'in_progress', 'completed', 'canceled'])) {
    $stmt = $pdo->prepare("UPDATE tasks SET status = ?, notified = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$new_status, $task_id, $user_id])) {
        echo json_encode(['status' => 'success']);
        exit();
    }
}
}

echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);