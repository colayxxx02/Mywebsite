<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student info
$sql = "SELECT * FROM students WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "Student record not found";
    exit();
}

// Get grades
$grades = $conn->query("SELECT * FROM grades WHERE student_id = " . $student['id'])->fetch_all(MYSQLI_ASSOC);

// Get schedule
$schedules = $conn->query("SELECT * FROM schedules WHERE student_id = " . $student['id'])->fetch_all(MYSQLI_ASSOC);

// Get clearance
$clearances = $conn->query("SELECT * FROM clearances WHERE student_id = " . $student['id'])->fetch_all(MYSQLI_ASSOC);

// Get announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SIS</title>
    <link rel="stylesheet" href="../css/student.css">
</head>
<body>
    <div class="navbar">
        <h1>👨‍🎓 Student Dashboard</h1>
        <div class="navbar-right">
            <span>Welcome, <strong><?php echo $student['fullname']; ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-grid">
            <!-- Profile Card -->
            <div class="card">
                <h3>👤 My Profile</h3>
                <div class="info-item">
                    <span class="info-label">Student ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['student_id_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['fullname']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Course</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['course']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['contact_number']); ?></span>
                </div>
                <button class="btn btn-primary" style="width: 100%; margin-top: 15px;" onclick="openEditModal()">✏️ Edit Profile</button>
            </div>
            
            <!-- Grades Card -->
            <div class="card">
                <h3>📈 My Grades</h3>
                <?php if (count($grades) > 0): ?>
                    <?php foreach ($grades as $grade): ?>
                    <div class="grade-item">
                        <div class="grade-subject">
                            <strong><?php echo htmlspecialchars($grade['subject_name']); ?></strong><br>
                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($grade['semester']); ?></small>
                        </div>
                        <span class="grade-value"><?php echo $grade['grade_value']; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No grades yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Schedule Card -->
            <div class="card">
                <h3>📅 Class Schedule</h3>
                <?php if (count($schedules) > 0): ?>
                    <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-item">
                        <div class="schedule-time">⏰ <?php echo htmlspecialchars($schedule['time_day']); ?></div>
                        <div class="schedule-subject"><?php echo htmlspecialchars($schedule['subject_name']); ?> (<?php echo htmlspecialchars($schedule['subject_code']); ?>)</div>
                        <div class="schedule-room">📍 <?php echo htmlspecialchars($schedule['room']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No schedule yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Clearance Card -->
            <div class="card">
                <h3>✅ Clearance Status</h3>
                <?php if (count($clearances) > 0): ?>
                    <?php foreach ($clearances as $clearance): ?>
                    <div class="clearance-item">
                        <span class="clearance-name"><?php echo htmlspecialchars($clearance['item_name']); ?></span>
                        <span class="clearance-status status-<?php echo strtolower($clearance['status']); ?>">
                            <?php echo $clearance['status']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No clearance requirements</p>
                <?php endif; ?>
            </div>
            
            <!-- Announcements Card -->
            <div class="card" style="grid-column: 1 / -1;">
                <h3>📢 Latest Announcements</h3>
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement">
                        <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                        <div class="announcement-content"><?php echo htmlspecialchars($announcement['content']); ?></div>
                        <div class="announcement-date">📅 <?php echo date('M d, Y H:i', strtotime($announcement['date_posted'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No announcements yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit My Profile</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST" action="update_profile.php">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                    
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number:</label>
                            <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number']); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" style="background: #7f8c8d;" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>