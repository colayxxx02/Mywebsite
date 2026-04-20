<?php 
require_once('../config/db.php'); 

// Check if the student is logged in
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Fetch logged-in student's data
$student_id = $_SESSION['student_id'];
$query = "SELECT * FROM students WHERE student_id = '$student_id'";
$student_data = mysqli_fetch_assoc(mysqli_query($conn, $query));

// Fetch latest 3 announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl font-serif">H</div>
            <span class="font-bold text-lg tracking-tight">Hampton <span class="text-blue-600">SIS</span></span>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-xs font-bold text-slate-900"><?php echo $student_data['fullname']; ?></p>
                <p class="text-[10px] text-slate-400"><?php echo $student_data['student_id']; ?></p>
            </div>
            <a href="../auth/logout.php" class="bg-slate-100 hover:bg-red-50 hover:text-red-600 p-2 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 md:p-8">
        
        <div class="mb-10">
            <h1 class="text-3xl font-bold text-slate-900">Good Day, <?php echo explode(' ', $student_data['fullname'])[0]; ?>! 👋</h1>
            <p class="text-slate-500">Here is your academic overview for the current semester.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center text-3xl mb-4 border-4 border-white shadow-sm text-slate-400">👤</div>
                        <h2 class="font-bold text-lg"><?php echo $student_data['fullname']; ?></h2>
                        <p class="text-sm text-blue-600 font-medium"><?php echo $student_data['course']; ?></p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between p-3 bg-slate-50 rounded-xl">
                            <span class="text-xs font-bold text-slate-400 uppercase">Clearance</span>
                            <span class="text-xs font-bold <?php echo ($student_data['status'] == 'Cleared') ? 'text-green-600' : 'text-red-500'; ?>">
                                <?php echo strtoupper($student_data['status']); ?>
                            </span>
                        </div>
                        <div class="flex justify-between p-3 bg-slate-50 rounded-xl">
                            <span class="text-xs font-bold text-slate-400 uppercase">Enrollment</span>
                            <span class="text-xs font-bold text-blue-600">
                                <?php echo strtoupper($student_data['enrollment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-blue-600 rounded-full"></span> Details
                    </h3>
                    <ul class="text-sm space-y-4">
                        <li class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400">Email</span>
                            <span class="font-medium"><?php echo $student_data['email'] ?? 'Not Set'; ?></span>
                        </li>
                        <li class="flex justify-between border-b border-slate-50 pb-2">
                            <span class="text-slate-400">Contact</span>
                            <span class="font-medium"><?php echo $student_data['contact'] ?? 'Not Set'; ?></span>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 rounded-3xl text-white shadow-xl shadow-blue-100 relative overflow-hidden group">
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold mb-2">Ready to Enroll?</h2>
                        <p class="text-blue-100 text-sm mb-6 max-w-md">Select your subjects and manage your schedule for this semester. Make sure your clearance is settled!</p>
                        <a href="enrollment.php" class="inline-flex items-center gap-2 bg-white text-blue-700 px-6 py-3 rounded-2xl font-bold hover:bg-blue-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 100-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z" />
                            </svg>
                            Enroll & Add Subjects
                        </a>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute -bottom-4 -right-4 h-40 w-40 text-blue-500/20 rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>

                <section>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-bold text-xl">Latest Announcements</h2>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Bulletin Board</span>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if(mysqli_num_rows($announcements) > 0): ?>
                            <?php while($post = mysqli_fetch_assoc($announcements)): ?>
                            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:border-blue-300 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase">
                                        <?php echo $post['category']; ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                                <h3 class="font-bold text-slate-900 mb-1"><?php echo $post['title']; ?></h3>
                                <p class="text-sm text-slate-500 line-clamp-2"><?php echo $post['content']; ?></p>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bg-white p-10 rounded-3xl border border-dashed border-slate-300 text-center text-slate-400 italic">
                                No new announcements at the moment.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="grades.php" class="bg-slate-900 text-white p-6 rounded-3xl flex items-center justify-between group hover:bg-black transition">
                        <div>
                            <h3 class="font-bold">My Report Card</h3>
                            <p class="text-xs text-slate-400">View your grades and academic performance.</p>
                        </div>
                        <div class="bg-slate-800 p-3 rounded-2xl group-hover:translate-x-1 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </a>
                    
                    <a href="schedule.php" class="bg-blue-600 text-white p-6 rounded-3xl flex items-center justify-between group hover:bg-blue-700 transition">
                        <div>
                            <h3 class="font-bold">Class Schedule</h3>
                            <p class="text-xs text-blue-200">Check your time and classroom assignments.</p>
                        </div>
                        <div class="bg-blue-500 p-3 rounded-2xl group-hover:translate-x-1 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </main>

</body>
</html>