<?php
require_once('../config/db.php');

if(!isset($_GET['student_id'])){
    echo "No student ID provided.";
    exit();
}

$sid = mysqli_real_escape_string($conn, $_GET['student_id']);

// Fetch Student Info
$student_query = mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$sid'");
$student = mysqli_fetch_assoc($student_query);

if(!$student){
    echo "Student not found.";
    exit();
}

// Fetch Enrolled Subjects for Grading
// Gi-join ang enrollments ug subjects table
$enrolled = mysqli_query($conn, "SELECT enrollments.id as eid, subjects.subject_code, subjects.subject_name, subjects.instructor, enrollments.grade 
    FROM enrollments 
    JOIN subjects ON enrollments.subject_id = subjects.id 
    WHERE enrollments.student_id = '$sid'");
?>

<div class="flex border-b border-slate-200 mb-6">
    <button onclick="switchModalTab('personal-info')" id="tab-info" class="py-2 px-4 border-b-2 border-blue-600 text-blue-600 font-semibold text-sm transition-all">Personal Information</button>
    <button onclick="switchModalTab('academic-grades')" id="tab-grades" class="py-2 px-4 border-b-2 border-transparent text-slate-500 font-semibold text-sm hover:text-slate-700 transition-all">Academic & Grades</button>
</div>

<div id="section-personal-info">
    <form action="manage_students.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="old_student_id" value="<?php echo $student['student_id']; ?>">
        
        <div class="col-span-2 bg-blue-50 p-4 rounded-xl mb-2">
            <p class="text-xs font-bold text-blue-600 uppercase">Primary Details</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Student ID</label>
            <input type="text" name="student_id" value="<?php echo $student['student_id']; ?>" required class="w-full p-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Full Name</label>
            <input type="text" name="fullname" value="<?php echo $student['fullname']; ?>" required class="w-full p-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Course</label>
            <input type="text" name="course" value="<?php echo $student['course']; ?>" required class="w-full p-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Major (Optional)</label>
            <input type="text" name="major" value="<?php echo $student['major'] ?? ''; ?>" placeholder="e.g. Programming" class="w-full p-2.5 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="col-span-2 bg-slate-50 p-4 rounded-xl mt-2">
            <p class="text-xs font-bold text-slate-500 uppercase">Other Details</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gender</label>
            <select name="gender" class="w-full p-2.5 rounded-lg border border-slate-200 outline-none">
                <option value="Male" <?php if($student['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if($student['gender'] == 'Female') echo 'selected'; ?>>Female</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Birthday</label>
            <input type="date" name="birthday" value="<?php echo $student['birthday']; ?>" class="w-full p-2.5 rounded-lg border border-slate-200 outline-none">
        </div>

        <div class="col-span-2 flex justify-end pt-4">
            <button type="submit" name="update_student" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 transition">Update Student Info</button>
        </div>
    </form>
</div>

<div id="section-academic-grades" class="hidden">
    <div class="overflow-hidden border border-slate-200 rounded-xl">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 font-bold text-slate-600">
                <tr>
                    <th class="p-4">Subject</th>
                    <th class="p-4">Instructor</th>
                    <th class="p-4 text-center">Grade</th>
                    <th class="p-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(mysqli_num_rows($enrolled) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($enrolled)): ?>
                    <tr>
                        <td class="p-4">
                            <span class="font-bold text-slate-800"><?php echo $row['subject_code']; ?></span><br>
                            <span class="text-xs text-slate-500"><?php echo $row['subject_name']; ?></span>
                        </td>
                        <td class="p-4"><?php echo $row['instructor']; ?></td>
                        <td class="p-4">
                            <form action="manage_students.php" method="POST" class="flex items-center justify-center gap-2">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['eid']; ?>">
                                <input type="hidden" name="student_id" value="<?php echo $sid; ?>">
                                
                                <input type="text" name="grade" value="<?php echo $row['grade']; ?>" 
                                       class="w-20 border rounded-lg p-1.5 text-center font-bold text-blue-600 border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none" 
                                       placeholder="0.00">
                        </td>
                        <td class="p-4 text-center">
                                <button type="submit" name="save_grade" class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-black transition">Save Grade</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="p-10 text-center text-slate-400">No subjects found for this student.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>