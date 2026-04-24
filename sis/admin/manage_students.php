<?php 
require_once('../config/db.php'); 

// Check Admin Login
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- HELPER FUNCTION PARA SA STANDING ---
function getStanding($year) {
    switch($year) {
        case '1': return 'Freshman';
        case '2': return 'Sophomore';
        case '3': return 'Junior';
        case '4': return 'Senior';
        case 'Graduating': return 'Graduating';
        default: return 'N/A';
    }
}

// --- 1. BACKEND LOGIC (CRUD) ---

// ADD STUDENT
if (isset($_POST['add_student'])) {
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $gender = $_POST['gender'];
    $bday = $_POST['birthday'];
    
    // Default values for new students: Uncleared status and Pending enrollment
    $query = "INSERT INTO students (student_id, fullname, course, year_level, gender, birthday, status, enrollment_status) 
              VALUES ('$sid', '$name', '$course', '$year_level', '$gender', '$bday', 'Uncleared', 'Pending')";
    
    if(mysqli_query($conn, $query)) {
        header("Location: manage_students.php?msg=added");
    } else {
        die("Error: " . mysqli_error($conn));
    }
    exit();
}

// UPDATE STUDENT (Full fix for all personal info + enrollment status)
if (isset($_POST['update_student'])) {
    $old_sid    = mysqli_real_escape_string($conn, $_POST['old_student_id']);
    $new_sid    = mysqli_real_escape_string($conn, $_POST['student_id']);
    $name       = mysqli_real_escape_string($conn, $_POST['fullname']);
    $course     = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $gender     = mysqli_real_escape_string($conn, $_POST['gender']);
    $bday       = mysqli_real_escape_string($conn, $_POST['birthday']);
    
    // Bag-ong Personal Fields
    $birthplace  = mysqli_real_escape_string($conn, $_POST['birthplace']);
    $religion    = mysqli_real_escape_string($conn, $_POST['religion']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    $contact     = mysqli_real_escape_string($conn, $_POST['contact']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);

    // Enrollment status (Para mo-update ang Dashboard Enrolled count)
    $enrollment_status = mysqli_real_escape_string($conn, $_POST['enrollment_status'] ?? 'Pending');
    $clearance_status  = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Uncleared');
    
    $query = "UPDATE students SET 
              student_id='$new_sid', 
              fullname='$name', 
              course='$course', 
              year_level='$year_level',
              gender='$gender', 
              birthday='$bday',
              birthplace='$birthplace',
              religion='$religion',
              nationality='$nationality',
              contact='$contact',
              email='$email',
              enrollment_status='$enrollment_status',
              status='$clearance_status'
              WHERE student_id='$old_sid'";

    if(mysqli_query($conn, $query)) {
        header("Location: manage_students.php?msg=updated&student_id=$new_sid");
    } else {
        die("Error Updating Records: " . mysqli_error($conn));
    }
    exit();
}

// SAVE GRADE
if (isset($_POST['save_grade'])) {
    $enrollment_id = intval($_POST['enrollment_id']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);

    $query = "UPDATE enrollments SET grade = '$grade' WHERE id = $enrollment_id";
    
    if(mysqli_query($conn, $query)) {
        header("Location: manage_students.php?msg=grade_updated&student_id=$student_id");
    } else {
        die("SQL ERROR: " . mysqli_error($conn));
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

// Fetch Courses for Dropdown
$course_options = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_alias ASC");
$courses_array = [];
while($c = mysqli_fetch_assoc($course_options)) {
    $courses_array[] = $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .modal-scroll::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Student Management</h1>
                <p class="text-slate-500 text-sm">Update profile, manage grades, and track academic standing.</p>
            </div>
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 transition">
                + Add New Student
            </button>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-blue-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-100 flex items-center gap-2 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Success: <?php echo ucwords(str_replace('_', ' ', $_GET['msg'])); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Student Info</th>
                        <th class="px-6 py-4">Course & Standing</th>
                        <th class="px-6 py-4 text-center">Enrollment Status</th>
                        <th class="px-6 py-4 text-center">Clearance</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50 transition text-sm">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900"><?php echo $row['fullname']; ?></div>
                                <div class="font-mono text-[11px] text-blue-600"><?php echo $row['student_id']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-700 font-semibold uppercase text-[11px]"><?php echo $row['course']; ?></div>
                                <div class="text-[11px] text-slate-500 italic">
                                    <?php echo getStanding($row['year_level'] ?? ''); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold 
                                    <?php echo ($row['enrollment_status'] == 'Enrolled') ? 'bg-blue-100 text-blue-600' : 
                                               (($row['enrollment_status'] == 'Dropped') ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600'); ?>">
                                    <?php echo strtoupper($row['enrollment_status'] ?? 'PENDING'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold <?php echo ($row['status'] == 'Cleared') ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-400'; ?>">
                                    <?php echo strtoupper($row['status'] ?? 'UNCLEARED'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center space-x-3">
                                <button onclick="viewStudent('<?php echo $row['student_id']; ?>')" class="text-blue-600 font-bold hover:underline">View & Grade</button>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this student?')" class="text-red-500 font-bold hover:underline">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="px-6 py-20 text-center text-slate-400 italic">No students registered yet.</td></tr>
                    <?php endif; ?>
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
                    <input type="text" name="student_id" required placeholder="e.g. 2024-0001" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Birthday</label>
                    <input type="date" name="birthday" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Full Name</label>
                    <input type="text" name="fullname" required placeholder="Last Name, First Name M.I." class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Course</label>
                    <select name="course" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                        <option value="">-- Select Course --</option>
                        <?php foreach($courses_array as $c): ?>
                            <option value="<?php echo $c['course_alias']; ?>">
                                <?php echo $c['course_alias']; ?> - <?php echo $c['major']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Year Level</label>
                    <select name="year_level" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 bg-white text-sm">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="Graduating">Graduating</option>
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
            <button onclick="closeModal('viewStudentModal')" class="absolute top-6 right-8 text-slate-400 text-3xl z-20 hover:text-slate-600">&times;</button>
            <div id="studentDetailContent" class="p-8 overflow-y-auto modal-scroll">
                </div>
        </div>
    </div>

    <script>
        // Check if there is a student_id in the URL to automatically open the modal
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
                // Clear URL student_id without refreshing
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