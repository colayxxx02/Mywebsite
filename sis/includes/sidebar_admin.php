<?php
// Mokuha sa ngalan sa file nga gi-access (pananglitan: manage_students.php)
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function para sa styling
function nav_item($link, $icon, $label, $current_page) {
    // I-check kung ang link mao ba ang current page
    $isActive = ($current_page == $link);
    
    // Kung active, gamita ang blue style. Kung dili, gamita ang normal style.
    $class = $isActive 
        ? "bg-blue-600 text-white shadow-lg shadow-blue-900/50" 
        : "text-slate-300 hover:bg-slate-800 hover:text-white";

    return "
    <a href='$link' class='flex items-center p-3 rounded-xl transition-all duration-200 $class'>
        <span class='mr-3 text-lg'>$icon</span> 
        <span class='font-medium text-sm'>$label</span>
    </a>";
}
?>

<div class="w-64 h-screen bg-slate-900 text-white fixed shadow-xl flex flex-col">
    <div class="p-6 border-b border-slate-800">
        <h1 class="text-xl font-bold tracking-wider">HAMPTON <span class="text-blue-400">SIS</span></h1>
        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-[0.2em] font-semibold">Admin Panel</p>
    </div>
    
    <nav class="mt-6 px-4 space-y-2 flex-1 overflow-y-auto">
        <?php 
            echo nav_item("dashboard.php", "📊", "Dashboard", $current_page);
            echo nav_item("manage_students.php", "👥", "Manage Students", $current_page);
            
            // BAG-O NGA MENU: Manage Courses
            echo nav_item("manage_courses.php", "🎓", "Manage Courses", $current_page);
            
            echo nav_item("course_sched.php", "📚", "Course & Schedule", $current_page);
            echo nav_item("enrollments.php", "📝", "Manage Enrollments", $current_page);
            echo nav_item("clearance.php", "🛡️", "Clearance", $current_page);
            echo nav_item("announcements.php", "📢", "Announcements", $current_page);
        ?>
    </nav>

    <div class="p-4 border-t border-slate-800 bg-slate-900">
        <a href="../auth/logout.php" class="flex items-center p-3 text-red-400 hover:bg-red-900/30 rounded-xl transition-all duration-200 group">
            <span class="mr-3 text-lg group-hover:scale-110 transition-transform">🚪</span> 
            <span class="font-bold text-sm">Logout</span>
        </a>
    </div>
</div>