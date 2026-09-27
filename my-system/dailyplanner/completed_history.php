<?php
require_once 'db.php';
require_once 'avatars.php';
checkLogin();

$user_id     = $_SESSION['user_id'];
$full_name   = $_SESSION['full_name'] ?? 'User';
$avatar_key  = $_SESSION['avatar'] ?? 'man1';
$currentPage = basename($_SERVER['PHP_SELF']);

$date_search   = $_GET['history_date'] ?? '';
$period_filter = $_GET['period'] ?? 'all';

$where_clauses = ["user_id = ?", "status = 'completed'"];
$params        = [$user_id];

if ($period_filter === 'today')       $where_clauses[] = "DATE(schedule_datetime) = CURDATE()";
elseif ($period_filter === 'yesterday') $where_clauses[] = "DATE(schedule_datetime) = SUBDATE(CURDATE(),1)";
elseif ($period_filter === 'week')    $where_clauses[] = "schedule_datetime >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
elseif ($period_filter === 'month')   $where_clauses[] = "schedule_datetime >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";

if ($date_search) { $where_clauses[] = "DATE(schedule_datetime) = ?"; $params[] = $date_search; }

$where_sql = implode(" AND ", $where_clauses);
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE $where_sql ORDER BY schedule_datetime DESC");
$stmt->execute($params);
$history_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

function chUrl($p='', $d=''){
    $params = [];
    if($p !== 'all' && $p !== '') $params['period'] = $p;
    if($d !== '') $params['history_date'] = $d;
    return 'completed_history.php'.(!empty($params)?'?'.http_build_query($params):'');
}
function catIcon($c){ return [
    'personal' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'academic' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'chores'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    'health'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
    'work'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
][$c] ?? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'; }
function priMeta($p){ return ['high'=>['#ef4444','rgba(239,68,68,.15)'],'medium'=>['#f59e0b','rgba(245,158,11,.15)'],'low'=>['#10b981','rgba(16,185,129,.15)']][$p]??['#94a3b8','rgba(148,163,184,.1)']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Completed History – Daily Planner</title>
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

/* PERIOD TABS */
.period-bar{display:flex;gap:.4rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center;}
.p-tab{padding:.45rem 1.1rem;border-radius:8px;background:rgba(255,255,255,.05);color:#94a3b8;text-decoration:none;font-size:.83rem;font-weight:600;white-space:nowrap;transition:all .2s;}
.p-tab.active,.p-tab:hover{background:#5b67f7;color:#fff;}
.date-pick{padding:.45rem .9rem;border-radius:8px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:#fff;font-size:.83rem;outline:none;cursor:pointer;}
.btn-clr{padding:.45rem .85rem;border-radius:8px;border:1px solid rgba(255,255,255,.09);background:transparent;color:#94a3b8;font-size:.8rem;cursor:pointer;text-decoration:none;}
.btn-clr:hover{color:#fff;}

/* TASK CARD */
.task-card{display:flex;align-items:flex-start;gap:.9rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:.95rem 1.2rem;margin-bottom:.55rem;transition:border-color .2s,background .2s;}
.task-card:hover{border-color:rgba(16,185,129,.3);background:rgba(255,255,255,.05);}
.check-icon{width:28px;height:28px;border-radius:8px;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;margin-top:2px;}
.t-body{flex:1;min-width:0;}
.t-name{font-size:.95rem;font-weight:700;color:#94a3b8;text-decoration:line-through;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0 0 .3rem;}
.t-meta{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;font-size:.78rem;color:#64748b;}
.t-badge{padding:.18rem .5rem;border-radius:5px;font-size:.72rem;font-weight:600;display:inline-block;}

/* GROUP HEADER */
.group-header{display:flex;align-items:center;gap:.6rem;margin:1.5rem 0 .7rem;}
.group-header h3{margin:0;font-size:.88rem;font-weight:700;color:#e2e8f0;}
.group-count{background:rgba(16,185,129,.15);color:#34d399;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:20px;}
.group-line{flex:1;height:1px;background:rgba(255,255,255,.05);}

/* EMPTY */
.empty-state{text-align:center;padding:3.5rem 1rem;color:#475569;}
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
            <h1>Completed Tasks</h1>
            <p>Your history of finished activities</p>
        </div>
        <a href="profile.php" class="user-pill">
            <img src="<?=getAvatarUrl($avatar_key,$full_name)?>" alt="avatar">
            <span style="font-weight:700;font-size:.9rem;"><?=htmlspecialchars($full_name)?></span>
        </a>
    </div>

    <!-- PERIOD TABS + DATE PICKER -->
    <form method="GET" style="display:contents;">
    <div class="period-bar">
        <a href="<?=chUrl('all',$date_search)?>"       class="p-tab <?=$period_filter==='all'?'active':''?>">All Time</a>
        <a href="<?=chUrl('today',$date_search)?>"     class="p-tab <?=$period_filter==='today'?'active':''?>">Today</a>
        <a href="<?=chUrl('yesterday',$date_search)?>" class="p-tab <?=$period_filter==='yesterday'?'active':''?>">Yesterday</a>
        <a href="<?=chUrl('week',$date_search)?>"      class="p-tab <?=$period_filter==='week'?'active':''?>">Last Week</a>
        <a href="<?=chUrl('month',$date_search)?>"     class="p-tab <?=$period_filter==='month'?'active':''?>">Last Month</a>
        <?php if($period_filter!=='all'): ?>
            <input type="hidden" name="period" value="<?=htmlspecialchars($period_filter)?>">
        <?php endif; ?>
        <input type="date" name="history_date" value="<?=htmlspecialchars($date_search)?>" class="date-pick" onchange="this.form.submit()" title="Filter by specific date">
        <?php if($date_search): ?>
            <a href="<?=chUrl($period_filter)?>" class="btn-clr" style="display:inline-flex;align-items:center;gap:.3rem;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear date</a>
        <?php endif; ?>
    </div>
    </form>

    <?php if(empty($history_tasks)): ?>
    <div class="empty-state">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <p>No completed tasks found for this period.</p>
        <p><a href="my_tasks.php" style="color:#818cf8;font-size:.82rem;">Go to My Tasks →</a></p>
    </div>
    <?php else:
        // Group by date
        $grouped = [];
        foreach($history_tasks as $t){
            $d = date('Y-m-d', strtotime($t['schedule_datetime']));
            $grouped[$d][] = $t;
        }
        foreach($grouped as $date => $items):
            $today_str = date('Y-m-d');
            $yest_str  = date('Y-m-d', strtotime('-1 day'));
            if($date === $today_str)       $label = 'Today — '.date('F j, Y', strtotime($date));
            elseif($date === $yest_str)    $label = 'Yesterday — '.date('F j, Y', strtotime($date));
            else                           $label = date('l, F j, Y', strtotime($date));
    ?>
    <div class="group-header">
        <h3><?=$label?></h3>
        <span class="group-count"><?=count($items)?> done</span>
        <div class="group-line"></div>
    </div>
    <?php foreach($items as $t):
        [$pc,$pb]=priMeta($t['priority']);
    ?>
    <div class="task-card">
        <div class="check-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="t-body">
            <p class="t-name"><?=htmlspecialchars($t['task_name'])?></p>
            <div class="t-meta">
                <span style="display:inline-flex;align-items:center;gap:3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?=date('h:i A',strtotime($t['schedule_datetime']))?></span>
                <span style="display:inline-flex;align-items:center;"><?=catIcon($t['category'])?><?=ucfirst($t['category'])?></span>
                <span class="t-badge" style="background:<?=$pb?>;color:<?=$pc?>;"><?=ucfirst($t['priority'])?></span>
                <span class="t-badge" style="background:rgba(16,185,129,.12);color:#34d399;display:inline-flex;align-items:center;gap:3px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Completed</span>
            </div>
            <?php if(!empty($t['description'])): ?>
                <p style="font-size:.8rem;color:#64748b;margin:.3rem 0 0;line-height:1.45;"><?=htmlspecialchars($t['description'])?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endforeach; endif; ?>

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
