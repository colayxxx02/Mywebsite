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

// Get all announcements
$sql = "SELECT * FROM announcements WHERE 1=1";

if (!empty($search)) {
    $search_term = '%' . $conn->real_escape_string($search) . '%';
    $sql .= " AND (title LIKE '$search_term' OR content LIKE '$search_term')";
}

$sql .= " ORDER BY date_posted DESC";
$result = $conn->query($sql);
$announcements = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];

// Get today's announcements
$today = date('Y-m-d');
$today_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE DATE(date_posted) = '$today'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - SIS</title>
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

        .stat-badge.today {
            border-top-color: #27ae60;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-badge.today .stat-number {
            color: #27ae60;
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

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .search-group {
            display: flex;
            flex-direction: column;
        }

        .search-group input {
            padding: 10px 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 13px;
        }

        .search-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .announcement-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .announcement-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .announcement-title {
            flex: 1;
        }

        .announcement-title h3 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 18px;
            line-height: 1.4;
        }

        .announcement-date {
            color: #7f8c8d;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .date-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .announcement-content {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .announcement-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .announcement-actions .btn {
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
            max-width: 700px;
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
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .char-count {
            text-align: right;
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }

        .view-data {
            line-height: 1.8;
        }

        .view-data h3 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .view-data .date-info {
            background: #f0f4ff;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .view-data p {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
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

        .confirm-delete .announcement-title-delete {
            font-weight: 600;
            color: #e74c3c;
            font-size: 16px;
            margin: 15px 0;
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }

            .announcement-header {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
            }

            .announcement-content {
                max-height: 150px;
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
                <li><a href="manage_clearance.php">✅ Clearances</a></li>
                <li><a href="announcements.php" class="active">📢 Announcements</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h2>📢 Manage Announcements</h2>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-badge">
                    <div class="stat-number"><?php echo $total_announcements; ?></div>
                    <div class="stat-label">Total Announcements</div>
                </div>
                <div class="stat-badge today">
                    <div class="stat-number"><?php echo $today_announcements; ?></div>
                    <div class="stat-label">Posted Today</div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="filters-section">
                <form method="GET" id="searchForm">
                    <div class="search-grid">
                        <div class="search-group">
                            <input type="text" name="search" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">🔍 Search</button>
                            <a href="announcements.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Add Announcement Button -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="openAddModal()">➕ Add Announcement</button>
            </div>
            
            <!-- Announcements List -->
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div class="announcement-title">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <div class="announcement-date">
                                📅 <?php echo date('M d, Y H:i', strtotime($announcement['date_posted'])); ?>
                            </div>
                        </div>
                        <div class="date-badge">
                            <?php echo date('Y-m-d', strtotime($announcement['date_posted'])); ?>
                        </div>
                    </div>
                    
                    <div class="announcement-content">
                        <?php echo htmlspecialchars($announcement['content']); ?>
                    </div>
                    
                    <div class="announcement-actions">
                        <button class="btn btn-primary" onclick="openViewModal(<?php echo $announcement['id']; ?>)">👁�� View Full</button>
                        <button class="btn btn-warning" onclick="openEditModal(<?php echo $announcement['id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-danger" onclick="openDeleteModal(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(substr($announcement['title'], 0, 50)); ?>')">🗑️ Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📭 No Announcements Found</h3>
                    <p>
                        <?php echo !empty($search) ? 'No announcements match your search.' : 'Click the "Add Announcement" button to post the first announcement.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD ANNOUNCEMENT MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New Announcement</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form id="addForm" onsubmit="addAnnouncement(event)">
                <div class="modal-body">
                    <div id="addMessage"></div>
                    
                    <div class="form-group">
                        <label for="addTitle">Announcement Title:</label>
                        <input type="text" id="addTitle" name="title" required placeholder="Enter announcement title" maxlength="255">
                        <div class="char-count"><span id="addTitleCount">0</span>/255</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="addContent">Announcement Content:</label>
                        <textarea id="addContent" name="content" required placeholder="Enter announcement content..." maxlength="2000"></textarea>
                        <div class="char-count"><span id="addContentCount">0</span>/2000</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- VIEW ANNOUNCEMENT MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👁️ View Announcement</h2>
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
    
    <!-- EDIT ANNOUNCEMENT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Announcement</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" onsubmit="editAnnouncement(event)">
                <div class="modal-body">
                    <div id="editMessage"></div>
                    
                    <input type="hidden" id="editAnnouncementId" name="id">
                    
                    <div class="form-group">
                        <label for="editTitle">Announcement Title:</label>
                        <input type="text" id="editTitle" name="title" required placeholder="Enter announcement title" maxlength="255">
                        <div class="char-count"><span id="editTitleCount">0</span>/255</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editContent">Announcement Content:</label>
                        <textarea id="editContent" name="content" required placeholder="Enter announcement content..." maxlength="2000"></textarea>
                        <div class="char-count"><span id="editContentCount">0</span>/2000</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DELETE ANNOUNCEMENT MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Delete Announcement</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirm-delete">
                    <div class="warning">
                        ⚠️ This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete this announcement:</p>
                    <p class="announcement-title-delete" id="deleteAnnouncementTitle"></p>
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

        // CHARACTER COUNT FUNCTIONS
        function updateCharCount(input, countId, max) {
            const count = input.value.length;
            document.getElementById(countId).textContent = count;
            
            if (count > max) {
                input.value = input.value.substring(0, max);
                document.getElementById(countId).textContent = max;
            }
        }

        // ADD MODAL FUNCTIONS
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
            document.getElementById('addForm').reset();
            document.getElementById('addMessage').innerHTML = '';
            document.getElementById('addTitleCount').textContent = '0';
            document.getElementById('addContentCount').textContent = '0';
            
            // Add character counter listeners
            document.getElementById('addTitle').oninput = function() {
                updateCharCount(this, 'addTitleCount', 255);
            };
            document.getElementById('addContent').oninput = function() {
                updateCharCount(this, 'addContentCount', 2000);
            };
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
            document.getElementById('addForm').reset();
        }

        function addAnnouncement(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addForm'));
            
            fetch('add_announcement.php', {
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
        function openViewModal(announcementId) {
            fetch('get_announcement.php?id=' + announcementId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const datePosted = new Date(data.announcement.date_posted);
                        const formattedDate = datePosted.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        const html = `
                            <h3>${data.announcement.title}</h3>
                            <div class="date-info">
                                ��� Posted on ${formattedDate}
                            </div>
                            <p>${data.announcement.content.replace(/\n/g, '<br>')}</p>
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
        function openEditModal(announcementId) {
            fetch('get_announcement.php?id=' + announcementId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editAnnouncementId').value = data.announcement.id;
                        document.getElementById('editTitle').value = data.announcement.title;
                        document.getElementById('editContent').value = data.announcement.content;
                        document.getElementById('editMessage').innerHTML = '';
                        
                        // Update character counts
                        document.getElementById('editTitleCount').textContent = data.announcement.title.length;
                        document.getElementById('editContentCount').textContent = data.announcement.content.length;
                        
                        // Add character counter listeners
                        document.getElementById('editTitle').oninput = function() {
                            updateCharCount(this, 'editTitleCount', 255);
                        };
                        document.getElementById('editContent').oninput = function() {
                            updateCharCount(this, 'editContentCount', 2000);
                        };
                        
                        document.getElementById('editModal').classList.add('show');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
        }

        function editAnnouncement(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch('edit_announcement.php', {
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
        function openDeleteModal(announcementId, title) {
            currentDeleteId = announcementId;
            document.getElementById('deleteAnnouncementTitle').textContent = title;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            currentDeleteId = null;
        }

        function confirmDelete() {
            if (!currentDeleteId) return;
            
            fetch('delete_announcement.php', {
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