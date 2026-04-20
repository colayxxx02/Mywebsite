<?php 
require_once('../config/db.php'); 

// Check Admin Login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- 1. BACKEND LOGIC (CRUD) ---

// ADD STUDENT
if (isset($_POST['add_student'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $gender = $_POST['gender'];
    $bday = $_POST['birthday'];
    
    $query = "INSERT INTO students (student_id, fullname, course, gender, birthday, status, enrollment_status) 
              VALUES ('$sid', '$name', '$course', '$gender', '$bday', 'Uncleared', 'Pending')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: manage_students.php?msg=added");
    } else {
        die("Error: " . mysqli_error($conn));
    }
    exit();
}

// UPDATE STUDENT (Personal Info - Gi-update para sa get_student_details.php form)
if (isset($_POST['update_student'])) {
    $old_sid = mysqli_real_escape_string($conn, $_POST['old_student_id']);
    $new_sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name    = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course  = mysqli_real_escape_string($conn, $_POST['course']);
    $gender  = mysqli_real_escape_string($conn, $_POST['gender']);
    $bday    = mysqli_real_escape_string($conn, $_POST['birthday']);
    
    $query = "UPDATE students SET 
              student_id='$new_sid', 
              fullname='$name', 
              course='$course', 
              gender='$gender', 
              birthday='$bday' 
              WHERE student_id='$old_sid'";

    if(mysqli_query($conn, $query)) {
        header("Location: manage_students.php?msg=updated&student_id=$new_sid");
    } else {
        die("Error: " . mysqli_error($conn));
    }
    exit();
}

// UPDATE STUDENT GRADE (Base sa imong Table: id, student_id, grade)
if (isset($_POST['save_grade'])) {
    $enrollment_id = intval($_POST['enrollment_id']); // primary key 'id'
    $grade         = mysqli_real_escape_string($conn, $_POST['grade']);
    $student_id    = mysqli_real_escape_string($conn, $_POST['student_id']);

    $query = "UPDATE enrollments SET grade = '$grade' WHERE id = $enrollment_id";
    
    if(mysqli_query($conn, $query)) {
        // I-pass ang student_id sa URL aron mo-auto open ang modal human sa refresh
        header("Location: manage_students.php?msg=grade_updated&student_id=$student_id");
    } else {
        die("Error updating grade: " . mysqli_error($conn));
    }
    exit();
}

// DELETE STUDENT
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM students WHERE id=$id");
    header("Location: manage_students.php?msg=deleted");
    exit();
}

// Fetch all students
$result = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Student Management</h1>
                <p class="text-slate-500 text-sm">Centralized control for student records and academics.</p>
            </div>
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 transition">
                + Add New Student
            </button>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-xl text-sm font-bold animate-pulse">
                ✅ Success: <?php echo ucwords(str_replace('_', ' ', $_GET['msg'])); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Student ID</th>
                        <th class="px-6 py-4">Full Name</th>
                        <th class="px-6 py-4">Course</th>
                        <th class="px-6 py-4 text-center">Clearance</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50 transition text-sm">
                        <td class="px-6 py-4 font-mono font-bold text-slate-600"><?php echo $row['student_id']; ?></td>
                        <td class="px-6 py-4 font-semibold text-slate-900"><?php echo $row['fullname']; ?></td>
                        <td class="px-6 py-4"><?php echo $row['course']; ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold <?php echo ($row['status'] == 'Cleared') ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                <?php echo strtoupper($row['status'] ?? 'UNCLEARED'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center space-x-3">
                            <button onclick="viewStudent('<?php echo $row['student_id']; ?>')" class="text-blue-600 font-bold hover:underline">View & Grade</button>
                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this student?')" class="text-red-500 font-bold hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="addStudentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 border border-slate-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Register Student</h2>
                <button onclick="closeModal('addStudentModal')" class="text-slate-400 text-3xl hover:text-slate-600">&times;</button>
            </div>
            <form action="manage_students.php" method="POST" class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Student ID</label>
                    <input type="text" name="student_id" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Birthday</label>
                    <input type="date" name="birthday" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Full Name</label>
                    <input type="text" name="fullname" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Course</label>
                    <input type="text" name="course" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Gender</label>
                    <select name="gender" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-span-2 flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('addStudentModal')" class="px-6 py-2 text-slate-400 font-bold hover:text-slate-600">Cancel</button>
                    <button type="submit" name="add_student" class="bg-slate-900 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-black transition shadow-lg shadow-slate-200">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewStudentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col relative">
            <button onclick="closeModal('viewStudentModal')" class="absolute top-6 right-8 text-slate-400 text-3xl z-10 hover:text-slate-600">&times;</button>
            <div id="studentDetailContent" class="p-8 overflow-y-auto">
                </div>
        </div>
    </div>

    <script>
        // Auto-open modal if student_id is present in URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const studentId = urlParams.get('student_id');
            if (studentId) {
                viewStudent(studentId);
            }
        };

        function openAddModal() {
            document.getElementById('addStudentModal').classList.remove('hidden');
            document.getElementById('addStudentModal').classList.add('flex');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
            if(id === 'viewStudentModal') {
                // Clear URL after closing modal
                window.history.replaceState({}, document.title, "manage_students.php");
            }
        }

        function viewStudent(studentId) {
            const modal = document.getElementById('viewStudentModal');
            const content = document.getElementById('studentDetailContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            content.innerHTML = '<div class="flex flex-col items-center justify-center py-20"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div><p class="font-bold text-slate-400 tracking-widest text-xs uppercase">Fetching Student Records...</p></div>';

            fetch(`get_student_details.php?student_id=${studentId}`)
                .then(response => response.text())
                .then(data => {
                    content.innerHTML = data;
                })
                .catch(err => {
                    content.innerHTML = '<p class="text-center text-red-500 py-10">Error loading data.</p>';
                });
        }

        function switchModalTab(tab) {
            const infoSec = document.getElementById('section-personal-info');
            const gradeSec = document.getElementById('section-academic-grades');
            const infoTab = document.getElementById('tab-info');
            const gradeTab = document.getElementById('tab-grades');

            if (tab === 'personal-info') {
                infoSec.classList.remove('hidden');
                gradeSec.classList.add('hidden');
                infoTab.className = "py-2 px-4 border-b-2 border-blue-600 text-blue-600 font-semibold text-sm";
                gradeTab.className = "py-2 px-4 border-b-2 border-transparent text-slate-500 font-semibold text-sm hover:text-slate-700";
            } else {
                gradeSec.classList.remove('hidden');
                infoSec.classList.add('hidden');
                gradeTab.className = "py-2 px-4 border-b-2 border-blue-600 text-blue-600 font-semibold text-sm";
                infoTab.className = "py-2 px-4 border-b-2 border-transparent text-slate-500 font-semibold text-sm hover:text-slate-700";
            }
        }
    </script>
</body>
</html>