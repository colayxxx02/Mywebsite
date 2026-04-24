<?php 
require_once('../config/db.php'); 

// Check Admin Login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- AJAX API LOGIC ---
if (isset($_GET['action']) && $_GET['action'] == 'toggle_item') {
    $sid = mysqli_real_escape_string($conn, $_GET['student_id']);
    $item = mysqli_real_escape_string($conn, $_GET['item']); 
    
    // Kuhaon ang status karon
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT $item FROM students WHERE student_id = '$sid'"));
    $new_val = ($res[$item] == 1) ? 0 : 1;
    
    // Update sa Database
    mysqli_query($conn, "UPDATE students SET $item = $new_val WHERE student_id = '$sid'");
    
    // I-check kung tanan offices cleared na ba para sa overall status
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT library, accounting, department, dean FROM students WHERE student_id = '$sid'"));
    $overall = ($check['library'] && $check['accounting'] && $check['department'] && $check['dean']) ? 'Cleared' : 'Uncleared';
    
    mysqli_query($conn, "UPDATE students SET status = '$overall' WHERE student_id = '$sid'");
    
    echo json_encode(['success' => true, 'new_val' => $new_val, 'overall' => $overall]);
    exit();
}

// --- SEARCH LOGIC ---
$search = "";
if (isset($_POST['search'])) {
    $search = mysqli_real_escape_string($conn, $_POST['search_query']);
    $query = "SELECT * FROM students WHERE fullname LIKE '%$search%' OR student_id LIKE '%$search%' ORDER BY fullname ASC";
} else {
    $query = "SELECT * FROM students ORDER BY created_at DESC";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clearance Portal | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .details-row { display: none; }
    </style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Student Clearance</h1>
                <p class="text-slate-500 text-sm">Click a student to toggle clearance per department.</p>
            </div>
            
            <form action="clearance.php" method="POST" class="flex gap-2">
                <input type="text" name="search_query" value="<?php echo $search; ?>" placeholder="Search student..." class="px-4 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm w-64 shadow-sm">
                <button type="submit" name="search" class="bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">Search</button>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-black text-slate-400 tracking-[0.15em]">
                    <tr>
                        <th class="px-8 py-5">Full Name</th>
                        <th class="px-8 py-5">Course</th>
                        <th class="px-8 py-5 text-center">Status</th>
                        <th class="px-8 py-5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        
                        <tr class="hover:bg-blue-50/40 transition cursor-pointer group" onclick="toggleRow('<?php echo $row['student_id']; ?>')">
                            <td class="px-8 py-5">
                                <div class="font-bold text-slate-900 group-hover:text-blue-600 transition"><?php echo $row['fullname']; ?></div>
                                <div class="text-[11px] text-slate-400 font-mono"><?php echo $row['student_id']; ?></div>
                            </td>
                            <td class="px-8 py-5 text-slate-600 font-medium"><?php echo $row['course']; ?></td>
                            <td class="px-8 py-5 text-center" id="overall-badge-<?php echo $row['student_id']; ?>">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-wider <?php echo ($row['status'] == 'Cleared') ? 'bg-green-100 text-green-600' : 'bg-rose-100 text-rose-600'; ?>">
                                    <?php echo strtoupper($row['status'] ?? 'UNCLEARED'); ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="text-blue-500 text-xs font-bold bg-blue-50 px-3 py-1 rounded-lg">Manage ↓</span>
                            </td>
                        </tr>

                        <tr id="row-<?php echo $row['student_id']; ?>" class="details-row bg-slate-50/50 shadow-inner">
                            <td colspan="4" class="px-12 py-8 border-y border-slate-100">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                    <?php 
                                    $offices = [
                                        'library' => 'Library',
                                        'accounting' => 'Accounting',
                                        'department' => 'Department',
                                        'dean' => 'Dean\'s Office'
                                    ];
                                    foreach($offices as $col => $label):
                                        $is_cleared = $row[$col] ?? 0;
                                    ?>
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 flex flex-col items-center gap-4 shadow-sm hover:shadow-md transition">
                                        <span class="text-[11px] font-bold uppercase text-slate-500 tracking-widest"><?php echo $label; ?></span>
                                        
                                        <button 
                                            onclick="updateStatus('<?php echo $row['student_id']; ?>', '<?php echo $col; ?>')"
                                            id="btn-<?php echo $row['student_id']; ?>-<?php echo $col; ?>"
                                            class="w-full py-2.5 rounded-xl text-[11px] font-black transition-all transform active:scale-95 <?php echo $is_cleared ? 'bg-green-600 text-white shadow-lg shadow-green-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'; ?>">
                                            <?php echo $is_cleared ? '✓ CLEARED' : 'CLEAR'; ?>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-8 py-20 text-center text-slate-400 italic font-medium">No student records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Open/Close student checklist
        function toggleRow(sid) {
            const targetRow = document.getElementById('row-' + sid);
            const isVisible = targetRow.style.display === 'table-row';
            
            // Close all first
            document.querySelectorAll('.details-row').forEach(r => r.style.display = 'none');
            
            // Open clicked row
            targetRow.style.display = isVisible ? 'none' : 'table-row';
        }

        // Handle AJAX Clearance Update
        function updateStatus(sid, item) {
            // Prevent main row toggle when clicking button
            event.stopPropagation();

            fetch(`clearance.php?action=toggle_item&student_id=${sid}&item=${item}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const btn = document.getElementById(`btn-${sid}-${item}`);
                    
                    if(data.new_val == 1) {
                        // Display CLEARED status (Green)
                        btn.className = "w-full py-2.5 rounded-xl text-[11px] font-black bg-green-600 text-white shadow-lg shadow-green-100 transition-all transform active:scale-95";
                        btn.innerHTML = "✓ CLEARED";
                    } else {
                        // Back to CLEAR button (Gray)
                        btn.className = "w-full py-2.5 rounded-xl text-[11px] font-black bg-slate-100 text-slate-400 hover:bg-slate-200 transition-all transform active:scale-95";
                        btn.innerHTML = "CLEAR";
                    }

                    // Update Overall Badge
                    const badge = document.getElementById(`overall-badge-${sid}`);
                    const isOverallCleared = data.overall === 'Cleared';
                    badge.innerHTML = `
                        <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-wider ${isOverallCleared ? 'bg-green-100 text-green-600' : 'bg-rose-100 text-rose-600'}">
                            ${data.overall.toUpperCase()}
                        </span>
                    `;
                }
            });
        }
    </script>
</body>
</html>