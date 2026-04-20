<?php 
require_once('../config/db.php'); 

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// --- 1. LOGIC PARA SA DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM announcements WHERE id = $id");
    header("Location: announcements.php?msg=deleted");
    exit();
}

// --- 2. LOGIC PARA SA ADD ---
if (isset($_POST['post_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category = $_POST['category'];
    mysqli_query($conn, "INSERT INTO announcements (title, content, category, created_at) VALUES ('$title', '$content', '$category', NOW())");
    header("Location: announcements.php?msg=posted");
    exit();
}

// --- 3. LOGIC PARA SA UPDATE (EDIT) ---
if (isset($_POST['update_announcement'])) {
    $id = $_POST['announcement_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category = $_POST['category'];
    mysqli_query($conn, "UPDATE announcements SET title='$title', content='$content', category='$category' WHERE id=$id");
    header("Location: announcements.php?msg=updated");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 flex text-slate-800">

    <?php include('../includes/sidebar_admin.php'); ?>

    <main class="ml-64 flex-1 p-8">
        <h1 class="text-3xl font-bold mb-8">Bulletin Board</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <h2 class="font-bold mb-4">New Announcement</h2>
                    <form action="announcements.php" method="POST" class="space-y-4">
                        <input type="text" name="title" placeholder="Title" required class="w-full p-3 rounded-xl border border-slate-200 text-sm">
                        <select name="category" class="w-full p-3 rounded-xl border border-slate-200 text-sm">
                            <option value="General">General</option>
                            <option value="Enrollment">Enrollment</option>
                            <option value="Academic">Academic</option>
                        </select>
                        <textarea name="content" rows="4" placeholder="Message..." required class="w-full p-3 rounded-xl border border-slate-200 text-sm"></textarea>
                        <button type="submit" name="post_announcement" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold">Post Now</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-start">
                        <span class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase"><?php echo $row['category']; ?></span>
                        
                        <div class="flex gap-4">
                            <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="text-blue-600 text-xs font-bold hover:underline">EDIT</button>
                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Sigurado ka i-delete ni?')" class="text-red-500 text-xs font-bold hover:underline">DELETE</a>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mt-2"><?php echo $row['title']; ?></h3>
                    <p class="text-slate-600 text-sm mt-2"><?php echo nl2br($row['content']); ?></p>
                    <div class="text-[10px] text-slate-400 mt-4 italic">Posted: <?php echo $row['created_at']; ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl p-8">
            <h2 class="text-2xl font-bold mb-6">Edit Post</h2>
            <form action="announcements.php" method="POST" class="space-y-4">
                <input type="hidden" name="announcement_id" id="edit_id">
                <input type="text" name="title" id="edit_title" class="w-full p-3 rounded-xl border border-slate-200">
                <select name="category" id="edit_category" class="w-full p-3 rounded-xl border border-slate-200">
                    <option value="General">General</option>
                    <option value="Enrollment">Enrollment</option>
                    <option value="Academic">Academic</option>
                </select>
                <textarea name="content" id="edit_content" rows="5" class="w-full p-3 rounded-xl border border-slate-200"></textarea>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="text-slate-400 font-bold px-4">Cancel</button>
                    <button type="submit" name="update_announcement" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold">Update Post</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_content').value = data.content;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>
</body>
</html>