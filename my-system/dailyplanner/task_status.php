<?php
require_once 'db.php';
require_once 'avatars.php';
checkLogin();

$user_id     = $_SESSION['user_id'];
$full_name   = $_SESSION['full_name'] ?? 'User';
$avatar_key  = $_SESSION['avatar'] ?? 'man1';
$currentPage = basename($_SERVER['PHP_SELF']);

$status_filter = $_GET['status'] ?? '';
$filter_date   = $_GET['filter_date'] ?? '';
$search_query  = trim($_GET['search'] ?? '');

$where_clauses = ["user_id = ?"];
$params        = [$user_id];

if (!empty($status_filter) && in_array($status_filter, ['pending','in_progress','completed','canceled'])) {
    $where_clauses[] = "status = ?";
    $params[]        = $status_filter;
}
if (!empty($filter_date)) {
    $where_clauses[] = "DATE(schedule_datetime) = ?";
    $params[]        = $filter_date;
}
if ($search_query !== '') {
    $where_clauses[] = "(task_name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $params[] = "%$search_query%"; $params[] = "%$search_query%"; $params[] = "%$search_query%";
}

$where_sql = implode(" AND ", $where_clauses);
$sql  = "SELECT * FROM tasks WHERE $where_sql ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

function priMeta($p){ return ['high'=>['#ef4444','rgba(239,68,68,.15)'],'medium'=>['#f59e0b','rgba(245,158,11,.15)'],'low'=>['#10b981','rgba(16,185,129,.15)']][$p]??['#94a3b8','rgba(148,163,184,.1)']; }
function stMeta($s){ return [
    'pending'     => ['#f59e0b','rgba(245,158,11,.12)','<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
    'in_progress' => ['#3b82f6','rgba(59,130,246,.12)','<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>'],
    'completed'   => ['#10b981','rgba(16,185,129,.12)','<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'],
    'canceled'    => ['#ef4444','rgba(239,68,68,.12)','<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'],
][$s]??['#94a3b8','rgba(255,255,255,.05)','•']; }
function catIcon($c){ return [
    'personal' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'academic' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'chores'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'health'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
    'work'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
][$c] ?? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Task Status – Daily Planner</title>
<link rel="stylesheet" href="style.css">
<style>
*,*::before,*::after{box-sizing:border-box;}
html,body{margin:0;padding:0;background:#0b0f19;color:#fff;font-family:'Segoe UI',sans-serif;min-height:100vh;}
.app-layout{display:flex;min-height:100vh;}
.main-content{flex:1;padding:2rem 2.25rem;overflow-y:auto;}

/* TOP BAR */
.top-bar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;}
.page-heading h1{margin:0;font-size:1.9rem;font-weight:800;letter-spacing:-.5px;}
.page-heading p{margin:.3rem 0 0;color:#64748b;font-size:.9rem;}
.user-pill{display:flex;align-items:center;gap:.65rem;text-decoration:none;color:inherit;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);padding:.5rem 1rem .5rem .5rem;border-radius:30px;}
.user-pill img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #5b67f7;}

/* FILTER BAR */
.filter-bar{display:flex;gap:.65rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:center;}
.search-wrap{flex:1;min-width:180px;position:relative;}
.search-wrap svg{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#64748b;pointer-events:none;}
.f-input{width:100%;padding:.6rem 1rem .6rem 2.4rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:#fff;font-size:.88rem;outline:none;transition:border-color .2s;}
.f-input:focus{border-color:#5b67f7;}
.f-input::placeholder{color:#64748b;}
.f-plain{padding:.6rem 1rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:#0f1729;color:#fff;font-size:.88rem;outline:none;cursor:pointer;color-scheme:dark;}
.btn-search{padding:.6rem 1.15rem;border-radius:10px;border:none;background:#5b67f7;color:#fff;font-weight:600;font-size:.88rem;cursor:pointer;}
.btn-clear{padding:.6rem .9rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:transparent;color:#94a3b8;font-size:.82rem;cursor:pointer;text-decoration:none;}
.btn-clear:hover{color:#fff;}

/* TASK CARD */
.task-card{display:flex;align-items:flex-start;gap:.9rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:.95rem 1.2rem;margin-bottom:.55rem;transition:border-color .2s,background .2s;}
.task-card:hover{border-color:rgba(91,103,247,.3);background:rgba(255,255,255,.05);}
.pri-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:5px;}
.t-body{flex:1;min-width:0;}
.t-name{font-size:.95rem;font-weight:700;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0 0 .3rem;}
.t-name.canceled-name{text-decoration:line-through;color:#475569;}
.t-name.completed-name{text-decoration:line-through;color:#64748b;}
.t-meta{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;font-size:.78rem;color:#64748b;}
.t-badge{padding:.18rem .5rem;border-radius:5px;font-size:.72rem;font-weight:600;display:inline-block;}
.t-desc{font-size:.8rem;color:#64748b;margin:.3rem 0 0;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

/* SECTION LABEL */
.result-label{font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.75rem;text-transform:uppercase;letter-spacing:.06em;}

/* EMPTY */
.empty-state{text-align:center;padding:3rem 1rem;color:#475569;}
.empty-state svg{opacity:.2;margin-bottom:.75rem;}
.empty-state p{margin:.35rem 0 0;font-size:.9rem;color:#64748b;}
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
            <a href="dashboard.php" style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='dashboard.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='dashboard.php'?'#5b67f7':'transparent'?>;"><span style="display:flex;align-items:center;gap:.8rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Dashboard</span></a>
            <a href="my_tasks.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='my_tasks.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='my_tasks.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>My Tasks</a>
            <a href="task_status.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='task_status.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='task_status.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Task Status</a>
            <a href="completed_history.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.95rem;color:<?=$currentPage=='completed_history.php'?'#fff':'#94a3b8'?>;background:<?=$currentPage=='completed_history.php'?'#5b67f7':'transparent'?>;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Completed</a>
        </nav>
    </div>
    <a href="logout.php" style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.2rem;border-radius:50px;text-decoration:none;color:#ef4444;font-weight:600;font-size:.95rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
</aside>

<?php include 'task_notifier.php'; ?>

<main class="main-content">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-heading">
            <h1>Task Status</h1>
            <p>Overview of all your tasks by status</p>
        </div>
        <a href="profile.php" class="user-pill">
            <img src="<?=getAvatarUrl($avatar_key,$full_name)?>" alt="avatar">
            <span style="font-weight:700;font-size:.9rem;"><?=htmlspecialchars($full_name)?></span>
        </a>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" class="filter-bar">
        <div class="search-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" value="<?=htmlspecialchars($search_query)?>" placeholder="Search tasks…" class="f-input">
        </div>
        <select name="status" class="f-plain">
            <option value="" <?=$status_filter===''?'selected':''?>>All Statuses</option>
            <option value="pending"     <?=$status_filter==='pending'?'selected':''?>>Pending</option>
            <option value="in_progress" <?=$status_filter==='in_progress'?'selected':''?>>In Progress</option>
            <option value="completed"   <?=$status_filter==='completed'?'selected':''?>>Completed</option>
            <option value="canceled"    <?=$status_filter==='canceled'?'selected':''?>>Canceled</option>
        </select>
        <input type="date" name="filter_date" value="<?=htmlspecialchars($filter_date)?>" class="f-plain" title="Filter by date">
        <button type="submit" class="btn-search">Search</button>
        <?php if($search_query||$filter_date||$status_filter): ?>
            <a href="task_status.php" class="btn-clear" style="display:inline-flex;align-items:center;gap:.3rem;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear</a>
        <?php endif; ?>
    </form>

    <!-- RESULTS LABEL -->
    <?php if(!empty($tasks)): ?>
    <div class="result-label"><?=count($tasks)?> task<?=count($tasks)!=1?'s':''?> found</div>
    <?php endif; ?>

    <!-- TASK CARDS -->
    <?php if(empty($tasks)): ?>
    <div class="empty-state">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <p>No tasks found for this filter.</p>
        <p><a href="task_status.php" style="color:#818cf8;font-size:.82rem;">Clear filters</a></p>
    </div>
    <?php else: foreach($tasks as $t):
        [$pc,$pb]=priMeta($t['priority']);
        [$sc,$sb,$si]=stMeta($t['status']);
        $nameCls=$t['status']==='completed'?'completed-name':($t['status']==='canceled'?'canceled-name':'');
    ?>
    <div class="task-card">
        <div class="pri-dot" style="background:<?=$pc?>;"></div>
        <div class="t-body">
            <p class="t-name <?=$nameCls?>"><?=htmlspecialchars($t['task_name'])?></p>
            <div class="t-meta">
                <span style="display:inline-flex;align-items:center;gap:3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?=date('M j, Y',(int)strtotime($t['schedule_datetime']))?></span>
                <span style="display:inline-flex;align-items:center;gap:3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?=date('h:i A',(int)strtotime($t['schedule_datetime']))?></span>
                <span style="display:inline-flex;align-items:center;"><?=catIcon($t['category'])?><?=ucfirst($t['category'])?></span>
                <span class="t-badge" style="background:<?=$pb?>;color:<?=$pc?>;"><?=ucfirst($t['priority'])?></span>
                <span class="t-badge" style="background:<?=$sb?>;color:<?=$sc?>;display:inline-flex;align-items:center;gap:3px;"><?=$si?> <?=str_replace('_',' ',ucfirst($t['status']))?></span>
            </div>
            <?php if(!empty($t['description'])): ?>
                <p class="t-desc"><?=htmlspecialchars($t['description'])?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>

</main>
</div>
<?php include 'confirm_modal.php'; ?>
<script>
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
</script>
</body>
</html>
