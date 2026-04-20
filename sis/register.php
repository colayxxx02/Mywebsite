<?php require_once('config/db.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Registration | Hampton SIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4">

    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-800">Create Demo Accounts</h1>
            <p class="text-slate-500">Register admin and student accounts for testing</p>
            <a href="index.php" class="text-blue-600 hover:underline text-sm inline-block mt-2">← Back to Login</a>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
                    <span class="bg-slate-800 text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">A</span>
                    Register Admin
                </h2>
                <form action="auth/register_process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="type" value="admin">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Full Name</label>
                        <input type="text" name="fullname" placeholder="Juan Dela Cruz" required
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="admin@sis.com" required
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Password</label>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-slate-800 outline-none">
                    </div>
                    <button type="submit" class="w-full bg-slate-800 text-white py-3 rounded-xl font-semibold hover:bg-black transition">Create Admin Account</button>
                </form>
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
                    <span class="bg-blue-600 text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">S</span>
                    Register Student
                </h2>
                <form action="auth/register_process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="type" value="student">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Full Name</label>
                            <input type="text" name="fullname" placeholder="Maria Clara" required
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Student ID</label>
                            <input type="text" name="student_id" placeholder="2024-0001" required
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Birthday</label>
                            <input type="date" name="birthday" required
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Course</label>
                            <input type="text" name="course" placeholder="BS Information Technology" required
                                class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">Create Student Account</button>
                </form>
            </div>

        </div>
    </div>

</body>
</html>