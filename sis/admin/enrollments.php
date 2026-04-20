<?php 
require_once('../config/db.php'); 

// Check Admin Login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- BACKEND LOGIC ---

// 1. APPROVE ENROLLMENT
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    // I-update ang status sa enrollment
    mysqli_query($conn, "UPDATE enrollments SET status = 'Enrolled' WHERE id = $id");
    
    // Kuhaa ang student_id ani nga enrollment
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_id FROM enrollments WHERE id = $id"));
    $sid = $res['student_id'];
    
    // I-update ang status sa student ngadto sa 'Cleared' (Hampton Logic)
    mysqli_query($conn, "UPDATE students SET status = 'Cleared' WHERE student_id = '$sid'");
    
    header("Location: enrollments.php?msg=approved");
}

// 2. REJECT/DELETE ENROLLMENT
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    mysqli_query($conn, "DELETE FROM enrollments WHERE id = $id");
    header("Location: enrollments.php?msg=rejected");
}

// Fetch Pending and Enrolled students with Subject Details
$query = "SELECT enrollments.id as eid, enrollments.status as estatus, enrollments.enroll_date, 
          students.fullname, students.student_id, 
          subjects.subject_code, subjects.subject_name 
          FROM enrollments 
          JOIN students ON enrollments.student_id = students.student_id 
          JOIN subjects ON enrollments.subject_id = subjects.id 
          ORDER BY enrollments.enroll_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Management | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">Enrollment Requests</h1>
            <p class="text-slate-500 text-sm">Review and approve student subject enrollments.</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-blue-600 text-white p-4 rounded-xl mb-6 shadow-lg shadow-blue-100 text-sm font-bold animate-bounce">
                <?php 
                    if($_GET['msg'] == 'approved') echo "Enrollment has been successfully approved!";
                    if($_GET['msg'] == 'rejected') echo "Request has been removed.";
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Student</th>
                        <th class="px-6 py-4">Subject Requested</th>
                        <th class="px-6 py-4">Date Requested</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50 transition text-sm">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900"><?php echo $row['fullname']; ?></div>
                                <div class="text-[10px] text-slate-400 font-mono"><?php echo $row['student_id']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-blue-600"><?php echo $row['subject_code']; ?></div>
                                <div class="text-xs text-slate-500"><?php echo $row['subject_name']; ?></div>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <?php echo date("M d, Y", strtotime($row['enroll_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if($row['estatus'] == 'Pending'): ?>
                                    <span class="bg-amber-100 text-amber-600 px-3 py-1 rounded-full text-[10px] font-bold">PENDING</span>
                                <?php else: ?>
                                    <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-[10px] font-bold">ENROLLED</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center space-x-2">
                                <?php if($row['estatus'] == 'Pending'): ?>
                                    <a href="?approve=<?php echo $row['eid']; ?>" class="bg-slate-900 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-black transition">Approve</a>
                                    <a href="?reject=<?php echo $row['eid']; ?>" onclick="return confirm('Reject this request?')" class="text-red-500 font-bold text-xs hover:underline">Reject</a>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs italic font-medium">No actions needed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center text-slate-400 italic">
                                No enrollment requests found in the system.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>