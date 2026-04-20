<?php require_once('config/db.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS Portal | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md p-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">HAMPTON <span class="text-blue-600">SIS</span></h1>
            <p class="text-slate-500 text-sm mt-2">Enter your credentials to access the portal</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
            <form action="auth/login_process.php" method="POST" class="space-y-5">
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="bg-red-100 text-red-600 p-3 rounded-lg text-sm text-center">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Student ID or Email</label>
                    <input type="text" name="identifier" placeholder="Enter ID or Email" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Password or Birthday</label>
                    <input type="password" name="password" placeholder="•••••••• or YYYY-MM-DD" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white font-semibold py-3 rounded-xl transition shadow-lg">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center mt-8 text-xs text-slate-400 font-medium">
            &copy; 2026 Hampton University SIS
        </p>
    </div>

</body>
</html>