<?php
require_once 'db.php';
require_once 'avatars.php';
checkLogin();

$user_id    = $_SESSION['user_id'];
$full_name  = $_SESSION['full_name'] ?? 'User';
$first_name = explode(' ', trim($full_name))[0];
$avatar_key = $_SESSION['avatar'] ?? 'man1';
$today      = date('Y-m-d');
$currentPage = basename($_SERVER['PHP_SELF']);

// ── TODAY'S TASKS (with category filter) ─────────────────────
$selected_category = $_GET['category'] ?? 'all';
$today_params = [$user_id, $today];
if ($selected_category !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? AND DATE(schedule_datetime)=? AND category=? ORDER BY created_at DESC");
    $today_params[] = $selected_category;
} else {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? AND DATE(schedule_datetime)=? ORDER BY created_at DESC");
}
$stmt->execute($today_params);
$today_tasks = $stmt->fetchAll();

// ── COUNTS ───────────────────────────────────────────────────
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=? AND DATE(schedule_datetime)=?");
$count_stmt->execute([$user_id, $today]);
$task_count = $count_stmt->fetchColumn();

$done_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=? AND DATE(schedule_datetime)=? AND status='completed'");
$done_stmt->execute([$user_id, $today]);
$completed_count = $done_stmt->fetchColumn();

$pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=? AND DATE(schedule_datetime)=? AND status='pending'");
$pending_stmt->execute([$user_id, $today]);
$pending_count = $pending_stmt->fetchColumn();

$progress_pct = $task_count > 0 ? round(($completed_count / $task_count) * 100) : 0;

// ── CALENDAR ─────────────────────────────────────────────────
$month           = date('m');
$year            = date('Y');
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays      = date('t', $firstDayOfMonth);
$dayOfWeek       = getdate($firstDayOfMonth)['wday'];
$monthName       = date('F Y');

$badge_stmt = $pdo->prepare("SELECT DAY(schedule_datetime) as d, COUNT(*) as cnt FROM tasks WHERE user_id=? AND YEAR(schedule_datetime)=? AND MONTH(schedule_datetime)=? GROUP BY DAY(schedule_datetime)");
$badge_stmt->execute([$user_id, $year, $month]);
$task_days = [];
foreach ($badge_stmt->fetchAll() as $r) $task_days[(int)$r['d']] = (int)$r['cnt'];

// ── UPCOMING (next 5 non-today tasks) ────────────────────────
$up_stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? AND DATE(schedule_datetime)>? AND status IN('pending','in_progress') ORDER BY schedule_datetime ASC LIMIT 5");
$up_stmt->execute([$user_id, $today]);
$upcoming = $up_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard – Daily Planner</title>
<link rel="stylesheet" href="style.css">
<style>
*,*::before,*::after{box-sizing:border-box;}
html,body{margin:0;padding:0;background:#0b0f19;color:#fff;font-family:'Segoe UI',sans-serif;min-height:100vh;}
.app-layout{display:flex;min-height:100vh;}
.main-content{flex:1;padding:2rem 2.25rem;overflow-y:auto;}

/* TOP BAR */
.top-bar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.75rem;gap:1rem;flex-wrap:wrap;}
.greeting h1{margin:0;font-size:2rem;font-weight:800;letter-spacing:-0.5px;}
.greeting h1 .hi{color:#5b67f7;}
.greeting .sub{margin:.3rem 0 0;color:#64748b;font-size:.95rem;}
.greeting .sub span{color:#10b981;font-weight:700;}
.user-pill{display:flex;align-items:center;gap:.65rem;text-decoration:none;color:inherit;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);padding:.5rem 1rem .5rem .5rem;border-radius:30px;}
.user-pill img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #5b67f7;}
.user-pill span{font-weight:700;font-size:.9rem;}

/* STAT CARDS */
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem;}
@media(max-width:700px){.stat-row{grid-template-columns:1fr 1fr;}}
.stat-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.1rem 1.3rem;display:flex;align-items:center;gap:1rem;}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;}
.stat-num{font-size:1.6rem;font-weight:800;line-height:1;color:var(--sc);}
.stat-lbl{font-size:.78rem;color:#64748b;margin-top:.2rem;}

/* PROGRESS BAR */
.progress-wrap{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.1rem 1.3rem;margin-bottom:1.75rem;}
.progress-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:.65rem;}
.progress-top span{font-size:.85rem;color:#94a3b8;}
.progress-top strong{font-size:.95rem;}
.progress-bar-bg{height:8px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden;}
.progress-bar-fill{height:100%;background:linear-gradient(90deg,#5b67f7,#818cf8);border-radius:99px;transition:width .5s;}

/* GRID */
.dashboard-grid{display:grid;grid-template-columns:1fr 320px;gap:1.5rem;}
@media(max-width:960px){.dashboard-grid{grid-template-columns:1fr;}}

/* CARD BOX */
.card-box{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.5rem;}
.card-box h2{margin:0 0 1.1rem;font-size:1.1rem;font-weight:700;}

/* CATEGORY TABS */
.cat-tabs{display:flex;gap:.4rem;margin-bottom:1.1rem;overflow-x:auto;padding-bottom:.2rem;}
.cat-tab{padding:.38rem .9rem;border-radius:8px;background:rgba(255,255,255,.05);color:#94a3b8;text-decoration:none;font-size:.82rem;font-weight:600;white-space:nowrap;transition:all .2s;display:inline-flex;align-items:center;gap:.3rem;}
.cat-tab.active,.cat-tab:hover{background:#5b67f7;color:#fff;}

/* TASK CARD (in dashboard) */
.d-task-card{display:flex;align-items:center;gap:.85rem;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:12px;padding:.8rem 1rem;margin-bottom:.5rem;transition:border-color .2s;}
.d-task-card:hover{border-color:rgba(91,103,247,.35);}
.d-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
.d-task-body{flex:1;min-width:0;}
.d-task-name{font-size:.9rem;font-weight:700;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.d-task-name.done-name{text-decoration:line-through;color:#475569;}
.d-task-meta{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.25rem;}
.d-badge{padding:.18rem .5rem;border-radius:5px;font-size:.72rem;font-weight:600;}
.d-time{font-size:.78rem;color:#64748b;}

/* PAGINATION */
.pg-bar{display:flex;align-items:center;justify-content:flex-end;gap:.5rem;margin-top:.85rem;}
.pg-bar button{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.09);color:#cbd5e1;padding:.3rem .8rem;border-radius:7px;font-size:.8rem;font-weight:600;cursor:pointer;transition:background .2s;}
.pg-bar button:hover:not(:disabled){background:#5b67f7;border-color:#5b67f7;color:#fff;}
.pg-bar button:disabled{opacity:.3;cursor:default;}
.pg-bar span{font-size:.8rem;color:#64748b;}

/* SEARCH */
.search-wrap{position:relative;margin-bottom:1rem;}
.search-wrap svg{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#64748b;pointer-events:none;}
.search-in{width:100%;padding:.6rem 1rem .6rem 2.4rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:#fff;font-size:.88rem;outline:none;}
.search-in:focus{border-color:#5b67f7;}
.search-in::placeholder{color:#64748b;}

/* EMPTY STATE */
.empty-d{text-align:center;padding:2rem 1rem;color:#475569;}
.empty-d p{margin:.4rem 0 0;font-size:.85rem;}

/* CALENDAR */
.cal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;}
.cal-header h3{margin:0;font-size:1rem;font-weight:700;}
.btn-add-cal{background:#5b67f7;color:#fff;border:none;padding:.5rem 1rem;border-radius:8px;cursor:pointer;font-weight:700;font-size:.82rem;display:flex;align-items:center;gap:.3rem;text-decoration:none;}
.btn-add-cal:hover{background:#4c56e0;}
.cal-table{width:100%;border-collapse:collapse;text-align:center;margin-top:.5rem;}
.cal-table th{padding:7px;color:#475569;font-size:.78rem;font-weight:600;}
.cal-table td{padding:8px;font-size:.82rem;color:#94a3b8;border-radius:6px;cursor:default;position:relative;}
.cal-table td.today{background:#5b67f7;color:#fff;font-weight:800;border-radius:8px;}
.cal-table td.has-task{color:#e2e8f0;}
.cal-badge{position:absolute;bottom:2px;left:50%;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:#5b67f7;}
.cal-table td.today .cal-badge{background:rgba(255,255,255,.8);}

/* UPCOMING */
.up-item{display:flex;align-items:center;gap:.75rem;padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.04);}
.up-item:last-child{border-bottom:none;}
.up-date-box{background:rgba(91,103,247,.12);border:1px solid rgba(91,103,247,.2);border-radius:8px;padding:.35rem .6rem;text-align:center;flex-shrink:0;min-width:44px;}
.up-date-box .up-day{font-size:1rem;font-weight:800;color:#818cf8;line-height:1;}
.up-date-box .up-mon{font-size:.65rem;color:#64748b;text-transform:uppercase;}
.up-name{font-size:.87rem;font-weight:700;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.up-meta{font-size:.75rem;color:#64748b;margin-top:.15rem;}
</style>
</head>
<body>
<div class="app-layout">

<!-- SIDEBAR -->
<aside style="width:260px;min-width:260px;padding:2rem 1.5rem;display:flex;flex-direction:column;justify-content:space-between;min-height:100vh;background:#0d1322;box-sizing:border-box;">
    <div>
        <div style="margin-bottom:2.5rem;">
            <a href="dashboard.php" style="display:flex;align-items:center;gap:.75rem;text-decoration:none;">
                <div style="width:38px;height:38px;background:#5b67f7;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(91,103,247,.4);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M12 23C16.1421 23 19.5 19.6421 19.5 15.5C19.5 11.5 16 8.5 14.5 5C14 7.5 12 9 10.5 10C9 11 8 12.5 8 15.5C8 16.5 8.3 17.5 9 18.2C8.5 18 8 17.5 7.5 16.8C6.5 15.3 6.5 13.5 7 12C5.2 13.8 4.5 16.3 5 18.8C5.6 21.3 8.2 23 12 23Z"/></svg>
                </div>
                <span style="font-size:1.25rem;font-weight:800;color:#fff;letter-spacing:-.3px;">DailyPlanner</span>
            </a>
        </div>
        <div style="margin-bottom:2rem;"><h1 style="margin:0;font-size:1.5rem;font-weight:800;line-height:1.2;color:#fff;">Start Your<br>Day Be <span style="color:#5b67f7;">Productive</span></h1></div>
        <div style="margin-bottom:1rem;font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">MENU</div>
        <nav style="display:flex;flex-direction:column;gap:.6rem;">
            <a href="dashboard.php" style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='dashboard.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='dashboard.php'?'#5b67f7':'transparent'?>;">
                <span style="display:flex;align-items:center;gap:.8rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Dashboard</span>
                <?php if($currentPage=='dashboard.php'):?><span style="font-size:.75rem;background:rgba(255,255,255,.25);border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;">↗</span><?php endif;?>
            </a>
            <a href="my_tasks.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='my_tasks.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='my_tasks.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>My Tasks</a>
            <a href="task_status.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='task_status.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='task_status.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Task Status</a>
            <a href="completed_history.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='completed_history.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='completed_history.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Completed</a>
        </nav>
    </div>
    <a href="logout.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;color:#ef4444;font-weight:600;font-size:.95rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
</aside>

<?php include 'task_notifier.php'; ?>
<?php include 'confirm_modal.php'; ?>

<!-- MAIN -->
<main class="main-content">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="greeting">
            <h1>Hello, <span class="hi"><?=htmlspecialchars($first_name)?></span> <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;"><path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"/><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"/><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/></svg></h1>
            <p class="sub"><?=date('l, F j, Y')?> &nbsp;·&nbsp; <span><?=$completed_count?></span> of <?=$task_count?> tasks done today</p>
        </div>
        <a href="profile.php" class="user-pill">
            <img src="<?=getAvatarUrl($avatar_key,$full_name)?>" alt="avatar">
            <span><?=htmlspecialchars($full_name)?></span>
        </a>
    </div>

    <!-- MAIN GRID -->
    <div class="dashboard-grid">

        <!-- LEFT: TODAY'S TASKS -->
        <div class="card-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
                <h2 style="margin:0;">Today's Tasks</h2>
                <a href="my_tasks.php" style="font-size:.8rem;color:#818cf8;text-decoration:none;">View all →</a>
            </div>

            <!-- Search -->
            <div class="search-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="dashSearch" oninput="dashFilter()" placeholder="Search tasks…" class="search-in">
            </div>

            <!-- Category tabs -->
            <div class="cat-tabs">
                <a href="dashboard.php" class="cat-tab <?=$selected_category==='all'?'active':''?>">All</a>
                <a href="dashboard.php?category=personal" class="cat-tab <?=$selected_category==='personal'?'active':''?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Personal
                </a>
                <a href="dashboard.php?category=academic" class="cat-tab <?=$selected_category==='academic'?'active':''?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg> Academic
                </a>
                <a href="dashboard.php?category=chores" class="cat-tab <?=$selected_category==='chores'?'active':''?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Chores
                </a>
                <a href="dashboard.php?category=health" class="cat-tab <?=$selected_category==='health'?'active':''?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Health
                </a>
                <a href="dashboard.php?category=work" class="cat-tab <?=$selected_category==='work'?'active':''?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Work
                </a>
            </div>

            <!-- Task list -->
            <div id="taskList">
            <?php if (!empty($today_tasks)):
                $priMeta = function($p){ return [
                    'high'   => ['#ef4444','rgba(239,68,68,.15)'],
                    'medium' => ['#f59e0b','rgba(245,158,11,.15)'],
                    'low'    => ['#10b981','rgba(16,185,129,.15)'],
                ][$p] ?? ['#94a3b8','rgba(148,163,184,.1)'];};
                $stMeta = function($s){ return [
                    'pending'     => ['#f59e0b','rgba(245,158,11,.12)'],
                    'in_progress' => ['#3b82f6','rgba(59,130,246,.12)'],
                    'completed'   => ['#10b981','rgba(16,185,129,.12)'],
                    'canceled'    => ['#ef4444','rgba(239,68,68,.12)'],
                ][$s] ?? ['#94a3b8','rgba(255,255,255,.05)'];};
                foreach ($today_tasks as $t):
                    [$pc,$pb] = $priMeta($t['priority']);
                    [$sc,$sb] = $stMeta($t['status']);
                    $done = $t['status']==='completed';
            ?>
            <div class="d-task-card task-row">
                <div class="d-dot" style="background:<?=$pc?>;"></div>
                <div class="d-task-body">
                    <div class="d-task-name <?=$done?'done-name':''?>"><?=htmlspecialchars($t['task_name'])?></div>
                    <div class="d-task-meta">
                        <span class="d-time"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?=date('h:i A',strtotime($t['schedule_datetime']))?></span>
                        <span class="d-badge" style="background:<?=$pb?>;color:<?=$pc?>;"><?=ucfirst($t['priority'])?></span>
                        <span class="d-badge" style="background:<?=$sb?>;color:<?=$sc?>;"><?=str_replace('_',' ',ucfirst($t['status']))?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-d">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="opacity:.25"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <p>No tasks scheduled for today.</p>
                <p><a href="my_tasks.php" style="color:#818cf8;font-size:.85rem;">Go to My Tasks to add one</a></p>
            </div>
            <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pg-bar" id="dashPg">
                <button id="pgPrev" onclick="pgChange(-1)" disabled>‹ Prev</button>
                <span id="pgInfo"></span>
                <button id="pgNext" onclick="pgChange(1)">Next ›</button>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <!-- CALENDAR -->
            <div class="card-box">
                <div class="cal-header">
                    <div>
                        <h3><?=$monthName?></h3>
                        <small style="color:#64748b;font-size:.78rem;">Today: <?=date('F j, Y')?></small>
                    </div>
                    
                </div>
                <table class="cal-table">
                    <thead><tr><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr></thead>
                    <tbody><tr>
                    <?php
                    if($dayOfWeek>0) echo str_repeat('<td></td>',$dayOfWeek);
                    $cd=1; $dw=$dayOfWeek;
                    while($cd<=$numberDays){
                        if($dw==7){$dw=0;echo '</tr><tr>';}
                        $isT=($cd==date('j'));
                        $hasT=isset($task_days[$cd]);
                        $cls=($isT&&$hasT)?'today has-task':($isT?'today':($hasT?'has-task':''));
                        $bdg=$hasT?'<span class="cal-badge"></span>':'';
                        echo "<td".($cls?" class=\"$cls\"":'').">$cd$bdg</td>";
                        $cd++;$dw++;
                    }
                    if($dw!=7&&$dw!=0) echo str_repeat('<td></td>',7-$dw);
                    ?>
                    </tr></tbody>
                </table>
            </div>

            <!-- UPCOMING TASKS -->
            <?php if(!empty($upcoming)): ?>
            <div class="card-box">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.9rem;">
                    <h2 style="margin:0;font-size:1rem;display:flex;align-items:center;gap:.4rem;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Upcoming</h2>
                    <a href="my_tasks.php" style="font-size:.78rem;color:#818cf8;text-decoration:none;">See all</a>
                </div>
                <?php foreach($upcoming as $u):
                    $priC=['high'=>'#ef4444','medium'=>'#f59e0b','low'=>'#10b981'][$u['priority']]??'#94a3b8';
                ?>
                <div class="up-item">
                    <div class="up-date-box">
                        <div class="up-day"><?=date('j',strtotime($u['schedule_datetime']))?></div>
                        <div class="up-mon"><?=date('M',strtotime($u['schedule_datetime']))?></div>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="up-name"><?=htmlspecialchars($u['task_name'])?></div>
                        <div class="up-meta">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?=date('h:i A',strtotime($u['schedule_datetime']))?> &nbsp;·&nbsp;
                            <span style="color:<?=$priC?>;"><?=ucfirst($u['priority'])?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- end right -->
    </div><!-- end grid -->

</main>
</div>

<script>
// Logout loading
document.querySelector('a[href="logout.php"]').addEventListener('click', function(e) {
    e.preventDefault();
    var href = this.href;
    showConfirm({
        type: 'warning',
        title: 'Logging Out',
        message: 'Are you sure you want to log out of your account?',
        okText: 'Yes, Logout',
        onOk: function() { window.location.href = href; }
    });
});

// Pagination
const PER_PAGE=5;
let curPage=1, allRows=[];
function initPg(){
    allRows=Array.from(document.querySelectorAll('#taskList .task-row'));
    if(allRows.length===0){document.getElementById('dashPg').style.display='none';return;}
    renderPg();
}
function renderPg(){
    const total=allRows.length, pages=Math.max(1,Math.ceil(total/PER_PAGE));
    if(curPage<1)curPage=1; if(curPage>pages)curPage=pages;
    const s=(curPage-1)*PER_PAGE, e=s+PER_PAGE;
    allRows.forEach((r,i)=>{r.style.display=(i>=s&&i<e)?'':'none';});
    document.getElementById('pgInfo').textContent=`${curPage} / ${pages}`;
    document.getElementById('pgPrev').disabled=curPage===1;
    document.getElementById('pgNext').disabled=curPage===pages;
    document.getElementById('dashPg').style.display=pages<=1?'none':'flex';
}
function pgChange(d){curPage+=d;renderPg();}

// Search (disables pagination while typing)
function dashFilter(){
    const q=document.getElementById('dashSearch').value.toLowerCase();
    const rows=document.querySelectorAll('#taskList .task-row');
    if(q){
        rows.forEach(r=>{r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';});
        document.getElementById('dashPg').style.display='none';
    } else {
        allRows=Array.from(rows); curPage=1; renderPg();
    }
}

document.addEventListener('DOMContentLoaded',initPg);
</script>
</body>
</html>
