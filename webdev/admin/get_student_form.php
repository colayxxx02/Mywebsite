<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    ?>
    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
    
    <div class="form-group">
        <label>Student ID:</label>
        <input type="text" name="student_id_number" value="<?php echo htmlspecialchars($student['student_id_number']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>Full Name:</label>
        <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Course:</label>
            <input type="text" name="course" value="<?php echo htmlspecialchars($student['course']); ?>" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
        </div>
    </div>
    
    <div class="form-group">
        <label>Contact Number:</label>
        <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number']); ?>">
    </div>
    <?php
} else {
    echo 'Student not found';
}

$stmt->close();
?>