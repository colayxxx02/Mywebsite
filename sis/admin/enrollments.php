<?php 
require_once('../config/db.php'); 

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- BACKEND LOGIC ---

if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE enrollments SET status = 'Enrolled' WHERE id = $id");
    header("Location: enrollments.php?msg=approved");
    exit();
}

if (isset($_GET['approve_all'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['approve_all']);
    mysqli_query($conn, "UPDATE enrollments SET status = 'Enrolled' WHERE student_id = '$student_id' AND status = 'Pending'");
    header("Location: enrollments.php?msg=all_approved");
    exit();
}

if (isset($_GET['drop'])) {
    $id = intval($_GET['drop']);
    mysqli_query($conn, "DELETE FROM enrollments WHERE id = $id");
    header("Location: enrollments.php?msg=dropped");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_sql = ($search !== '') ? " WHERE (fullname LIKE '%$search%' OR students.student_id LIKE '%$search%')" : "";

$student_query = "SELECT DISTINCT students.student_id, students.fullname, students.course, students.year_level 
                  FROM students 
                  JOIN enrollments ON students.student_id = enrollments.student_id 
                  $where_sql";
$students_result = mysqli_query($conn, $student_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Management | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .subject-row { display: none; }
        .subject-row.active { display: table-row; }
    </style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Student Enrollments</h1>
                <p class="text-slate-500 text-sm">Review student requests and finalize their enrollment.</p>
            </div>
            
            <form action="enrollments.php" method="GET" class="flex gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Name or ID..." class="px-4 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm w-64 shadow-sm">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg">Search</button>
            </form>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-600 text-white p-4 rounded-2xl mb-6 shadow-lg text-sm font-bold animate-bounce">
                <?php 
                    if($_GET['msg'] == 'approved') echo "Subject approved successfully!";
                    if($_GET['msg'] == 'all_approved') echo "Enrollment validated! All subjects are now enrolled.";
                    if($_GET['msg'] == 'dropped') echo "Subject has been removed.";
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Student Name & ID</th>
                        <th class="px-6 py-4">Course & Year</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($students_result) > 0): ?>
                        <?php while($s = mysqli_fetch_assoc($students_result)): 
                            // I-check kung naay pending ani nga student
                            $check_pending = mysqli_query($conn, "SELECT id FROM enrollments WHERE student_id = '{$s['student_id']}' AND status = 'Pending'");
                            $has_pending = mysqli_num_rows($check_pending) > 0;
                        ?>
                        <tr class="hover:bg-blue-50/50 cursor-pointer transition" onclick="toggleSubjects('<?php echo $s['student_id']; ?>')">
                            <td class="px-6 py-5">
                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($s['fullname']); ?></div>
                                <div class="text-[10px] text-blue-600 font-mono"><?php echo $s['student_id']; ?></div>
                            </td>
                            <td class="px-6 py-5 uppercase text-xs font-medium text-slate-500">
                                <?php echo $s['course']; ?> - <?php echo $s['year_level']; ?>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <?php if($has_pending): ?>
                                    <span class="bg-amber-100 text-amber-600 px-3 py-1 rounded-full text-[10px] font-bold">FOR APPROVAL</span>
                                <?php else: ?>
                                    <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[10px] font-bold">FULLY ENROLLED</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <button class="text-blue-600 text-xs font-bold uppercase hover:bg-blue-100 px-3 py-2 rounded-lg transition">Review Subjects ▼</button>
                            </td>
                        </tr>

                        <tr id="sub-<?php echo $s['student_id']; ?>" class="subject-row bg-slate-50/50">
                            <td colspan="4" class="px-8 py-6">
                                <div class="bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                                    <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
                                        <h3 class="font-bold text-slate-700 text-sm">Requested Subjects</h3>
                                        <?php if($has_pending): ?>
                                            <a href="?approve_all=<?php echo $s['student_id']; ?>" 
                                               onclick="return confirm('Approve all pending subjects for this student?')"
                                               class="bg-green-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-green-700 shadow-lg transition">
                                               ✓ VALIDATE & APPROVE ENROLLMENT
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-100 text-[9px] uppercase font-bold text-slate-500">
                                            <tr>
                                                <th class="px-4 py-2 text-left">Code</th>
                                                <th class="px-4 py-2 text-left">Subject Name</th>
                                                <th class="px-4 py-2 text-center">Status</th>
                                                <th class="px-4 py-2 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php 
                                            $sid = $s['student_id'];
                                            $sub_res = mysqli_query($conn, "SELECT enrollments.id as eid, enrollments.status, subjects.subject_code, subjects.subject_name 
                                                                          FROM enrollments 
                                                                          JOIN subjects ON enrollments.subject_id = subjects.id 
                                                                          WHERE enrollments.student_id = '$sid'");
                                            while($row = mysqli_fetch_assoc($sub_res)):
                                            ?>
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 font-bold text-blue-600"><?php echo $row['subject_code']; ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo $row['subject_name']; ?></td>
                                                <td class="px-4 py-3 text-center">
                                                    <?php if($row['status'] == 'Pending'): ?>
                                                        <span class="text-[9px] bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-bold tracking-tighter">PENDING</span>
                                                    <?php else: ?>
                                                        <span class="text-[9px] bg-green-100 text-green-600 px-2 py-0.5 rounded-full font-bold tracking-tighter">ENROLLED</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-right space-x-3">
                                                    <?php if($row['status'] == 'Pending'): ?>
                                                        <a href="?approve=<?php echo $row['eid']; ?>" class="text-blue-600 font-bold hover:underline text-xs">Approve</a>
                                                    <?php endif; ?>
                                                    <a href="?drop=<?php echo $row['eid']; ?>" onclick="return confirm('Drop this subject?')" class="text-red-500 font-bold hover:underline text-xs">Drop</a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="p-20 text-center text-slate-400 italic">No active enrollment requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function toggleSubjects(id) {
            const row = document.getElementById('sub-' + id);
            const isActive = row.classList.contains('active');
            
            // Close all
            document.querySelectorAll('.subject-row').forEach(r => r.classList.remove('active'));
            
            // Toggle gipili
            if (!isActive) row.classList.add('active');
        }
    </script>
</body>
</html>