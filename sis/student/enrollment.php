<?php 
require_once('../config/db.php'); 

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// --- BACKEND ACTIONS ---
if (isset($_POST['add_to_cart'])) {
    $subject_id = $_POST['subject_id'];
    $check = mysqli_query($conn, "SELECT id FROM enrollments WHERE student_id = '$student_id' AND subject_id = '$subject_id'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO enrollments (student_id, subject_id, status) VALUES ('$student_id', '$subject_id', 'Cart')");
        header("Location: enrollment.php?msg=added");
    }
    exit();
}

if (isset($_GET['remove'])) {
    $enroll_id = $_GET['remove'];
    mysqli_query($conn, "DELETE FROM enrollments WHERE id = $enroll_id AND status = 'Cart'");
    header("Location: enrollment.php?msg=removed");
    exit();
}

if (isset($_POST['finalize_enrollment'])) {
    mysqli_query($conn, "UPDATE enrollments SET status = 'Pending', enrolled_at = NOW() WHERE student_id = '$student_id' AND status = 'Cart'");
    mysqli_query($conn, "UPDATE students SET enrollment_status = 'Pending' WHERE student_id = '$student_id'");
    header("Location: enrollment.php?msg=success");
    exit();
}

// --- DATA FETCHING ---
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$student_id'"));
$available_subjects = mysqli_query($conn, "SELECT * FROM subjects 
    WHERE id NOT IN (SELECT subject_id FROM enrollments WHERE student_id = '$student_id') 
    ORDER BY year_level ASC, subject_code ASC");
$cart_items = mysqli_query($conn, "SELECT e.id as enroll_id, s.* FROM enrollments e JOIN subjects s ON e.subject_id = s.id WHERE e.student_id = '$student_id' AND e.status = 'Cart'");
$cart_count = mysqli_num_rows($cart_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Portal | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white border-b border-slate-200 px-8 py-4 sticky top-0 z-30 flex justify-between items-center shadow-sm">
        <div>
            <h1 class="text-xl font-black tracking-tighter text-blue-600">HAMPTON <span class="text-slate-900">SIS</span></h1>
        </div>
        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">Clearance Status</p>
                <p class="text-xs font-black <?php echo ($student['status'] == 'Cleared') ? 'text-green-600' : 'text-rose-500'; ?>">
                    <?php echo strtoupper($student['status']); ?>
                </p>
            </div>
            <button onclick="toggleModal()" class="relative bg-slate-900 text-white p-3 rounded-2xl hover:bg-blue-600 transition shadow-xl shadow-slate-200">
                <i class="fas fa-shopping-cart"></i>
                <?php if($cart_count > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-rose-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </button>
            <a href="dashboard.php" class="text-slate-400 hover:text-slate-900 transition"><i class="fas fa-times text-xl"></i></a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-900">Enrollment Portal</h2>
            <p class="text-slate-500">Select subjects you wish to enroll for this semester.</p>
        </div>

        <?php if ($student['status'] == 'Cleared'): ?>
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200 uppercase text-[10px] font-black text-slate-400 tracking-widest">
                        <tr>
                            <th class="px-6 py-4">Year</th>
                            <th class="px-6 py-4">Code</th>
                            <th class="px-6 py-4">Subject Description</th>
                            <th class="px-6 py-4 text-center">Units</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php while($sub = mysqli_fetch_assoc($available_subjects)): ?>
                        <tr class="hover:bg-blue-50/30 transition group">
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-lg text-[10px] font-bold">YR <?php echo $sub['year_level']; ?></span>
                            </td>
                            <td class="px-6 py-4 font-bold text-blue-600 text-sm"><?php echo $sub['subject_code']; ?></td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-800"><?php echo $sub['subject_name']; ?></p>
                                <p class="text-[10px] text-slate-400"><?php echo $sub['instructor'] ?? 'TBA'; ?> • <?php echo $sub['sched_day'] ?? 'TBA'; ?></p>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-sm"><?php echo $sub['units']; ?></td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="checkConflict(<?php echo $sub['id']; ?>)" class="bg-slate-100 text-slate-900 p-2.5 rounded-xl hover:bg-blue-600 hover:text-white transition group-hover:scale-110 transform">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-white p-20 rounded-3xl border-2 border-dashed border-rose-200 text-center">
                <i class="fas fa-lock text-4xl text-rose-200 mb-4"></i>
                <p class="text-slate-500 font-medium">Your enrollment is currently <span class="text-rose-600 font-bold">LOCKED</span>.</p>
                <p class="text-slate-400 text-sm">Settle your office clearances to view available subjects.</p>
            </div>
        <?php endif; ?>
    </main>

    <div id="cartModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-slate-900 opacity-50" onclick="toggleModal()"></div>
        
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-3xl shadow-2xl z-50 overflow-y-auto">
            <div class="modal-content py-6 text-left px-8">
                <div class="flex justify-between items-center pb-6 border-b border-slate-100">
                    <p class="text-xl font-bold">Selected Subjects</p>
                    <div class="modal-close cursor-pointer z-50" onclick="toggleModal()">
                        <i class="fas fa-times text-slate-400 hover:text-slate-900"></i>
                    </div>
                </div>

                <div class="py-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <?php if($cart_count > 0): ?>
                        <?php 
                        $total_units = 0;
                        while($item = mysqli_fetch_assoc($cart_items)): 
                            $total_units += $item['units'];
                        ?>
                        <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div>
                                <p class="font-bold text-sm text-slate-800"><?php echo $item['subject_name']; ?></p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter"><?php echo $item['subject_code']; ?> • <?php echo $item['units']; ?> Units</p>
                            </div>
                            <a href="?remove=<?php echo $item['enroll_id']; ?>" class="text-rose-400 hover:text-rose-600 transition text-lg"><i class="fas fa-trash-can text-sm"></i></a>
                        </div>
                        <?php endwhile; ?>

                        <div class="pt-4 flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Total Load</span>
                            <span class="text-xl font-black text-blue-600"><?php echo $total_units; ?> <span class="text-xs">Units</span></span>
                        </div>

                        <form action="" method="POST" class="mt-8">
                            <button type="submit" name="finalize_enrollment" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold shadow-xl shadow-blue-100 hover:bg-blue-700 transition transform active:scale-95">
                                ENROLL NOW
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-10">
                            <i class="fas fa-shopping-basket text-4xl text-slate-100 mb-3"></i>
                            <p class="text-slate-400 text-sm italic">Your cart is empty.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleModal() {
            const body = document.querySelector('body');
            const modal = document.querySelector('#cartModal');
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            body.classList.toggle('modal-active');
        }

        function checkConflict(subjectId) {
            const formData = new URLSearchParams();
            formData.append('subject_id', subjectId);

            fetch('../api/check_conflict.php', {
                method: 'POST',
                body: formData,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'conflict') {
                    Swal.fire({ icon: 'warning', title: 'Schedule Conflict!', text: data.message, confirmButtonColor: '#1e293b' });
                } else {
                    submitAddToCart(subjectId);
                }
            });
        }

        function submitAddToCart(subjectId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'enrollment.php';
            const subInput = document.createElement('input');
            subInput.type = 'hidden'; subInput.name = 'subject_id'; subInput.value = subjectId;
            const btnInput = document.createElement('input');
            btnInput.type = 'hidden'; btnInput.name = 'add_to_cart'; btnInput.value = '1';
            form.appendChild(subInput); form.appendChild(btnInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>