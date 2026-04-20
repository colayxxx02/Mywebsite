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
    $code = $_POST['code']; $name = $_POST['name']; $units = $_POST['units'];
    $day = $_POST['day']; $room = $_POST['room']; $start = $_POST['start'];
    $end = $_POST['end']; $instructor = $_POST['instructor'];

    mysqli_query($conn, "INSERT INTO subjects (subject_code, subject_name, units, sched_day, sched_time_start, sched_time_end, instructor, room) 
                         VALUES ('$code', '$name', '$units', '$day', '$start', '$end', '$instructor', '$room')");
    header("Location: course_sched.php?msg=added");
    exit();
}

// 3. EDIT SUBJECT
if (isset($_POST['update_subject'])) {
    $id = $_POST['id']; $code = $_POST['code']; $name = $_POST['name'];
    $units = $_POST['units']; $day = $_POST['day']; $room = $_POST['room'];
    $start = $_POST['start']; $end = $_POST['end']; $instructor = $_POST['instructor'];

    mysqli_query($conn, "UPDATE subjects SET subject_code='$code', subject_name='$name', units='$units', sched_day='$day', 
                         room='$room', sched_time_start='$start', sched_time_end='$end', instructor='$instructor' WHERE id=$id");
    header("Location: course_sched.php?msg=updated");
    exit();
}

// 4. DELETE SUBJECT
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM subjects WHERE id=$id");
    header("Location: course_sched.php?msg=deleted");
    exit();
}

// Fetch Current Settings
$settings_query = mysqli_query($conn, "SELECT * FROM enrollment_settings LIMIT 1");
$es = mysqli_fetch_assoc($settings_query);

// Fetch all subjects
$result = mysqli_query($conn, "SELECT * FROM subjects ORDER BY subject_code ASC");
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

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-2 h-8 bg-blue-600 rounded-full"></div>
                <h2 class="text-xl font-bold text-slate-800">Enrollment Period Settings</h2>
            </div>
            <form action="course_sched.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">Active Semester</label>
                    <input type="text" name="semester" value="<?php echo $es['semester'] ?? ''; ?>" placeholder="e.g. 1st Sem 2026" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $es['start_date'] ?? ''; ?>" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">End Date</label>
                    <input type="date" name="end_date" value="<?php echo $es['end_date'] ?? ''; ?>" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Status</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" class="sr-only peer" <?php echo (isset($es['is_active']) && $es['is_active'] == 1) ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <button type="submit" name="update_enrollment_settings" class="ml-auto bg-slate-900 text-white px-6 py-3 rounded-xl font-bold hover:bg-black transition shadow-lg">
                        Save Config
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Code & Description</th>
                        <th class="px-6 py-4 text-center">Units</th>
                        <th class="px-6 py-4">Schedule</th>
                        <th class="px-6 py-4">Instructor</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50 transition text-sm">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-900"><?php echo $row['subject_code']; ?></span><br>
                            <span class="text-xs text-slate-500"><?php echo $row['subject_name']; ?></span>
                        </td>
                        <td class="px-6 py-4 text-center font-medium"><?php echo $row['units']; ?></td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-bold"><?php echo $row['sched_day']; ?></span><br>
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
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Units</label>
                    <input type="number" name="units" id="units" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Subject Name</label>
                    <input type="text" name="name" id="name" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Day(s)</label>
                    <select name="day" id="day" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
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
                document.getElementById('units').value = data.units;
                document.getElementById('day').value = data.sched_day;
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

        // --- CONFLICT CHECKER FOR ADMIN ---
        function checkAdminConflict() {
            // Get values from form
            const id = document.getElementById('subject_id').value;
            const day = document.getElementById('day').value;
            const start = document.getElementById('start').value;
            const end = document.getElementById('end').value;
            const room = document.getElementById('room').value;
            const instructor = document.getElementById('instructor').value;

            // Simple client-side validation first
            if(!day || !start || !end || !room || !instructor) {
                form.reportValidity();
                return;
            }

            const formData = new URLSearchParams();
            formData.append('id', id);
            formData.append('day', day);
            formData.append('start', start);
            formData.append('end', end);
            formData.append('room', room);
            formData.append('instructor', instructor);
            formData.append('subject_id', 'CHECK_ADMIN'); // Trigger Admin-specific logic in app file

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
                    // If no conflict, proceed to submit form
                    submitForm();
                }
            })
            .catch(err => {
                console.error("Conflict Check Error:", err);
                submitForm(); // Fallback: submit anyway if script fails
            });
        }

        function submitForm() {
            // Create hidden input to simulate button click for PHP
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