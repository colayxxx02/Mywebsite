<?php 
require_once('../config/db.php'); 

// 1. Security Check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// 2. Fetch Student Data
$student_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, course FROM students WHERE student_id = '$student_id'"));

// 3. Fetch Enrolled Subjects Schedule
// We JOIN enrollment_details with subjects to get the time, room, and day
$schedule_query = mysqli_query($conn, "
    SELECT s.subject_code, s.subject_name, s.instructor, s.room, s.sched_day, s.sched_time_start, s.sched_time_end 
    FROM enrollments e 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.student_id = '$student_id' AND e.status = 'Enrolled'
    ORDER BY FIELD(s.sched_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.sched_time_start ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white border-b p-4 px-8 flex justify-between items-center sticky top-0 z-50">
        <a href="dashboard.php" class="text-blue-600 font-bold flex items-center gap-2 hover:text-blue-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Dashboard
        </a>
        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Weekly Class Schedule</span>
    </nav>

    <main class="max-w-6xl mx-auto mt-10 p-6 pb-20">
        
        <div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Class Schedule</h1>
                <p class="text-slate-500">Your assigned subjects, time, and room locations for this term.</p>
            </div>
            <div class="bg-blue-600 text-white px-6 py-3 rounded-2xl shadow-lg shadow-blue-100 flex items-center gap-3">
                <div class="text-2xl">📅</div>
                <div>
                    <p class="text-[10px] font-bold uppercase opacity-80">Current Semester</p>
                    <p class="font-bold text-sm">First Semester, 2026</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <?php if(mysqli_num_rows($schedule_query) > 0): ?>
                
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-5">Day & Time</th>
                                <th class="px-8 py-5">Subject</th>
                                <th class="px-8 py-5">Room</th>
                                <th class="px-8 py-5">Instructor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php while($row = mysqli_fetch_assoc($schedule_query)): ?>
                            <tr class="hover:bg-slate-50/50 transition group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xs">
                                            <?php echo substr($row['sched_day'], 0, 3); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm"><?php echo $row['sched_day']; ?></p>
                                            <p class="text-xs text-slate-400">
                                                <?php 
                                                    echo date('h:i A', strtotime($row['sched_time_start'])) . ' - ' . 
                                                         date('h:i A', strtotime($row['sched_time_end'])); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="font-bold text-slate-800"><?php echo $row['subject_name']; ?></p>
                                    <p class="text-[10px] font-mono text-blue-500 uppercase"><?php echo $row['subject_code']; ?></p>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-[10px] font-bold">
                                        📍 <?php echo !empty($row['room']) ? $row['room'] : 'TBA'; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-slate-200 rounded-full flex items-center justify-center text-[10px] text-slate-500 font-bold">
                                            <?php echo substr($row['instructor'] ?? '?', 0, 1); ?>
                                        </div>
                                        <p class="text-sm font-medium text-slate-700"><?php echo $row['instructor'] ?? 'To be assigned'; ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="bg-white p-20 rounded-3xl border-2 border-dashed border-slate-200 text-center">
                    <div class="text-6xl mb-6">🗓️</div>
                    <h3 class="text-xl font-bold text-slate-800">No Schedule Found</h3>
                    <p class="text-slate-400 max-w-sm mx-auto mt-2">You haven't enrolled in any subjects yet or your enrollment is still pending approval.</p>
                    <a href="enrollment.php" class="inline-block mt-6 bg-blue-600 text-white px-8 py-3 rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        Go to Enrollment
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-8 bg-slate-900 p-6 rounded-3xl text-white flex items-center gap-4">
            <span class="text-2xl">💡</span>
            <p class="text-xs text-slate-400 leading-relaxed">
                <strong class="text-white">Note:</strong> Classes generally follow a Monday-Friday cycle unless stated otherwise. If you notice overlapping schedules, please report to the Registrar’s Office immediately.
            </p>
        </div>

    </main>

</body>
</html>