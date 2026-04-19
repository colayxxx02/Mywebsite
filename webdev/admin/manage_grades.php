<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$search = '';
$filter_student = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['filter_student'])) {
    $filter_student = trim($_GET['filter_student']);
}

// Get all students for dropdown
$students_result = $conn->query("SELECT id, student_id_number, fullname FROM students ORDER BY fullname ASC");
$all_students = $students_result->fetch_all(MYSQLI_ASSOC);

// Get all grades with search and filter
$sql = "SELECT g.*, s.student_id_number, s.fullname, s.course FROM grades g 
        JOIN students s ON g.student_id = s.id WHERE 1=1";

if (!empty($filter_student)) {
    $filter_student_id = intval($filter_student);
    $sql .= " AND g.student_id = $filter_student_id";
}

if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $sql .= " AND (s.student_id_number LIKE '$search_term' OR s.fullname LIKE '$search_term' OR g.subject_name LIKE '$search_term')";
}

$sql .= " ORDER BY g.id DESC";
$result = $conn->query($sql);
$grades = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_grades = $conn->query("SELECT COUNT(*) as count FROM grades")->fetch_assoc()['count'];
$passed_count = $conn->query("SELECT COUNT(*) as count FROM grades WHERE status = 'Passed'")->fetch_assoc()['count'];
$failed_count = $conn->query("SELECT COUNT(*) as count FROM grades WHERE status = 'Failed'")->fetch_assoc()['count'];
$incomplete_count = $conn->query("SELECT COUNT(*) as count FROM grades WHERE status = 'Incomplete'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - SIS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-badge {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 4px solid #667eea;
        }

        .stat-badge.passed {
            border-top-color: #27ae60;
        }

        .stat-badge.failed {
            border-top-color: #e74c3c;
        }

        .stat-badge.incomplete {
            border-top-color: #f39c12;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-badge.passed .stat-number {
            color: #27ae60;
        }

        .stat-badge.failed .stat-number {
            color: #e74c3c;
        }

        .stat-badge.incomplete .stat-number {
            color: #f39c12;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .filters-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 13px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .grade-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .grade-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .grade-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .grade-title {
            flex: 1;
        }

        .grade-title h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }

        .grade-subtitle {
            color: #7f8c8d;
            font-size: 13px;
        }

        .grade-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .grade-value {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
        }

        .status-tag {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-passed {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-incomplete {
            background: #fff3cd;
            color: #856404;
        }

        .grade-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-cell {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .info-value {
            color: #555;
            font-size: 14px;
        }

        .grade-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .grade-actions .btn {
            padding: 8px 15px;
            font-size: 13px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
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
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .grade-header {
                flex-direction: column;
            }

            .grade-info {
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
                <li><a href="manage_students.php">👥 Manage Students</a></li>
                <li><a href="manage_grades.php" class="active">📈 Grades</a></li>
                <li><a href="manage_schedules.php">📅 Schedules</a></li>
                <li><a href="manage_clearance.php">✅ Clearances</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>📈 Manage Grades</h2>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-badge">
                    <div class="stat-number"><?php echo $total_grades; ?></div>
                    <div class="stat-label">Total Grades</div>
                </div>
                <div class="stat-badge passed">
                    <div class="stat-number"><?php echo $passed_count; ?></div>
                    <div class="stat-label">Passed</div>
                </div>
                <div class="stat-badge failed">
                    <div class="stat-number"><?php echo $failed_count; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-badge incomplete">
                    <div class="stat-number"><?php echo $incomplete_count; ?></div>
                    <div class="stat-label">Incomplete</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" id="filterForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="filterStudent">Filter by Student:</label>
                            <select id="filterStudent" name="filter_student" onchange="document.getElementById('filterForm').submit()">
                                <option value="">-- All Students --</option>
                                <?php foreach ($all_students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo $filter_student == $student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['student_id_number'] . ' - ' . $student['fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="searchGrade">Search Subject:</label>
                            <input type="text" id="searchGrade" name="search" placeholder="Search subject name..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">🔍 Search</button>
                            <a href="manage_grades.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Add Grade Button -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Grade</button>
            </div>
            
            <!-- Grades List -->
            <?php if (count($grades) > 0): ?>
                <?php foreach ($grades as $grade): ?>
                <div class="grade-card">
                    <div class="grade-header">
                        <div class="grade-title">
                            <h3><?php echo htmlspecialchars($grade['subject_name']); ?></h3>
                            <p class="grade-subtitle">
                                <?php echo htmlspecialchars($grade['student_id_number'] . ' - ' . $grade['fullname']); ?>
                            </p>
                        </div>
                        <div class="grade-badge">
                            <div class="grade-value"><?php echo $grade['grade_value']; ?></div>
                            <span class="status-tag status-<?php echo strtolower($grade['status']); ?>">
                                <?php echo $grade['status']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grade-info">
                        <div class="info-cell">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($grade['course']); ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="info-label">Semester</span>
                            <span class="info-value"><?php echo htmlspecialchars($grade['semester']); ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="info-label">Grade Scale</span>
                            <span class="info-value">
                                <?php 
                                $grade_val = floatval($grade['grade_value']);
                                if ($grade_val <= 1.5) echo '📊 Excellent';
                                elseif ($grade_val <= 2.5) echo '📊 Good';
                                elseif ($grade_val <= 3.0) echo '📊 Satisfactory';
                                else echo '📊 Passing';
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grade-actions">
                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $grade['id']; ?>)">👁️ View</button>
                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $grade['id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $grade['id']; ?>, '<?php echo htmlspecialchars($grade['subject_name']); ?>')">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Grades Found</h3>
                    <p>
                        <?php echo !empty($search) || !empty($filter_student) ? 'No grades match your criteria.' : 'Click the "Add Grade" button to add the first grade.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD GRADE MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Grade</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" onsubmit="addGrade(event)">
                <div class="modal-body">
                    <div id="addMessage"></div>
                    
                    <div class="form-group">
                        <label for="addStudent">Student:</label>
                        <select id="addStudent" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['student_id_number'] . ' - ' . $student['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="addSubject">Subject Name:</label>
                        <input type="text" id="addSubject" name="subject_name" required placeholder="e.g., Programming 101">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addGradeValue">Grade Value (1.0 - 4.0):</label>
                            <input type="number" id="addGradeValue" name="grade_value" required min="1" max="4" step="0.01" placeholder="e.g., 1.50">
                        </div>
                        <div class="form-group">
                            <label for="addStatus">Status:</label>
                            <select id="addStatus" name="status" required>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                                <option value="Incomplete">Incomplete</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addSemester">Semester:</label>
                        <input type="text" id="addSemester" name="semester" required placeholder="e.g., 1st Semester">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Grade</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- VIEW GRADE MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Grade Details</h2>
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
    
    <!-- EDIT GRADE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Grade</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="editGrade(event)">
                <div class="modal-body">
                    <div id="editMessage"></div>
                    
                    <input type="hidden" id="editGradeId" name="id">
                    
                    <div class="form-group">
                        <label for="editStudent">Student:</label>
                        <select id="editStudent" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['student_id_number'] . ' - ' . $student['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editSubject">Subject Name:</label>
                        <input type="text" id="editSubject" name="subject_name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editGradeValue">Grade Value (1.0 - 4.0):</label>
                            <input type="number" id="editGradeValue" name="grade_value" required min="1" max="4" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="editStatus">Status:</label>
                            <select id="editStatus" name="status" required>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                                <option value="Incomplete">Incomplete</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editSemester">Semester:</label>
                        <input type="text" id="editSemester" name="semester" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Grade</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DELETE GRADE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Grade</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete">
                    <div class="warning">
                        ⚠️ This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this grade for:</p>
                    <p style="font-weight: 600; color: #e74c3c; font-size: 16px;" id="deleteGradeName"></p>
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

        function addGrade(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addForm'));
            
            fetch('add_grade.php', {
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
        function openViewModal(gradeId) {
            fetch('get_grade.php?id=' + gradeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const gradeScale = data.grade.grade_value <= 1.5 ? 'Excellent' : 
                                          data.grade.grade_value <= 2.5 ? 'Good' :
                                          data.grade.grade_value <= 3.0 ? 'Satisfactory' : 'Passing';
                        
                        const html = `
                            <p><strong>Student:</strong> ${data.grade.student_id_number} - ${data.grade.fullname}</p>
                            <p><strong>Subject:</strong> ${data.grade.subject_name}</p>
                            <p><strong>Grade Value:</strong> ${data.grade.grade_value}</p>
                            <p><strong>Grade Scale:</strong> ${gradeScale}</p>
                            <p><strong>Status:</strong> ${data.grade.status}</p>
                            <p><strong>Semester:</strong> ${data.grade.semester}</p>
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
        function openEditModal(gradeId) {
            fetch('get_grade.php?id=' + gradeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editGradeId').value = data.grade.id;
                        document.getElementById('editStudent').value = data.grade.student_id;
                        document.getElementById('editSubject').value = data.grade.subject_name;
                        document.getElementById('editGradeValue').value = data.grade.grade_value;
                        document.getElementById('editStatus').value = data.grade.status;
                        document.getElementById('editSemester').value = data.grade.semester;
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

        function editGrade(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch('edit_grade.php', {
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
        function openDeleteModal(gradeId, subjectName) {
            currentDeleteId = gradeId;
            document.getElementById('deleteGradeName').textContent = subjectName;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;
            
            fetch('delete_grade.php', {
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