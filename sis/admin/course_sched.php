<?php 
require_once('../config/db.php'); 

// Security Check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../index.php"); 
    exit(); 
}

// --- BACKEND LOGIC ---

// 1. UPDATE ENROLLMENT SETTINGS
if (isset($_POST['update_enrollment_settings'])) {
    $sem   = mysqli_real_escape_string($conn, $_POST['semester']);
    $start = $_POST['start_date'];
    $end   = $_POST['end_date'];
    $active = isset($_POST['is_active']) ? 1 : 0;

    $check = mysqli_query($conn, "SELECT id FROM enrollment_settings LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE enrollment_settings SET semester='$sem', start_date='$start', end_date='$end', is_active='$active' WHERE id=1");
    } else {
        mysqli_query($conn, "INSERT INTO enrollment_settings (semester, start_date, end_date, is_active) VALUES ('$sem', '$start', '$end', '$active')");
    }
    header("Location: course_sched.php?msg=settings_updated");
    exit();
}

// 2. ADD SUBJECT
if (isset($_POST['add_subject'])) {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $year = mysqli_real_escape_string($conn, $_POST['year_level']);
    $units = $_POST['units'];
    $day = $_POST['day']; 
    $room = mysqli_real_escape_string($conn, $_POST['room']); 
    $start = $_POST['start'];
    $end = $_POST['end']; 
    $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);

    $query = "INSERT INTO subjects (subject_code, subject_name, year_level, units, sched_day, sched_time_start, sched_time_end, instructor, room) 
              VALUES ('$code', '$name', '$year', '$units', '$day', '$start', '$end', '$instructor', '$room')";
    
    mysqli_query($conn, $query);
    header("Location: course_sched.php?msg=added");
    exit();
}

// 3. EDIT SUBJECT
if (isset($_POST['update_subject'])) {
    $id = intval($_POST['id']);
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $year = mysqli_real_escape_string($conn, $_POST['year_level']);
    $units = $_POST['units']; 
    $day = $_POST['day']; 
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $start = $_POST['start']; 
    $end = $_POST['end']; 
    $instructor = mysqli_real_escape_string($conn, $_POST['instructor']);

    $query = "UPDATE subjects SET subject_code='$code', subject_name='$name', year_level='$year', units='$units', sched_day='$day', 
              room='$room', sched_time_start='$start', sched_time_end='$end', instructor='$instructor' WHERE id=$id";

    mysqli_query($conn, $query);
    header("Location: course_sched.php?msg=updated");
    exit();
}

// 4. DELETE SUBJECT
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM subjects WHERE id=$id");
    header("Location: course_sched.php?msg=deleted");
    exit();
}

$settings_query = mysqli_query($conn, "SELECT * FROM enrollment_settings LIMIT 1");
$es = mysqli_fetch_assoc($settings_query);
$result = mysqli_query($conn, "SELECT * FROM subjects ORDER BY year_level ASC, subject_code ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course & Schedule | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex text-slate-800">
    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Course & Schedule</h1>
                <p class="text-slate-500 text-sm">Manage curriculum subjects and enrollment periods.</p>
            </div>
            <button onclick="openModal('add')" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                + New Subject
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Year Level</th>
                        <th class="px-6 py-4">Code & Description</th>
                        <th class="px-6 py-4 text-center">Units</th>
                        <th class="px-6 py-4">Schedule</th>
                        <th class="px-6 py-4">Instructor</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50 transition text-sm">
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-bold border border-blue-100 uppercase">
                                    <?php echo !empty($row['year_level']) ? $row['year_level'] : 'N/A'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-900"><?php echo $row['subject_code']; ?></span><br>
                                <span class="text-xs text-slate-500"><?php echo $row['subject_name']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-center font-medium"><?php echo $row['units']; ?></td>
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold"><?php echo $row['sched_day']; ?></span><br>
                                <span class="text-xs text-slate-500">
                                    <?php echo date("h:i A", strtotime($row['sched_time_start'])); ?> - <?php echo date("h:i A", strtotime($row['sched_time_end'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-700"><?php echo $row['instructor']; ?></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-tighter">Room: <?php echo $row['room']; ?></div>
                            </td>
                            <td class="px-6 py-4 text-center space-x-3">
                                <button onclick='openModal("edit", <?php echo json_encode($row); ?>)' class="text-blue-600 font-bold hover:underline">Edit</button>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this subject?')" class="text-red-500 font-bold hover:underline">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="subjectModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 id="modalTitle" class="text-2xl font-bold">Add Subject</h2>
                <button onclick="closeModal()" class="text-slate-400 text-3xl hover:text-slate-600">&times;</button>
            </div>

            <form id="subjectForm" action="course_sched.php" method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="id" id="subject_id">
                
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Subject Code</label>
                    <input type="text" name="code" id="code" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Year Level</label>
                    <select name="year_level" id="year_level" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <div class="col-span-2 md:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Units</label>
                    <input type="number" name="units" id="units" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Subject Name</label>
                    <input type="text" name="name" id="name" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Day(s)</label>
                    <select name="day" id="day" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="MTH">MTH (Mon & Thu)</option>
                        <option value="TF">TF (Tue & Fri)</option>
                        <option value="WED">WED (Wednesday)</option>
                        <option value="SAT">SAT (Saturday)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Room</label>
                    <input type="text" name="room" id="room" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Start</label>
                    <input type="time" name="start" id="start" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">End</label>
                    <input type="time" name="end" id="end" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Instructor</label>
                    <input type="text" name="instructor" id="instructor" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>

                <div class="col-span-2 flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-6 py-2 text-slate-400 font-bold hover:text-slate-600 transition">Cancel</button>
                    <button type="button" id="submitBtn" onclick="checkAdminConflict()" class="bg-slate-900 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg hover:bg-black transition">
                        Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('subjectModal');
        const title = document.getElementById('modalTitle');
        const btn = document.getElementById('submitBtn');
        const form = document.getElementById('subjectForm');

        let currentMode = 'add';

        function openModal(mode, data = null) {
            currentMode = mode;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            if (mode === 'edit') {
                title.innerText = "Edit Subject";
                btn.innerText = "Update Subject";
                
                document.getElementById('subject_id').value = data.id;
                document.getElementById('code').value = data.subject_code;
                document.getElementById('name').value = data.subject_name;
                document.getElementById('year_level').value = data.year_level;
                document.getElementById('units').value = data.units;
                document.getElementById('day').value = data.sched_day; // Will match MTH, TF, etc.
                document.getElementById('room').value = data.room;
                document.getElementById('start').value = data.sched_time_start;
                document.getElementById('end').value = data.sched_time_end;
                document.getElementById('instructor').value = data.instructor;
            } else {
                title.innerText = "Add New Subject";
                btn.innerText = "Save Subject";
                document.getElementById('subject_id').value = "";
                form.reset();
            }
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function checkAdminConflict() {
            if(!form.checkValidity()){
                form.reportValidity();
                return;
            }

            const id = document.getElementById('subject_id').value;
            const day = document.getElementById('day').value;
            const start = document.getElementById('start').value;
            const end = document.getElementById('end').value;
            const room = document.getElementById('room').value;
            const instructor = document.getElementById('instructor').value;

            const formData = new URLSearchParams();
            formData.append('id', id);
            formData.append('day', day);
            formData.append('start', start);
            formData.append('end', end);
            formData.append('room', room);
            formData.append('instructor', instructor);
            formData.append('subject_id', 'CHECK_ADMIN');

            fetch('../api/check_conflict.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'conflict') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Schedule Conflict!',
                        text: data.message,
                        confirmButtonColor: '#1e293b'
                    });
                } else {
                    submitForm();
                }
            })
            .catch(err => {
                console.error("Conflict Check Error:", err);
                submitForm();
            });
        }

        function submitForm() {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = (currentMode === 'edit') ? 'update_subject' : 'add_subject';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);
            form.submit();
        }
    </script>
</body>
</html>