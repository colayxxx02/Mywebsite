<?php 
require_once('../config/db.php'); 

// I-check kung admin ba gyud ang naka-login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- UPDATED QUERIES ---

// 1. Total Students
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students"))['count'];

// 2. Total Programs (Courses)
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];

// 3. Enrolled (Kadtong 'Enrolled' ang enrollment_status)
$enrolled_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE enrollment_status='Enrolled'"))['count'];

// 4. Pending (Kadtong 'Pending' ang status)
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE enrollment_status='Pending'"))['count'];

// 5. Graduating (Kini importante: gi-check nato ang 'year_level' column)
$graduating_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE year_level='Graduating'"))['count'];

// 6. Dropped
$dropped_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE enrollment_status='Dropped'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Dashboard Overview</h1>
                <p class="text-slate-500 text-sm">Welcome back, Admin <?php echo $_SESSION['name']; ?>!</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-sm text-slate-400 font-medium bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200">
                    <?php echo date('F d, Y'); ?>
                </div>
                <a href="../auth/logout.php" onclick="return confirm('Are you sure?')" class="bg-red-50 text-red-600 px-5 py-2 rounded-xl text-sm font-bold border border-red-100 hover:bg-red-600 hover:text-white transition-all">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl mr-4 text-2xl">👥</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Total Students</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($total_students); ?></h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-purple-50 text-purple-600 rounded-2xl mr-4 text-2xl">🎓</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Total Programs</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($total_courses); ?></h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-green-50 text-green-600 rounded-2xl mr-4 text-2xl">✅</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Enrolled Now</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($enrolled_count); ?></h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl mr-4 text-2xl">⏳</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Pending Enrollment</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($pending_count); ?></h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 ring-2 ring-indigo-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl mr-4 text-2xl">📜</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Graduating Class</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($graduating_count); ?></h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center hover:shadow-md transition">
                <div class="p-4 bg-red-50 text-red-600 rounded-2xl mr-4 text-2xl">🛑</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Dropped Students</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?php echo number_format($dropped_count); ?></h3>
                </div>
            </div>

        </div>

        <div class="mt-10 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <h2 class="text-lg font-bold text-slate-800 mb-2">System Status</h2>
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <span class="flex h-3 w-3 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                Hampton SIS is online. Database connections are active.
            </div>
        </div>
    </main>

</body>
</html>