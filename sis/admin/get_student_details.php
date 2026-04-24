<?php 
require_once('../config/db.php'); 

if(!isset($_GET['student_id'])){
    echo "<p class='p-4 text-red-500 font-bold'>No student ID provided.</p>";
    exit();
}

$sid = mysqli_real_escape_string($conn, $_GET['student_id']);

// Fetch Student Info
$student_query = mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$sid'");
$student = mysqli_fetch_assoc($student_query);

if(!$student){
    echo "<p class='p-4 text-red-500 font-bold'>Student not found.</p>";
    exit();
}

// Fetch Courses for the Dropdown
$course_options = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_alias ASC");

// Fetch Enrolled Subjects for Grading
$enrolled = mysqli_query($conn, "SELECT enrollments.id as eid, subjects.subject_code, subjects.subject_name, subjects.instructor, enrollments.grade 
    FROM enrollments 
    JOIN subjects ON enrollments.subject_id = subjects.id 
    WHERE enrollments.student_id = '$sid'");
?>

<div class="flex border-b border-slate-200 mb-6">
    <button onclick="switchModalTab('personal-info')" id="tab-info" class="py-2 px-6 border-b-2 border-blue-600 text-blue-600 font-bold text-sm transition-all">
        Personal Information
    </button>
    <button onclick="switchModalTab('academic-grades')" id="tab-grades" class="py-2 px-6 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 transition-all">
        Academic & Grades
    </button>
</div>

<div id="section-personal-info">
    <form action="manage_students.php" method="POST" class="space-y-6">
        <input type="hidden" name="old_student_id" value="<?php echo $student['student_id']; ?>">
        
        <div class="bg-blue-50 p-6 rounded-3xl border border-blue-100 shadow-sm">
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-4">Core Information & System Status</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Student ID</label>
                    <input type="text" name="student_id" value="<?php echo $student['student_id']; ?>" required class="w-full p-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-semibold">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Full Name</label>
                    <input type="text" name="fullname" value="<?php echo $student['fullname']; ?>" required class="w-full p-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-semibold">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Course</label>
                    <select name="course" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none bg-white text-sm">
                        <?php 
                        mysqli_data_seek($course_options, 0); 
                        while($c = mysqli_fetch_assoc($course_options)): 
                        ?>
                            <option value="<?php echo $c['course_alias']; ?>" <?php if($student['course'] == $c['course_alias']) echo 'selected'; ?>>
                                <?php echo $c['course_alias']; ?> - <?php echo $c['major']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 text-blue-600">Enrollment Status</label>
                    <select name="enrollment_status" class="w-full p-2.5 rounded-xl border border-blue-200 focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm font-bold text-blue-700">
                        <option value="Pending" <?php if(($student['enrollment_status'] ?? '') == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Enrolled" <?php if(($student['enrollment_status'] ?? '') == 'Enrolled') echo 'selected'; ?>>Enrolled</option>
                        <option value="Dropped" <?php if(($student['enrollment_status'] ?? '') == 'Dropped') echo 'selected'; ?>>Dropped</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Clearance Status</label>
                    <select name="status" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none bg-white text-sm">
                        <option value="Uncleared" <?php if(($student['status'] ?? '') == 'Uncleared') echo 'selected'; ?>>Uncleared</option>
                        <option value="Cleared" <?php if(($student['status'] ?? '') == 'Cleared') echo 'selected'; ?>>Cleared</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Personal Details</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Year Level</label>
                    <select name="year_level" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none bg-white text-sm">
                        <option value="1" <?php if($student['year_level'] == '1') echo 'selected'; ?>>1st Year</option>
                        <option value="2" <?php if($student['year_level'] == '2') echo 'selected'; ?>>2nd Year</option>
                        <option value="3" <?php if($student['year_level'] == '3') echo 'selected'; ?>>3rd Year</option>
                        <option value="4" <?php if($student['year_level'] == '4') echo 'selected'; ?>>4th Year</option>
                        <option value="Graduating" <?php if($student['year_level'] == 'Graduating') echo 'selected'; ?>>Graduating</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Gender</label>
                    <select name="gender" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none bg-white text-sm">
                        <option value="Male" <?php if($student['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($student['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Birthday</label>
                    <input type="date" name="birthday" value="<?php echo $student['birthday']; ?>" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nationality</label>
                    <input type="text" name="nationality" value="<?php echo $student['nationality'] ?? 'Filipino'; ?>" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Birthplace</label>
                    <input type="text" name="birthplace" value="<?php echo $student['birthplace'] ?? ''; ?>" placeholder="City/Province" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Religion</label>
                    <input type="text" name="religion" value="<?php echo $student['religion'] ?? ''; ?>" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm">
                </div>
            </div>
        </div>

        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 shadow-inner">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Contact Information</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Contact Number</label>
                    <input type="text" name="contact" value="<?php echo $student['contact'] ?? ''; ?>" placeholder="09XXXXXXXXX" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm font-mono">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Email Address</label>
                    <input type="email" name="email" value="<?php echo $student['email'] ?? ''; ?>" placeholder="student@example.com" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none text-sm font-medium">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-6 border-t border-slate-100">
            <button type="submit" name="update_student" class="bg-blue-600 text-white px-12 py-3.5 rounded-2xl font-black shadow-xl shadow-blue-200 hover:bg-blue-700 transition transform active:scale-95 text-sm uppercase tracking-wider">
                Save & Update Profile
            </button>
        </div>
    </form>
</div>

<div id="section-academic-grades" class="hidden">
    <div class="overflow-hidden border border-slate-200 rounded-3xl shadow-sm bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 font-bold text-slate-600 uppercase text-[10px] tracking-widest">
                <tr>
                    <th class="p-5">Subject Details</th>
                    <th class="p-5">Instructor</th>
                    <th class="p-5 text-center">Grade Management</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(mysqli_num_rows($enrolled) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($enrolled)): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-5">
                            <span class="font-bold text-blue-600 uppercase text-xs"><?php echo $row['subject_code']; ?></span><br>
                            <span class="text-xs font-semibold text-slate-800"><?php echo $row['subject_name']; ?></span>
                        </td>
                        <td class="p-5 text-slate-500 italic text-xs"><?php echo $row['instructor']; ?></td>
                        <td class="p-5">
                            <form action="manage_students.php" method="POST" class="flex items-center justify-center gap-2">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['eid']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                <input type="text" name="grade" value="<?php echo $row['grade']; ?>" class="w-16 border rounded-xl p-2 text-center font-bold text-blue-600 border-slate-300 outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="0.0">
                                <button type="submit" name="save_grade" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-black transition">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="p-20 text-center">
                            <div class="text-slate-300 text-4xl mb-2 text-center">📚</div>
                            <p class="text-slate-400 italic text-sm">No subjects enrolled for this student.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Ensure the switchModalTab function is accessible if it's not in the main file
    if (typeof switchModalTab !== 'function') {
        window.switchModalTab = function(tab) {
            const infoSec = document.getElementById('section-personal-info');
            const gradeSec = document.getElementById('section-academic-grades');
            const infoTab = document.getElementById('tab-info');
            const gradeTab = document.getElementById('tab-grades');

            if (tab === 'personal-info') {
                infoSec.classList.remove('hidden');
                gradeSec.classList.add('hidden');
                infoTab.className = "py-2 px-6 border-b-2 border-blue-600 text-blue-600 font-bold text-sm transition-all";
                gradeTab.className = "py-2 px-6 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 transition-all";
            } else {
                gradeSec.classList.remove('hidden');
                infoSec.classList.add('hidden');
                gradeTab.className = "py-2 px-6 border-b-2 border-blue-600 text-blue-600 font-bold text-sm transition-all";
                infoTab.className = "py-2 px-6 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 transition-all";
            }
        };
    }
</script>