<?php 
require_once('../config/db.php'); 

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// --- BACKEND ACTIONS ---

// A. Action: Add to Cart (Now called after JS validation)
if (isset($_POST['add_to_cart'])) {
    $subject_id = $_POST['subject_id'];
    
    $check = mysqli_query($conn, "SELECT id FROM enrollments WHERE student_id = '$student_id' AND subject_id = '$subject_id'");
    
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO enrollments (student_id, subject_id, status) VALUES ('$student_id', '$subject_id', 'Cart')");
        header("Location: enrollment.php?msg=added_to_cart");
    } else {
        header("Location: enrollment.php?msg=already_exists");
    }
    exit();
}

// B. Action: Remove from Cart
if (isset($_GET['remove'])) {
    $enroll_id = $_GET['remove'];
    mysqli_query($conn, "DELETE FROM enrollments WHERE id = $enroll_id AND status = 'Cart'");
    header("Location: enrollment.php?msg=removed");
    exit();
}

// C. Action: Finalize Enrollment
if (isset($_POST['finalize_enrollment'])) {
    mysqli_query($conn, "UPDATE enrollments SET status = 'Enrolled', enrolled_at = NOW() WHERE student_id = '$student_id' AND status = 'Cart'");
    mysqli_query($conn, "UPDATE students SET enrollment_status = 'Pending' WHERE student_id = '$student_id'");
    header("Location: enrollment.php?msg=enroll_success");
    exit();
}

// --- DATA FETCHING ---
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$student_id'"));
$available_subjects = mysqli_query($conn, "SELECT * FROM subjects WHERE id NOT IN (SELECT subject_id FROM enrollments WHERE student_id = '$student_id')");
$cart_items = mysqli_query($conn, "SELECT e.id as enroll_id, s.* FROM enrollments e JOIN subjects s ON e.subject_id = s.id WHERE e.student_id = '$student_id' AND e.status = 'Cart'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Portal | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 text-slate-800 p-8">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold">Enrollment Portal</h1>
            <a href="dashboard.php" class="text-blue-600 font-bold hover:underline">← Dashboard</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <span class="bg-blue-600 w-2 h-6 rounded-full"></span>
                    Available Subjects
                </h2>

                <?php if ($student['status'] == 'Cleared'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php while($sub = mysqli_fetch_assoc($available_subjects)): ?>
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <span class="bg-slate-100 text-slate-500 text-[10px] font-bold px-2 py-1 rounded-md uppercase"><?php echo $sub['subject_code']; ?></span>
                                <span class="text-blue-600 font-bold text-sm"><?php echo $sub['units']; ?> Units</span>
                            </div>
                            <h3 class="font-bold text-slate-800 mb-1"><?php echo $sub['subject_name']; ?></h3>
                            <p class="text-xs text-slate-400 mb-4"><?php echo $sub['instructor'] ?? 'TBA'; ?> | <?php echo $sub['sched_day'] ?? 'TBA'; ?></p>
                            
                            <button type="button" onclick="checkConflict(<?php echo $sub['id']; ?>)" class="w-full bg-slate-900 text-white py-2 rounded-xl text-xs font-bold hover:bg-blue-600 transition">
                                + Add to Cart
                            </button>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-20 rounded-3xl border-2 border-dashed border-slate-200 text-center">
                        <p class="text-slate-400 font-medium italic">Your account is currently LOCKED. Please settle your clearance to view subjects.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-3xl shadow-xl border border-slate-100 sticky top-10">
                    <h2 class="text-xl font-bold mb-6 flex items-center justify-between">
                        Your Cart
                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full"><?php echo mysqli_num_rows($cart_items); ?></span>
                    </h2>

                    <div class="space-y-4 mb-8">
                        <?php if(mysqli_num_rows($cart_items) > 0): ?>
                            <?php 
                            $total_units = 0;
                            while($item = mysqli_fetch_assoc($cart_items)): 
                                $total_units += $item['units'];
                            ?>
                            <div class="flex justify-between items-center group">
                                <div>
                                    <p class="font-bold text-sm text-slate-700"><?php echo $item['subject_name']; ?></p>
                                    <p class="text-[10px] text-slate-400"><?php echo $item['units']; ?> Units</p>
                                </div>
                                <a href="?remove=<?php echo $item['enroll_id']; ?>" class="text-red-300 hover:text-red-500 font-bold text-lg">&times;</a>
                            </div>
                            <?php endwhile; ?>
                            
                            <div class="border-t pt-4 mt-4 text-sm">
                                <div class="flex justify-between font-bold">
                                    <span>Total Units:</span>
                                    <span class="text-blue-600"><?php echo $total_units; ?></span>
                                </div>
                            </div>

                            <form action="" method="POST" class="mt-6">
                                <button type="submit" name="finalize_enrollment" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 transition">
                                    Enroll Selected Subjects
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-center text-slate-400 py-10 text-sm">Your cart is empty.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Clearance Status</p>
                        <p class="font-bold <?php echo ($student['status'] == 'Cleared') ? 'text-green-600' : 'text-red-500'; ?>">
                            <?php echo strtoupper($student['status']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function checkConflict(subjectId) {
        const formData = new URLSearchParams();
        formData.append('subject_id', subjectId);

        // Fetch conflict status from the centralized app file
        fetch('../api/check_conflict.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'conflict') {
                Swal.fire({
                    icon: 'error',
                    title: 'Schedule Conflict!',
                    text: data.message,
                    confirmButtonColor: '#1e293b'
                });
            } else if (data.status === 'clear') {
                // If no conflict, proceed to submit to backend
                submitAddToCart(subjectId);
            } else {
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching conflict status:', error);
        });
    }

    // Helper to submit the form programmatically
    function submitAddToCart(subjectId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'enrollment.php';

        const subInput = document.createElement('input');
        subInput.type = 'hidden';
        subInput.name = 'subject_id';
        subInput.value = subjectId;

        const btnInput = document.createElement('input');
        btnInput.type = 'hidden';
        btnInput.name = 'add_to_cart';
        btnInput.value = '1';

        form.appendChild(subInput);
        form.appendChild(btnInput);
        document.body.appendChild(form);
        form.submit();
    }
    </script>

</body>
</html>