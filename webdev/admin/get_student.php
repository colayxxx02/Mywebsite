<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

$id = intval($_GET['id']);
$sql = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    ?>
    <div style="line-height: 2;">
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id_number']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['fullname']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($student['contact_number']); ?></p>
    </div>
    <?php
} else {
    echo 'Student not found';
}

$stmt->close();
?>