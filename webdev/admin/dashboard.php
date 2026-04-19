<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_courses = $conn->query("SELECT COUNT(DISTINCT subject_name) as count FROM grades")->fetch_assoc()['count'];
$pending_clearance = $conn->query("SELECT COUNT(*) as count FROM clearances WHERE status = 'Pending'")->fetch_assoc()['count'];

// Get all students
$students_result = $conn->query("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id");
$students = $students_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIS</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="navbar">
        <h1>👨‍💼 Admin Dashboard</h1>
        <div class="navbar-right">
            <span>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    
    <div class="admin-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="manage_students.php">👥 Manage Students</a></li>
                <li><a href="manage_grades.php">📈 Grades</a></li>
                <li><a href="manage_schedules.php">📅 Schedules</a></li>
                <li><a href="manage_clearance.php">✅ Clearances</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>Dashboard Overview</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Courses</div>
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Clearances</div>
                    <div class="stat-number"><?php echo $pending_clearance; ?></div>
                </div>
            </div>
            
            <div class="section-card">
                <div class="section-header">
                    <h3>👥 Student List</h3>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="openAddModal()">➕ Add Student</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $student['id']; ?>)">👁️ View</button>
                                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $student['id']; ?>)">✏️ Edit</button>
                                        <button class="btn btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Student</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" method="POST" action="add_student.php">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student ID:</label>
                            <input type="text" name="student_id_number" required>
                        </div>
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="username" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password:</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" name="fullname" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Course:</label>
                            <input type="text" name="course" required>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number:</label>
                        <input type="tel" name="contact_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Student Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Student</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Student</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST" action="edit_student.php">
                <div class="modal-body" id="editContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function openViewModal(studentId) {
            fetch('get_student.php?id=' + studentId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('viewContent').innerHTML = data;
                    document.getElementById('viewModal').style.display = 'block';
                });
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        function openEditModal(studentId) {
            fetch('get_student_form.php?id=' + studentId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editContent').innerHTML = data;
                    document.getElementById('editModal').style.display = 'block';
                });
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function deleteStudent(studentId) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('delete_student.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + studentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Student deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('viewModal')) {
                closeViewModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>