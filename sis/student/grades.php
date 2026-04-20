<?php 
require_once('../config/db.php'); 

// 1. Security: Check if the user is a logged-in student
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// 2. Fetch Student Personal Information
$student_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, course FROM students WHERE student_id = '$student_id'"));

// 3. Fetch Grades and Subject Details
// We JOIN enrollment_details (e) with subjects (s) to get the names and units
$grades_query = mysqli_query($conn, "
    SELECT s.subject_code, s.subject_name, s.units, e.grade, e.status 
    FROM enrollments e 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.student_id = '$student_id' AND e.status = 'Enrolled'
");

// 4. GPA Calculation Logic
$total_units = 0;
$weighted_sum = 0;
$has_grades = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Grades | Hampton SIS</title>
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
        <div class="flex items-center gap-4">
            <button onclick="window.print()" class="text-xs font-bold bg-slate-100 px-4 py-2 rounded-xl hover:bg-slate-200 transition">Print Report</button>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto mt-10 p-6 pb-20">
        
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 mb-8 flex flex-col md:flex-row justify-between items-center">
            <div class="text-center md:text-left">
                <h1 class="text-3xl font-bold text-slate-900"><?php echo $student_info['fullname']; ?></h1>
                <p class="text-blue-600 font-medium tracking-wide"><?php echo $student_info['course']; ?></p>
                <p class="text-xs text-slate-400 mt-1 font-mono uppercase">Student ID: <?php echo $student_id; ?></p>
            </div>
            <div class="mt-6 md:mt-0 text-center md:text-right bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Current Term</p>
                <p class="font-bold text-slate-700">1st Semester, 2026</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <tr>
                        <th class="px-8 py-5">Subject Details</th>
                        <th class="px-8 py-5 text-center">Units</th>
                        <th class="px-8 py-5 text-center">Final Grade</th>
                        <th class="px-8 py-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($grades_query) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($grades_query)): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-8 py-5">
                                <p class="font-bold text-slate-800"><?php echo $row['subject_name']; ?></p>
                                <p class="text-[10px] font-mono text-blue-500 uppercase"><?php echo $row['subject_code']; ?></p>
                            </td>
                            <td class="px-8 py-5 text-center font-semibold text-slate-600"><?php echo $row['units']; ?></td>
                            <td class="px-8 py-5 text-center">
                                <?php 
                                    $g = $row['grade'];
                                    // Visual color logic
                                    $color = "text-slate-300";
                                    if(is_numeric($g)) {
                                        $has_grades = true;
                                        $total_units += $row['units'];
                                        $weighted_sum += ($g * $row['units']);
                                        $color = ($g <= 3.0) ? "text-green-600 font-bold text-lg" : "text-red-600 font-bold text-lg";
                                    } else if ($g == 'INC') {
                                        $color = "text-orange-500 font-bold";
                                    }
                                ?>
                                <span class="<?php echo $color; ?>">
                                    <?php echo !empty($g) ? $g : '---'; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-md bg-green-50 text-green-600 uppercase">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center text-slate-400 italic">
                                No grades available. You must be officially enrolled to see your subjects here.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-slate-900 text-white p-8 rounded-3xl shadow-xl flex justify-between items-center">
                <div>
                    <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">General Weighted Average</h3>
                    <p class="text-3xl font-bold mt-1">
                        <?php echo ($total_units > 0) ? number_format($weighted_sum / $total_units, 2) : '0.00'; ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center text-2xl">🎓</div>
            </div>

            <div class="bg-white border border-slate-200 p-8 rounded-3xl flex justify-between items-center">
                <div>
                    <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Units Credited</h3>
                    <p class="text-3xl font-bold mt-1 text-slate-800"><?php echo $total_units; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl font-bold">∑</div>
            </div>
        </div>

        <div class="mt-10 flex flex-wrap gap-6 justify-center text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
            <div class="flex items-center gap-2"><span class="w-2 h-2 bg-green-500 rounded-full"></span> 1.0 - 3.0 Passing</div>
            <div class="flex items-center gap-2"><span class="w-2 h-2 bg-red-500 rounded-full"></span> 5.0 Failing</div>
            <div class="flex items-center gap-2"><span class="w-2 h-2 bg-orange-500 rounded-full"></span> INC Incomplete</div>
            <div class="flex items-center gap-2"><span class="w-2 h-2 bg-slate-300 rounded-full"></span> --- Not Yet Encoded</div>
        </div>

    </main>

</body>
</html>