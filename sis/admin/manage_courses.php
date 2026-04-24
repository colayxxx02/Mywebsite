<?php 
require_once('../config/db.php'); 

// Add Course Logic
if (isset($_POST['add_course'])) {
    $name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $alias = mysqli_real_escape_string($conn, $_POST['course_alias']);
    $major = mysqli_real_escape_string($conn, $_POST['major']);
    
    mysqli_query($conn, "INSERT INTO courses (course_name, course_alias, major) VALUES ('$name', '$alias', '$major')");
    header("Location: manage_courses.php?msg=added");
}

// Delete Logic
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM courses WHERE id=$id");
    header("Location: manage_courses.php?msg=deleted");
}

$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex text-slate-800">
    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <h1 class="text-3xl font-bold mb-6">Manage Courses & Programs</h1>

        <div class="bg-white p-6 rounded-2xl shadow-sm border mb-8">
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 uppercase">Course Name</label>
                    <input type="text" name="course_name" placeholder="e.g. BS Information Technology" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 uppercase">Alias (Abbreviation)</label>
                    <input type="text" name="course_alias" placeholder="e.g. BSIT" required class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2 uppercase">Major (Optional)</label>
                    <input type="text" name="major" placeholder="e.g. Programming" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none">
                </div>
                <button type="submit" name="add_course" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg">Save Course</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b uppercase text-[10px] font-bold text-slate-400 tracking-widest">
                    <tr>
                        <th class="px-6 py-4">Alias</th>
                        <th class="px-6 py-4">Full Course Name</th>
                        <th class="px-6 py-4">Major</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = mysqli_fetch_assoc($courses)): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-bold text-blue-600"><?php echo $row['course_alias']; ?></td>
                        <td class="px-6 py-4"><?php echo $row['course_name']; ?></td>
                        <td class="px-6 py-4 text-slate-500"><?php echo $row['major']; ?></td>
                        <td class="px-6 py-4 text-center">
                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete course?')" class="text-red-500 font-bold hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>