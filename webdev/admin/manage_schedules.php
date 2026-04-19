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

// Get all schedules with search and filter
$sql = "SELECT sch.*, s.student_id_number, s.fullname, s.course FROM schedules sch 
        JOIN students s ON sch.student_id = s.id WHERE 1=1";

if (!empty($filter_student)) {
    $filter_student_id = intval($filter_student);
    $sql .= " AND sch.student_id = $filter_student_id";
}

if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $sql .= " AND (s.student_id_number LIKE '$search_term' OR s.fullname LIKE '$search_term' OR sch.subject_name LIKE '$search_term' OR sch.subject_code LIKE '$search_term')";
}

$sql .= " ORDER BY sch.id DESC";
$result = $conn->query($sql);
$schedules = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_schedules = $conn->query("SELECT COUNT(*) as count FROM schedules")->fetch_assoc()['count'];
$unique_students = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM schedules")->fetch_assoc()['count'];
$unique_subjects = $conn->query("SELECT COUNT(DISTINCT subject_name) as count FROM schedules")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - SIS</title>
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

        .stat-badge.students {
            border-top-color: #3498db;
        }

        .stat-badge.subjects {
            border-top-color: #e74c3c;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-badge.students .stat-number {
            color: #3498db;
        }

        .stat-badge.subjects .stat-number {
            color: #e74c3c;
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

        .schedule-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .schedule-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .schedule-title {
            flex: 1;
        }

        .schedule-title h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }

        .schedule-subtitle {
            color: #7f8c8d;
            font-size: 13px;
        }

        .time-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .schedule-info {
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

        .schedule-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .schedule-actions .btn {
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

        .time-info {
            background: #f0f4ff;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #667eea;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .schedule-header {
                flex-direction: column;
            }

            .schedule-info {
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
                <li><a href="dashboard.php">��� Dashboard</a></li>
                <li><a href="manage_students.php">👥 Manage Students</a></li>
                <li><a href="manage_grades.php">📈 Grades</a></li>
                <li><a href="manage_schedules.php" class="active">📅 Schedules</a></li>
                <li><a href="manage_clearance.php">✅ Clearances</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>📅 Manage Class Schedules</h2>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-badge">
                    <div class="stat-number"><?php echo $total_schedules; ?></div>
                    <div class="stat-label">Total Schedules</div>
                </div>
                <div class="stat-badge students">
                    <div class="stat-number"><?php echo $unique_students; ?></div>
                    <div class="stat-label">Students Enrolled</div>
                </div>
                <div class="stat-badge subjects">
                    <div class="stat-number"><?php echo $unique_subjects; ?></div>
                    <div class="stat-label">Unique Subjects</div>
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
                            <label for="searchSchedule">Search Subject/Code:</label>
                            <input type="text" id="searchSchedule" name="search" placeholder="Search subject or code..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">🔍 Search</button>
                            <a href="manage_schedules.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Add Schedule Button -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Schedule</button>
            </div>
            
            <!-- Schedules List -->
            <?php if (count($schedules) > 0): ?>
                <?php foreach ($schedules as $schedule): ?>
                <div class="schedule-card">
                    <div class="schedule-header">
                        <div class="schedule-title">
                            <h3><?php echo htmlspecialchars($schedule['subject_name']); ?></h3>
                            <p class="schedule-subtitle">
                                <?php echo htmlspecialchars($schedule['student_id_number'] . ' - ' . $schedule['fullname']); ?>
                            </p>
                        </div>
                        <div class="time-badge">
                            ⏰ <?php echo htmlspecialchars($schedule['time_day']); ?>
                        </div>
                    </div>
                    
                    <div class="schedule-info">
                        <div class="info-cell">
                            <span class="info-label">Subject Code</span>
                            <span class="info-value"><?php echo htmlspecialchars($schedule['subject_code']); ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="info-label">Room</span>
                            <span class="info-value">📍 <?php echo htmlspecialchars($schedule['room']); ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($schedule['course']); ?></span>
                        </div>
                    </div>
                    
                    <div class="schedule-actions">
                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $schedule['id']; ?>)">👁️ View</button>
                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $schedule['id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['subject_name']); ?>')">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Schedules Found</h3>
                    <p>
                        <?php echo !empty($search) || !empty($filter_student) ? 'No schedules match your criteria.' : 'Click the "Add Schedule" button to add the first schedule.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD SCHEDULE MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Schedule</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" onsubmit="addSchedule(event)">
                <div class="modal-body">
                    <div id="addMessage"></div>
                    
                    <div class="time-info">
                        💡 Format for Time/Day: e.g., "MWF 8:00AM - 9:00AM" or "TTh 10:00AM - 11:30AM"
                    </div>
                    
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
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="addSubjectCode">Subject Code:</label>
                            <input type="text" id="addSubjectCode" name="subject_code" required placeholder="e.g., CS101">
                        </div>
                        <div class="form-group">
                            <label for="addSubject">Subject Name:</label>
                            <input type="text" id="addSubject" name="subject_name" required placeholder="e.g., Programming 101">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addTimeDay">Time & Day:</label>
                        <input type="text" id="addTimeDay" name="time_day" required placeholder="e.g., MWF 8:00AM - 9:00AM">
                    </div>
                    
                    <div class="form-group">
                        <label for="addRoom">Room Number:</label>
                        <input type="text" id="addRoom" name="room" required placeholder="e.g., Room 101 or Lab A">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- VIEW SCHEDULE MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Schedule Details</h2>
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
    
    <!-- EDIT SCHEDULE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Schedule</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="editSchedule(event)">
                <div class="modal-body">
                    <div id="editMessage"></div>
                    
                    <div class="time-info">
                        💡 Format for Time/Day: e.g., "MWF 8:00AM - 9:00AM" or "TTh 10:00AM - 11:30AM"
                    </div>
                    
                    <input type="hidden" id="editScheduleId" name="id">
                    
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
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editSubjectCode">Subject Code:</label>
                            <input type="text" id="editSubjectCode" name="subject_code" required>
                        </div>
                        <div class="form-group">
                            <label for="editSubject">Subject Name:</label>
                            <input type="text" id="editSubject" name="subject_name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editTimeDay">Time & Day:</label>
                        <input type="text" id="editTimeDay" name="time_day" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRoom">Room Number:</label>
                        <input type="text" id="editRoom" name="room" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DELETE SCHEDULE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Schedule</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete">
                    <div class="warning">
                        ⚠️ This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this schedule:</p>
                    <p style="font-weight: 600; color: #e74c3c; font-size: 16px;" id="deleteScheduleName"></p>
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

        function addSchedule(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addForm'));
            
            fetch('add_schedule.php', {
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
        function openViewModal(scheduleId) {
            fetch('get_schedule.php?id=' + scheduleId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const html = `
                            <p><strong>Student:</strong> ${data.schedule.student_id_number} - ${data.schedule.fullname}</p>
                            <p><strong>Subject Code:</strong> ${data.schedule.subject_code}</p>
                            <p><strong>Subject Name:</strong> ${data.schedule.subject_name}</p>
                            <p><strong>Time & Day:</strong> ${data.schedule.time_day}</p>
                            <p><strong>Room:</strong> ${data.schedule.room}</p>
                            <p><strong>Course:</strong> ${data.schedule.course}</p>
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
        function openEditModal(scheduleId) {
            fetch('get_schedule.php?id=' + scheduleId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editScheduleId').value = data.schedule.id;
                        document.getElementById('editStudent').value = data.schedule.student_id;
                        document.getElementById('editSubjectCode').value = data.schedule.subject_code;
                        document.getElementById('editSubject').value = data.schedule.subject_name;
                        document.getElementById('editTimeDay').value = data.schedule.time_day;
                        document.getElementById('editRoom').value = data.schedule.room;
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

        function editSchedule(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch('edit_schedule.php', {
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
        function openDeleteModal(scheduleId, subjectName) {
            currentDeleteId = scheduleId;
            document.getElementById('deleteScheduleName').textContent = subjectName;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;
            
            fetch('delete_schedule.php', {
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