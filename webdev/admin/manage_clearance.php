<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$search = '';
$filter_student = '';
$filter_status = '';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

if (isset($_GET['filter_student'])) {
    $filter_student = trim($_GET['filter_student']);
}

if (isset($_GET['filter_status'])) {
    $filter_status = trim($_GET['filter_status']);
}

// Get all students for dropdown
$students_result = $conn->query("SELECT id, student_id_number, fullname FROM students ORDER BY fullname ASC");
$all_students = $students_result->fetch_all(MYSQLI_ASSOC);

// Get all clearances with search and filter
$sql = "SELECT c.*, s.student_id_number, s.fullname, s.course FROM clearances c 
        JOIN students s ON c.student_id = s.id WHERE 1=1";

if (!empty($filter_student)) {
    $filter_student_id = intval($filter_student);
    $sql .= " AND c.student_id = $filter_student_id";
}

if (!empty($filter_status)) {
    $filter_status_val = $conn->real_escape_string($filter_status);
    $sql .= " AND c.status = '$filter_status_val'";
}

if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $sql .= " AND (s.student_id_number LIKE '$search_term' OR s.fullname LIKE '$search_term' OR c.item_name LIKE '$search_term')";
}

$sql .= " ORDER BY c.id DESC";
$result = $conn->query($sql);
$clearances = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_clearance = $conn->query("SELECT COUNT(*) as count FROM clearances")->fetch_assoc()['count'];
$cleared = $conn->query("SELECT COUNT(*) as count FROM clearances WHERE status = 'Cleared'")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM clearances WHERE status = 'Pending'")->fetch_assoc()['count'];
$unpaid = $conn->query("SELECT COUNT(*) as count FROM clearances WHERE status = 'Unpaid'")->fetch_assoc()['count'];

// Get clearance items (unique)
$items_result = $conn->query("SELECT DISTINCT item_name FROM clearances ORDER BY item_name ASC");
$clearance_items = [];
while ($row = $items_result->fetch_assoc()) {
    $clearance_items[] = $row['item_name'];
}
if (empty($clearance_items)) {
    $clearance_items = ['Library', 'Laboratory', 'Tuition', 'Registrar'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clearances - SIS</title>
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

        .stat-badge.cleared {
            border-top-color: #27ae60;
        }

        .stat-badge.pending {
            border-top-color: #f39c12;
        }

        .stat-badge.unpaid {
            border-top-color: #e74c3c;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-badge.cleared .stat-number {
            color: #27ae60;
        }

        .stat-badge.pending .stat-number {
            color: #f39c12;
        }

        .stat-badge.unpaid .stat-number {
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

        .clearance-card {
            background: white;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .clearance-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .clearance-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .clearance-title {
            flex: 1;
        }

        .clearance-title h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 16px;
        }

        .clearance-subtitle {
            color: #7f8c8d;
            font-size: 13px;
        }

        .item-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-tag {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .status-cleared {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .clearance-info {
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

        .clearance-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .clearance-actions .btn {
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

        .status-info {
            background: #f0f4ff;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .quick-status-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .quick-btn {
            padding: 10px 12px;
            border: 2px solid #ecf0f1;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .quick-btn:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .quick-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .clearance-header {
                flex-direction: column;
            }

            .clearance-info {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
            }

            .quick-status-buttons {
                grid-template-columns: 1fr;
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
                <li><a href="manage_grades.php">📈 Grades</a></li>
                <li><a href="manage_schedules.php">📅 Schedules</a></li>
                <li><a href="manage_clearance.php" class="active">✅ Clearances</a></li>
                <li><a href="announcements.php">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>✅ Manage Student Clearances</h2>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-badge">
                    <div class="stat-number"><?php echo $total_clearance; ?></div>
                    <div class="stat-label">Total Clearances</div>
                </div>
                <div class="stat-badge cleared">
                    <div class="stat-number"><?php echo $cleared; ?></div>
                    <div class="stat-label">Cleared</div>
                </div>
                <div class="stat-badge pending">
                    <div class="stat-number"><?php echo $pending; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-badge unpaid">
                    <div class="stat-number"><?php echo $unpaid; ?></div>
                    <div class="stat-label">Unpaid</div>
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
                            <label for="filterStatus">Filter by Status:</label>
                            <select id="filterStatus" name="filter_status" onchange="document.getElementById('filterForm').submit()">
                                <option value="">-- All Status --</option>
                                <option value="Cleared" <?php echo $filter_status == 'Cleared' ? 'selected' : ''; ?>>✅ Cleared</option>
                                <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                <option value="Unpaid" <?php echo $filter_status == 'Unpaid' ? 'selected' : ''; ?>>💳 Unpaid</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="searchClearance">Search Item:</label>
                            <input type="text" id="searchClearance" name="search" placeholder="Search student or item..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">�� Search</button>
                            <a href="manage_clearance.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Add Clearance Button -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Clearance Item</button>
            </div>
            
            <!-- Clearances List -->
            <?php if (count($clearances) > 0): ?>
                <?php foreach ($clearances as $clearance): ?>
                <div class="clearance-card">
                    <div class="clearance-header">
                        <div class="clearance-title">
                            <h3><?php echo htmlspecialchars($clearance['item_name']); ?></h3>
                            <p class="clearance-subtitle">
                                <?php echo htmlspecialchars($clearance['student_id_number'] . ' - ' . $clearance['fullname']); ?>
                            </p>
                        </div>
                        <div>
                            <span class="status-tag status-<?php echo strtolower($clearance['status']); ?>">
                                <?php 
                                if ($clearance['status'] == 'Cleared') echo '✅ ' . $clearance['status'];
                                elseif ($clearance['status'] == 'Pending') echo '⏳ ' . $clearance['status'];
                                else echo '💳 ' . $clearance['status'];
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="clearance-info">
                        <div class="info-cell">
                            <span class="info-label">Course</span>
                            <span class="info-value"><?php echo htmlspecialchars($clearance['course']); ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="info-label">Current Status</span>
                            <span class="info-value">
                                <?php 
                                if ($clearance['status'] == 'Cleared') echo '✅ Cleared';
                                elseif ($clearance['status'] == 'Pending') echo '⏳ Pending';
                                else echo '💳 Unpaid';
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="clearance-actions">
                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $clearance['id']; ?>)">👁️ View</button>
                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $clearance['id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $clearance['id']; ?>, '<?php echo htmlspecialchars($clearance['item_name']); ?>')">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Clearances Found</h3>
                    <p>
                        <?php echo !empty($search) || !empty($filter_student) || !empty($filter_status) ? 'No clearances match your criteria.' : 'Click the "Add Clearance Item" button to add the first clearance.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD CLEARANCE MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add Clearance Item</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" onsubmit="addClearance(event)">
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
                        <label for="addItemName">Clearance Item:</label>
                        <select id="addItemName" name="item_name" required onchange="handleItemChange(this)">
                            <option value="">-- Select or Enter Item --</option>
                            <?php foreach ($clearance_items as $item): ?>
                                <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                            <?php endforeach; ?>
                            <option value="other">📝 Enter Custom Item</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="customItemDiv" style="display: none;">
                        <label for="customItem">Custom Item Name:</label>
                        <input type="text" id="customItem" name="custom_item" placeholder="Enter custom clearance item">
                    </div>
                    
                    <div class="form-group">
                        <label for="addStatus">Status:</label>
                        <select id="addStatus" name="status" required>
                            <option value="Pending">⏳ Pending</option>
                            <option value="Cleared">✅ Cleared</option>
                            <option value="Unpaid">💳 Unpaid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Clearance</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- VIEW CLEARANCE MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Clearance Details</h2>
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
    
    <!-- EDIT CLEARANCE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Clearance Item</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="editClearance(event)">
                <div class="modal-body">
                    <div id="editMessage"></div>
                    
                    <input type="hidden" id="editClearanceId" name="id">
                    
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
                        <label for="editItemName">Clearance Item:</label>
                        <input type="text" id="editItemName" name="item_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editStatus">Status:</label>
                        <div class="quick-status-buttons">
                            <button type="button" class="quick-btn" data-status="Cleared" onclick="setStatus(this, 'editStatus')">
                                ✅ Cleared
                            </button>
                            <button type="button" class="quick-btn" data-status="Pending" onclick="setStatus(this, 'editStatus')">
                                ⏳ Pending
                            </button>
                            <button type="button" class="quick-btn" data-status="Unpaid" onclick="setStatus(this, 'editStatus')">
                                💳 Unpaid
                            </button>
                        </div>
                        <select id="editStatus" name="status" required style="display: none;">
                            <option value="Pending">⏳ Pending</option>
                            <option value="Cleared">✅ Cleared</option>
                            <option value="Unpaid">💳 Unpaid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Clearance</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DELETE CLEARANCE MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Clearance</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete">
                    <div class="warning">
                        ⚠️ This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this clearance item:</p>
                    <p style="font-weight: 600; color: #e74c3c; font-size: 16px;" id="deleteClearanceName"></p>
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

        // UTILITY FUNCTION FOR HANDLING ITEM CHANGE
        function handleItemChange(select) {
            const customDiv = document.getElementById('customItemDiv');
            if (select.value === 'other') {
                customDiv.style.display = 'block';
                document.getElementById('customItem').required = true;
            } else {
                customDiv.style.display = 'none';
                document.getElementById('customItem').required = false;
            }
        }

        // SET STATUS FUNCTION FOR QUICK BUTTONS
        function setStatus(btn, selectId) {
            event.preventDefault();
            const status = btn.getAttribute('data-status');
            document.getElementById(selectId).value = status;
            
            // Update button states
            btn.parentElement.querySelectorAll('.quick-btn').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        }

        // ADD MODAL FUNCTIONS
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
            document.getElementById('addForm').reset();
            document.getElementById('addMessage').innerHTML = '';
            document.getElementById('customItemDiv').style.display = 'none';
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
            document.getElementById('addForm').reset();
        }

        function addClearance(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addForm'));
            
            // If custom item is selected, use custom value
            if (document.getElementById('addItemName').value === 'other') {
                formData.set('item_name', document.getElementById('customItem').value);
            }
            
            fetch('add_clearance.php', {
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
        function openViewModal(clearanceId) {
            fetch('get_clearance.php?id=' + clearanceId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusIcon = data.clearance.status === 'Cleared' ? '✅' : 
                                          data.clearance.status === 'Pending' ? '⏳' : '💳';
                        
                        const html = `
                            <p><strong>Student:</strong> ${data.clearance.student_id_number} - ${data.clearance.fullname}</p>
                            <p><strong>Course:</strong> ${data.clearance.course}</p>
                            <p><strong>Item Name:</strong> ${data.clearance.item_name}</p>
                            <p><strong>Status:</strong> ${statusIcon} ${data.clearance.status}</p>
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
        function openEditModal(clearanceId) {
            fetch('get_clearance.php?id=' + clearanceId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editClearanceId').value = data.clearance.id;
                        document.getElementById('editStudent').value = data.clearance.student_id;
                        document.getElementById('editItemName').value = data.clearance.item_name;
                        document.getElementById('editStatus').value = data.clearance.status;
                        document.getElementById('editMessage').innerHTML = '';
                        
                        // Set active button
                        const buttons = document.querySelectorAll('#editModal .quick-btn');
                        buttons.forEach(btn => {
                            btn.classList.remove('active');
                            if (btn.getAttribute('data-status') === data.clearance.status) {
                                btn.classList.add('active');
                            }
                        });
                        
                        document.getElementById('editModal').classList.add('show');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
        }

        function editClearance(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch('edit_clearance.php', {
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
        function openDeleteModal(clearanceId, itemName) {
            currentDeleteId = clearanceId;
            document.getElementById('deleteClearanceName').textContent = itemName;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;
            
            fetch('delete_clearance.php', {
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