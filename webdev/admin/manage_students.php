<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Get all students with search
$sql = "SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE 1=1";
if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $sql .= " AND (s.student_id_number LIKE '$search_term' OR s.fullname LIKE '$search_term' OR s.email LIKE '$search_term' OR s.course LIKE '$search_term')";
}
$sql .= " ORDER BY s.id DESC";
$result = $conn->query($sql);
$students = $result->fetch_all(MYSQLI_ASSOC);

// Get total count
$total_sql = "SELECT COUNT(*) as count FROM students";
$total_result = $conn->query($total_sql);
$total_students = $total_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - SIS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .stats-summary {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .search-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-section form {
            display: flex;
            gap: 10px;
            flex: 1;
            min-width: 250px;
        }

        .search-section input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-section input:focus {
            outline: none;
            border-color: #667eea;
        }

        .student-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .student-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .student-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-id-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-value {
            color: #555;
            font-size: 14px;
        }

        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }

        .action-row .btn {
            padding: 8px 15px;
            font-size: 13px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            opacity: 0.7;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 15px 25px;
            background: #f9f9f9;
            border-radius: 0 0 12px 12px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-top: 1px solid #ecf0f1;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .form-message.success {
            background: #d5f4e6;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .form-message.error {
            background: #fadbd8;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .view-data {
            line-height: 1.8;
        }

        .view-data p {
            margin-bottom: 12px;
        }

        .view-data strong {
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }

        .confirm-delete {
            text-align: center;
            padding: 20px;
        }

        .confirm-delete p {
            margin-bottom: 20px;
            color: #555;
            font-size: 15px;
        }

        .confirm-delete .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        @media (max-width: 768px) {
            .student-header {
                flex-direction: column;
            }

            .student-info {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
            }
        }
    </style>
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
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_students.php" class="active">👥 Manage Students</a></li>
                <li><a href="manage_grades.php">📈 Grades</a></li>
                <li><a href="manage_schedules.php">📅 Schedules</a></li>
                <li><a href="manage_clearance.php">✅ Clearances</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>👥 Manage Students</h2>
            </div>
            
            <div class="stats-summary">
                <strong>📊 Total Students:</strong> <?php echo $total_students; ?>
            </div>
            
            <div class="search-section">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search by ID, name, email, or course..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="manage_students.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Student</button>
            </div>
            
            <!-- Students List -->
            <?php if (count($students) > 0): ?>
                <?php foreach ($students as $student): ?>
                <div class="student-card">
                    <div class="student-header">
                        <h3 style="margin: 0; color: #2c3e50;"><?php echo htmlspecialchars($student['fullname']); ?></h3>
                        <span class="student-id-badge"><?php echo htmlspecialchars($student['student_id_number']); ?></span>
                    </div>
                    
                    <div class="student-info">
                        <div class="info-row">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['course']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contact</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['contact_number']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Username</span>
                            <span class="info-value"><?php echo htmlspecialchars($student['username']); ?></span>
                        </div>
                    </div>
                    
                    <div class="action-row">
                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $student['id']; ?>)">👁️ View</button>
                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $student['id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['fullname']); ?>')">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Students Found</h3>
                    <p>
                        <?php echo !empty($search) ? 'No students match your search criteria.' : 'Click the "Add Student" button to add the first student.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD STUDENT MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Student</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" onsubmit="addStudent(event)">
                <div class="modal-body">
                    <div id="addMessage"></div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addStudentId">Student ID Number:</label>
                            <input type="text" id="addStudentId" name="student_id_number" required placeholder="e.g., 2024-0001">
                        </div>
                        <div class="form-group">
                            <label for="addUsername">Username:</label>
                            <input type="text" id="addUsername" name="username" required placeholder="Login username" minlength="4">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addPassword">Password:</label>
                            <input type="password" id="addPassword" name="password" required placeholder="Min 6 characters" minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="addConfirmPassword">Confirm Password:</label>
                            <input type="password" id="addConfirmPassword" name="confirm_password" required placeholder="Confirm password" minlength="6">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addFullName">Full Name:</label>
                        <input type="text" id="addFullName" name="fullname" required placeholder="Student's full name">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addCourse">Course:</label>
                            <input type="text" id="addCourse" name="course" required placeholder="e.g., BSCS">
                        </div>
                        <div class="form-group">
                            <label for="addEmail">Email:</label>
                            <input type="email" id="addEmail" name="email" required placeholder="student@example.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addContact">Contact Number:</label>
                        <input type="tel" id="addContact" name="contact_number" placeholder="e.g., 09123456789">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- VIEW STUDENT MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Student Details</h2>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="viewContent" class="view-data"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- EDIT STUDENT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Student Information</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="editStudent(event)">
                <div class="modal-body">
                    <div id="editMessage"></div>
                    
                    <input type="hidden" id="editStudentId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editStudentIdNum">Student ID Number:</label>
                            <input type="text" id="editStudentIdNum" name="student_id_number" required>
                        </div>
                        <div class="form-group">
                            <label for="editFullName">Full Name:</label>
                            <input type="text" id="editFullName" name="fullname" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editCourse">Course:</label>
                            <input type="text" id="editCourse" name="course" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email:</label>
                            <input type="email" id="editEmail" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editContact">Contact Number:</label>
                        <input type="tel" id="editContact" name="contact_number">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DELETE STUDENT MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Student</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete">
                    <div class="warning">
                        ⚠️ This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete:</p>
                    <p style="font-weight: 600; color: #e74c3c; font-size: 16px;" id="deleteStudentName"></p>
                    <p style="color: #7f8c8d; font-size: 13px;">All associated records (grades, schedule, clearance) will also be deleted.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Permanently</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentDeleteId = null;

        // ADD MODAL FUNCTIONS
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
            document.getElementById('addForm').reset();
            document.getElementById('addMessage').innerHTML = '';
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
            document.getElementById('addForm').reset();
        }

        function addStudent(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addForm'));
            const password = document.getElementById('addPassword').value;
            const confirmPassword = document.getElementById('addConfirmPassword').value;
            
            if (password !== confirmPassword) {
                showMessage('addMessage', 'Passwords do not match!', 'error');
                return;
            }
            
            fetch('add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('addMessage', '✅ ' + data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('addMessage', '❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('addMessage', '❌ Error: ' + error, 'error');
            });
        }

        // VIEW MODAL FUNCTIONS
        function openViewModal(studentId) {
            fetch('get_student.php?id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const html = `
                            <p><strong>Student ID:</strong> ${data.student.student_id_number}</p>
                            <p><strong>Full Name:</strong> ${data.student.fullname}</p>
                            <p><strong>Username:</strong> ${data.student.username}</p>
                            <p><strong>Course:</strong> ${data.student.course}</p>
                            <p><strong>Email:</strong> ${data.student.email}</p>
                            <p><strong>Contact Number:</strong> ${data.student.contact_number}</p>
                        `;
                        document.getElementById('viewContent').innerHTML = html;
                        document.getElementById('viewModal').classList.add('show');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
        }

        // EDIT MODAL FUNCTIONS
        function openEditModal(studentId) {
            fetch('get_student.php?id=' + studentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editStudentId').value = data.student.id;
                        document.getElementById('editStudentIdNum').value = data.student.student_id_number;
                        document.getElementById('editFullName').value = data.student.fullname;
                        document.getElementById('editCourse').value = data.student.course;
                        document.getElementById('editEmail').value = data.student.email;
                        document.getElementById('editContact').value = data.student.contact_number;
                        document.getElementById('editMessage').innerHTML = '';
                        document.getElementById('editModal').classList.add('show');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
        }

        function editStudent(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch('edit_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('editMessage', '✅ ' + data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('editMessage', '❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('editMessage', '❌ Error: ' + error, 'error');
            });
        }

        // DELETE MODAL FUNCTIONS
        function openDeleteModal(studentId, studentName) {
            currentDeleteId = studentId;
            document.getElementById('deleteStudentName').textContent = studentName;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;
            
            fetch('delete_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + currentDeleteId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error));
        }

        // UTILITY FUNCTION
        function showMessage(elementId, message, type) {
            const messageDiv = document.getElementById(elementId);
            messageDiv.className = 'form-message ' + type;
            messageDiv.innerHTML = message;
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === addModal) closeAddModal();
            if (event.target === viewModal) closeViewModal();
            if (event.target === editModal) closeEditModal();
            if (event.target === deleteModal) closeDeleteModal();
        }
    </script>
</body>
</html>