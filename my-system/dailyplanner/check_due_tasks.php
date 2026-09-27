<?php
error_reporting(0);
ini_set('display_errors', 0);

// Siguraduha nga PH Timezone ang gamiton
date_default_timezone_set('Asia/Manila');

require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$current_now = date('Y-m-d H:i:s');

try {
    // I-query ang task nga naabot na ang oras ug PENDING pa o IN_PROGRESS
    $stmt = $pdo->prepare("
        SELECT id, task_name AS title, description, schedule_datetime AS due_date, status 
        FROM tasks 
        WHERE user_id = ? 
          AND status IN ('pending', 'in_progress')
          AND schedule_datetime <= ? 
          AND (notified IS NULL OR notified = 0)
        ORDER BY schedule_datetime ASC 
        LIMIT 1
    ");
    
    $stmt->execute([$user_id, $current_now]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        echo json_encode([
            'status' => 'success', 
            'has_due' => true, 
            'task' => $task,
            'server_time' => $current_now // Gamiton para sa debugging
        ]);
    } else {
        echo json_encode([
            'status' => 'success', 
            'has_due' => false,
            'server_time' => $current_now
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();