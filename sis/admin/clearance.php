<?php 
require_once('../config/db.php'); 

// Check Admin Login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- BACKEND LOGIC ---

// 1. UPDATE STATUS (Toggle between Cleared and Pending)
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['status'] == 'Cleared') ? 'Pending' : 'Cleared';
    
    mysqli_query($conn, "UPDATE students SET status = '$new_status' WHERE id = $id");
    header("Location: clearance.php?msg=updated");
    exit();
}

// 2. SEARCH LOGIC
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
    <title>Clearance Management | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Student Clearance</h1>
                <p class="text-slate-500 text-sm">Manage eligibility for enrollment and official records.</p>
            </div>
            
            <form action="clearance.php" method="POST" class="flex gap-2">
                <input type="text" name="search_query" value="<?php echo $search; ?>" placeholder="Search student name or ID..." class="px-4 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm w-64">
                <button type="submit" name="search" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-black transition">Search</button>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Student Details</th>
                        <th class="px-6 py-4">Course</th>
                        <th class="px-6 py-4 text-center">Clearance Status</th>
                        <th class="px-6 py-4 text-center">Action</th>
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
                            <td class="px-6 py-4 text-slate-600">
                                <?php echo $row['course']; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if($row['status'] == 'Cleared'): ?>
                                    <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-600 px-3 py-1 rounded-full text-[10px] font-bold">
                                        <span class="w-1.5 h-1.5 bg-green-600 rounded-full"></span>
                                        CLEARED
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-600 px-3 py-1 rounded-full text-[10px] font-bold">
                                        <span class="w-1.5 h-1.5 bg-red-600 rounded-full"></span>
                                        PENDING
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="?id=<?php echo $row['id']; ?>&status=<?php echo $row['status']; ?>" 
                                   class="inline-block px-4 py-1.5 rounded-lg text-xs font-bold transition <?php echo ($row['status'] == 'Cleared') ? 'bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600' : 'bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-100'; ?>">
                                    <?php echo ($row['status'] == 'Cleared') ? 'Mark as Pending' : 'Approve Clearance'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center text-slate-400 italic">
                                No student records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>