<?php
require_once 'db.php';
require_once 'avatars.php';
checkLogin();

$user_id    = $_SESSION['user_id'];
$full_name  = $_SESSION['full_name'] ?? 'User Name';
$first_name = explode(' ', trim($full_name))[0];
$avatar_key = $_SESSION['avatar'] ?? 'man1';
$currentPage = basename($_SERVER['PHP_SELF']);

// ── SAVE / UPDATE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $task_name   = trim($_POST['task_name']);
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'];
    $schedule    = $_POST['schedule_datetime'];
    $priority    = $_POST['priority'];

    if (!empty($_POST['task_id'])) {
        $status = $_POST['status'] ?? 'pending';
        $stmt = $pdo->prepare("UPDATE tasks SET task_name=?, description=?, category=?, schedule_datetime=?, priority=?, status=? WHERE id=? AND user_id=?");
        $stmt->execute([$task_name, $description, $category, $schedule, $priority, $status, $_POST['task_id'], $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task_name, description, category, schedule_datetime, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $task_name, $description, $category, $schedule, $priority]);
    }
    header('Location: my_tasks.php');
    exit();
}

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    header('Location: my_tasks.php');
    exit();
}

// ── FILTERS ──────────────────────────────────────────────────
$priority_filter = strtolower(trim($_GET['priority'] ?? ''));
$status_filter   = strtolower(trim($_GET['status_filter'] ?? ''));
$filter_date     = $_GET['filter_date'] ?? '';
$search_query    = trim($_GET['search'] ?? '');

$where_clauses = ["user_id = ?"];
$params        = [$user_id];

if (!empty($priority_filter) && in_array($priority_filter, ['high', 'medium', 'low'])) {
    $where_clauses[] = "LOWER(priority) = ?";
    $params[]        = $priority_filter;
}
if (!empty($status_filter) && in_array($status_filter, ['pending', 'in_progress', 'completed', 'canceled'])) {
    $where_clauses[] = "status = ?";
    $params[]        = $status_filter;
}
if ($filter_date) {
    $where_clauses[] = "DATE(schedule_datetime) = ?";
    $params[]        = $filter_date;
}
if ($search_query !== '') {
    $where_clauses[] = "(task_name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $params[]        = "%$search_query%";
    $params[]        = "%$search_query%";
    $params[]        = "%$search_query%";
}

$where_sql = implode(" AND ", $where_clauses);

// ── PAGINATION ───────────────────────────────────────────────
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE $where_sql");
$count_stmt->execute($params);
$total_tasks = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_tasks / $limit));

$query = "SELECT * FROM tasks WHERE $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt  = $pdo->prepare($query);
$stmt->execute($params);
$all_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── SUMMARY COUNTS (all tasks, no filter) ────────────────────
$sum_stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM tasks WHERE user_id = ? GROUP BY status");
$sum_stmt->execute([$user_id]);
$summary = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'canceled' => 0];
foreach ($sum_stmt->fetchAll() as $row) {
    $summary[$row['status']] = (int)$row['cnt'];
}
$total_all = array_sum($summary);

// ── GROUP INTO TODAY / TOMORROW / UPCOMING / PAST ────────────
$today_str    = date('Y-m-d');
$tomorrow_str = date('Y-m-d', strtotime('+1 day'));

$groups = ['Today' => [], 'Tomorrow' => [], 'Upcoming' => [], 'Past' => []];
foreach ($all_tasks as $task) {
    $d = date('Y-m-d', strtotime($task['schedule_datetime']));
    if ($d === $today_str)         $groups['Today'][]    = $task;
    elseif ($d === $tomorrow_str)  $groups['Tomorrow'][] = $task;
    elseif ($d > $today_str)       $groups['Upcoming'][] = $task;
    else                           $groups['Past'][]     = $task;
}

// ── URL BUILDER ──────────────────────────────────────────────
function buildUrl($priority = '', $search = '', $date = '', $page = 1, $status = '') {
    $p = [];
    if ($priority !== '') $p['priority']      = $priority;
    if ($status   !== '') $p['status_filter'] = $status;
    if ($search   !== '') $p['search']        = $search;
    if ($date     !== '') $p['filter_date']   = $date;
    if ($page      >  1)  $p['page']          = $page;
    return 'my_tasks.php' . (!empty($p) ? '?' . http_build_query($p) : '');
}

// ── STATUS META ──────────────────────────────────────────────
function statusMeta($s) {
    $map = [
        'pending'     => ['label' => 'Pending',     'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.15)'],
        'in_progress' => ['label' => 'In Progress',  'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.15)'],
        'completed'   => ['label' => 'Completed',    'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.15)'],
        'canceled'    => ['label' => 'Canceled',     'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.15)'],
    ];
    return $map[$s] ?? ['label' => ucfirst($s), 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.15)'];
}
function priorityMeta($p) {
    $map = [
        'high'   => ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.15)',   'dot' => '#ef4444'],
        'medium' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.15)',  'dot' => '#f59e0b'],
        'low'    => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,0.15)',  'dot' => '#10b981'],
    ];
    return $map[$p] ?? ['color' => '#94a3b8', 'bg' => 'rgba(255,255,255,0.05)', 'dot' => '#94a3b8'];
}
function categoryIcon($c) {
    $icons = [
        'personal' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'academic' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
        'chores'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'health'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'work'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    ];
    return $icons[$c] ?? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Tasks – Daily Planner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            background: #0b0f19;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .app-layout { display: flex; min-height: 100vh; }

        /* ── MAIN ── */
        .main-content {
            flex: 1;
            padding: 2rem 2.25rem;
            overflow-y: auto;
            max-width: 100%;
        }

        /* ── TOP BAR ── */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .page-heading h1 {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .page-heading p {
            margin: 0.3rem 0 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .btn-add {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: #5b67f7;
            color: #fff;
            border: none;
            padding: 0.65rem 1.3rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-add:hover { background: #4c56e0; transform: translateY(-1px); }
        .user-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #5b67f7;
        }

        /* ── SUMMARY CARDS ── */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 1000px) { .summary-row { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 600px)  { .summary-row { grid-template-columns: repeat(2, 1fr); } }
        .summary-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 1.1rem 1.3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.2s, background 0.2s;
        }
        .summary-card:hover, .summary-card.active {
            border-color: var(--card-color);
            background: rgba(255,255,255,0.06);
        }
        .summary-card.active { box-shadow: 0 0 0 1px var(--card-color); }
        .summary-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            background: var(--card-bg);
            flex-shrink: 0;
        }
        .summary-info .num {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--card-color);
            line-height: 1;
        }
        .summary-info .lbl {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-wrap {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .search-wrap svg {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }
        .filter-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-input:focus { border-color: #5b67f7; }
        .filter-input::placeholder { color: #64748b; }
        .date-filter {
            padding: 0.65rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: #0f1729;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            color-scheme: dark;
        }
        .date-filter:focus { border-color: #5b67f7; }
        .filter-select {
            padding: 0.65rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: #0f1729;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
            appearance: auto;
        }
        .filter-select:focus { border-color: #5b67f7; }
        .filter-select option {
            background: #0f1729;
            color: #f1f5f9;
        }
        .btn-search {
            padding: 0.65rem 1.2rem;
            border-radius: 10px;
            border: none;
            background: #5b67f7;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-clear {
            padding: 0.65rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: transparent;
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-clear:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #e2e8f0;
        }
        .section-count {
            background: rgba(91,103,247,0.2);
            color: #818cf8;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.15rem 0.55rem;
            border-radius: 20px;
        }
        .section-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin-bottom: 0.85rem;
        }

        /* ── TASK CARD ── */
        .task-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.6rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .task-card:hover {
            border-color: rgba(91,103,247,0.3);
            background: rgba(255,255,255,0.05);
        }
        .task-card.overdue {
            border-left: 3px solid #ef4444;
        }
        .priority-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }
        .task-body { flex: 1; min-width: 0; }
        .task-name {
            font-size: 0.975rem;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0 0 0.3rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .task-name.completed-name {
            text-decoration: line-through;
            color: #64748b;
        }
        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            font-size: 0.8rem;
            color: #64748b;
        }
        .task-meta .sep { color: rgba(255,255,255,0.1); }
        .badge {
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .task-desc {
            font-size: 0.82rem;
            color: #64748b;
            margin: 0.35rem 0 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .task-actions {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        .btn-act {
            padding: 0.3rem 0.85rem;
            border-radius: 7px;
            border: none;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: opacity 0.15s;
        }
        .btn-act:hover { opacity: 0.85; }
        .btn-edit-act  { background: rgba(91,103,247,0.18); color: #818cf8; }
        .btn-del-act   { background: rgba(239,68,68,0.15);  color: #f87171; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #475569;
        }
        .empty-state svg { opacity: 0.25; margin-bottom: 0.75rem; }
        .empty-state p { margin: 0; font-size: 0.9rem; }

        /* ── SECTION BLOCK ── */
        .task-section { margin-bottom: 2rem; }

        /* ── PAGINATION ── */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.4rem;
            margin-top: 2rem;
        }
        .pg-btn {
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .pg-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .pg-btn.cur   { background: #5b67f7; color: #fff; border-color: #5b67f7; }
        .pg-btn.off   { opacity: 0.3; pointer-events: none; }

        /* ── MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.72);
            z-index: 999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(6px);
        }
        .modal-box {
            background: #131b2e;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 18px;
            padding: 1.75rem;
            width: 90%;
            max-width: 470px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-title { margin: 0 0 0.25rem; font-size: 1.25rem; font-weight: 800; }
        .modal-sub   { margin: 0 0 1.5rem; color: #64748b; font-size: 0.85rem; }
        .form-group  { margin-bottom: 1rem; }
        .form-label  { display: block; font-size: 0.82rem; color: #94a3b8; margin-bottom: 0.3rem; font-weight: 600; }
        .form-input  {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,0.09);
            background: #0b0f19;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus { border-color: #5b67f7; }
        .form-row { display: flex; gap: 0.75rem; }
        .form-row .form-group { flex: 1; }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .btn-cancel-modal {
            padding: 0.6rem 1.2rem;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,0.1);
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-save-modal {
            padding: 0.6rem 1.4rem;
            border-radius: 9px;
            border: none;
            background: #5b67f7;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
<div class="app-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" style="width:260px;min-width:260px;padding:2rem 1.5rem;display:flex;flex-direction:column;justify-content:space-between;min-height:100vh;background:#0d1322;box-sizing:border-box;">
        <div>
            <div class="brand-logo" style="margin-bottom:2.5rem;">
                <a href="dashboard.php" style="display:flex;align-items:center;gap:0.75rem;text-decoration:none;">
                    <div style="width:38px;height:38px;background:#5b67f7;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(91,103,247,0.4);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 23C16.1421 23 19.5 19.6421 19.5 15.5C19.5 11.5 16 8.5 14.5 5C14 7.5 12 9 10.5 10C9 11 8 12.5 8 15.5C8 16.5 8.3 17.5 9 18.2C8.5 18 8 17.5 7.5 16.8C6.5 15.3 6.5 13.5 7 12C5.2 13.8 4.5 16.3 5 18.8C5.6 21.3 8.2 23 12 23Z"/></svg>
                    </div>
                    <span style="font-size:1.25rem;font-weight:800;color:#ffffff;letter-spacing:-0.3px;">DailyPlanner</span>
                </a>
            </div>
            <div style="margin-bottom:2rem;">
                <h1 style="margin:0;font-size:1.5rem;font-weight:800;line-height:1.2;color:#ffffff;">Start Your<br>Day Be <span style="color:#5b67f7;">Productive</span></h1>
            </div>
            <div style="margin-bottom:1rem;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">MENU</div>
            <nav style="display:flex;flex-direction:column;gap:0.6rem;">
                <a href="dashboard.php" style="display:flex;align-items:center;justify-content:space-between;padding:0.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.95rem;color:<?= $currentPage=='dashboard.php'?'#ffffff':'#94a3b8'?>;background:<?= $currentPage=='dashboard.php'?'#5b67f7':'transparent'?>;">
                    <span style="display:flex;align-items:center;gap:0.8rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Dashboard</span>
                </a>
                <a href="my_tasks.php" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.95rem;color:<?= $currentPage=='my_tasks.php'?'#ffffff':'#94a3b8'?>;background:<?= $currentPage=='my_tasks.php'?'#5b67f7':'transparent'?>;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    My Tasks
                </a>
                <a href="task_status.php" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.95rem;color:<?= $currentPage=='task_status.php'?'#ffffff':'#94a3b8'?>;background:<?= $currentPage=='task_status.php'?'#5b67f7':'transparent'?>;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Task Status
                </a>
                <a href="completed_history.php" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.95rem;color:<?= $currentPage=='completed_history.php'?'#ffffff':'#94a3b8'?>;background:<?= $currentPage=='completed_history.php'?'#5b67f7':'transparent'?>;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Completed
                </a>
            </nav>
        </div>
        <div>
            <a href="logout.php" style="display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1.2rem;border-radius:50px;text-decoration:none;color:#ef4444;font-weight:600;font-size:0.95rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <?php include 'task_notifier.php'; ?>
    <?php include 'confirm_modal.php'; ?>

    <!-- ── MAIN CONTENT ── -->
    <main class="main-content">

        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="page-heading">
                <h1>My Tasks</h1>
                <p>Manage, track, and organise all your scheduled activities</p>
            </div>
            <div class="top-bar-right">
                <button class="btn-add" onclick="openTaskModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Task
                </button>
                <a href="profile.php">
                    <img src="<?= getAvatarUrl($avatar_key, $full_name) ?>" alt="Avatar" class="user-avatar">
                </a>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="summary-row">
            <a href="my_tasks.php" class="summary-card <?= ($priority_filter===''&&$status_filter==='')?'active':'' ?>"
               style="--card-color:#818cf8;--card-bg:rgba(91,103,247,0.15);">
                <div class="summary-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <div class="summary-info">
                    <div class="num"><?= $total_all ?></div>
                    <div class="lbl">Total Tasks</div>
                </div>
            </a>
            <a href="<?= buildUrl('','','','1','pending') ?>" class="summary-card <?= $status_filter==='pending'?'active':'' ?>"
               style="--card-color:#f59e0b;--card-bg:rgba(245,158,11,0.15);">
                <div class="summary-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="summary-info">
                    <div class="num"><?= $summary['pending'] ?></div>
                    <div class="lbl">Pending</div>
                </div>
            </a>
            <a href="<?= buildUrl('','','','1','in_progress') ?>" class="summary-card <?= $status_filter==='in_progress'?'active':'' ?>"
               style="--card-color:#3b82f6;--card-bg:rgba(59,130,246,0.15);">
                <div class="summary-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></div>
                <div class="summary-info">
                    <div class="num"><?= $summary['in_progress'] ?></div>
                    <div class="lbl">In Progress</div>
                </div>
            </a>
            <a href="<?= buildUrl('','','','1','completed') ?>" class="summary-card <?= $status_filter==='completed'?'active':'' ?>"
               style="--card-color:#10b981;--card-bg:rgba(16,185,129,0.15);">
                <div class="summary-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="summary-info">
                    <div class="num"><?= $summary['completed'] ?></div>
                    <div class="lbl">Completed</div>
                </div>
            </a>
            <a href="<?= buildUrl('','','','1','canceled') ?>" class="summary-card <?= $status_filter==='canceled'?'active':'' ?>"
               style="--card-color:#ef4444;--card-bg:rgba(239,68,68,0.15);">
                <div class="summary-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                <div class="summary-info">
                    <div class="num"><?= $summary['canceled'] ?></div>
                    <div class="lbl">Cancelled</div>
                </div>
            </a>
        </div>

        <!-- FILTER BAR -->
        <form method="GET" class="filter-bar">
            <div class="search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search tasks…" class="filter-input">
            </div>
            <select name="priority" class="filter-select">
                <option value="" <?= $priority_filter===''?'selected':'' ?>>All Priorities</option>
                <option value="high"   <?= $priority_filter==='high'?'selected':''   ?>>High</option>
                <option value="medium" <?= $priority_filter==='medium'?'selected':'' ?>>Medium</option>
                <option value="low"    <?= $priority_filter==='low'?'selected':''    ?>>Low</option>
            </select>
            
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" class="date-filter" title="Filter by date">
            <button type="submit" class="btn-search">Search</button>
            <?php if ($search_query || $priority_filter || $status_filter || $filter_date): ?>
                <a href="my_tasks.php" class="btn-clear" style="display:inline-flex;align-items:center;gap:.3rem;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($total_tasks === 0): ?>
            <!-- GLOBAL EMPTY STATE -->
            <div class="empty-state" style="margin-top:3rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <p style="font-size:1rem;color:#94a3b8;margin-top:0.5rem;">No tasks found.</p>
                <p style="font-size:0.85rem;">Try adjusting your filters or <button onclick="openTaskModal()" style="background:none;border:none;color:#818cf8;cursor:pointer;font-size:0.85rem;padding:0;text-decoration:underline;">add a new task</button>.</p>
            </div>

        <?php else: ?>

            <?php
            // Section display config
            $section_config = [
                'Today'    => ['icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',  'color' => '#f59e0b'],
                'Tomorrow' => ['icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',  'color' => '#3b82f6'],
                'Upcoming' => ['icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',  'color' => '#818cf8'],
                'Past'     => ['icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',  'color' => '#64748b'],
            ];
            foreach ($groups as $label => $tasks):
                if (empty($tasks)) continue;
                $cfg = $section_config[$label];
            ?>
            <div class="task-section">
                <div class="section-header">
                    <span style="display:flex;align-items:center;"><?= $cfg['icon'] ?></span>
                    <h2><?= $label ?></h2>
                    <span class="section-count"><?= count($tasks) ?></span>
                    <?php if ($label === 'Past'): ?>
                        <span style="font-size:0.75rem;color:#ef4444;font-weight:600;margin-left:0.25rem;">• Overdue</span>
                    <?php endif; ?>
                </div>
                <div class="section-divider"></div>

                <?php foreach ($tasks as $t):
                    $pm  = priorityMeta($t['priority']);
                    $sm  = statusMeta($t['status']);
                    $isOverdue = ($label === 'Past' && !in_array($t['status'], ['completed','canceled']));
                ?>
                <div class="task-card <?= $isOverdue ? 'overdue' : '' ?>">
                    <!-- Priority dot -->
                    <div class="priority-dot" style="background:<?= $pm['dot'] ?>;"></div>

                    <!-- Body -->
                    <div class="task-body">
                        <p class="task-name <?= $t['status']==='completed'?'completed-name':'' ?>">
                            <?= htmlspecialchars($t['task_name']) ?>
                        </p>
                        <div class="task-meta">
                            <!-- Date/Time -->
                            <span style="display:inline-flex;align-items:center;gap:3px;">
                                <?php if ($label === 'Today' || $label === 'Past'): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?= date('h:i A', strtotime($t['schedule_datetime'])) ?>
                                <?php else: ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?= date('M j, Y · h:i A', strtotime($t['schedule_datetime'])) ?>
                                <?php endif; ?>
                            </span>
                            <span class="sep">|</span>
                            <!-- Category -->
                            <span style="display:inline-flex;align-items:center;"><?= categoryIcon($t['category']) ?><?= ucfirst($t['category']) ?></span>
                            <span class="sep">|</span>
                            <!-- Priority badge -->
                            <span class="badge" style="background:<?= $pm['bg'] ?>;color:<?= $pm['color'] ?>;">
                                <?= ucfirst($t['priority']) ?>
                            </span>
                            <!-- Status badge -->
                            <span class="badge" style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>;">
                                <?= $sm['label'] ?>
                            </span>
                        </div>
                        <?php if (!empty($t['description'])): ?>
                            <p class="task-desc"><?= htmlspecialchars($t['description']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="task-actions">
                        <button onclick='openTaskModal(<?= json_encode($t) ?>)' class="btn-act btn-edit-act">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit
                        </button>
                        <a href="my_tasks.php?delete=<?= $t['id'] ?>"
                           onclick="return confirmDelete('<?= htmlspecialchars(addslashes($t['task_name'])) ?>', this.href)"
                           class="btn-act btn-del-act" id="del-<?= $t['id'] ?>">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>Delete
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="<?= buildUrl($priority_filter,$search_query,$filter_date,$page-1,$status_filter) ?>"
                   class="pg-btn <?= $page<=1?'off':'' ?>">← Prev</a>

                <?php
                $range = 2;
                for ($i = 1; $i <= $total_pages; $i++):
                    if ($i === 1 || $i === $total_pages || abs($i - $page) <= $range):
                ?>
                    <a href="<?= buildUrl($priority_filter,$search_query,$filter_date,$i,$status_filter) ?>"
                       class="pg-btn <?= $i===$page?'cur':'' ?>"><?= $i ?></a>
                <?php
                    elseif (abs($i - $page) === $range + 1):
                        echo '<span style="color:#475569;padding:0 0.25rem;">…</span>';
                    endif;
                endfor;
                ?>

                <a href="<?= buildUrl($priority_filter,$search_query,$filter_date,$page+1,$status_filter) ?>"
                   class="pg-btn <?= $page>=$total_pages?'off':'' ?>">Next →</a>
            </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>

<!-- ── ADD / EDIT MODAL ── -->
<div id="taskModal" class="modal-overlay">
    <div class="modal-box">
        <h3 id="modalTitle" class="modal-title">Add New Task</h3>
        <p class="modal-sub">Fill in the details below to schedule your task.</p>

        <form method="POST">
            <input type="hidden" name="action"  value="save">
            <input type="hidden" name="task_id" id="f_task_id">

            <div class="form-group">
                <label class="form-label">Task Name *</label>
                <input type="text" name="task_name" id="f_task_name" class="form-input" placeholder="e.g. Study for exam" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="f_description" class="form-input" rows="2" placeholder="Optional notes…" style="resize:vertical;"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Schedule Date &amp; Time *</label>
                <input type="datetime-local" name="schedule_datetime" id="f_schedule" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" id="f_category" class="form-input" required>
                        <option value="">Select…</option>
                        <option value="personal">Personal</option>
                        <option value="academic">Academic</option>
                        <option value="chores">Chores</option>
                        <option value="health">Health</option>
                        <option value="work">Work</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" id="f_priority" class="form-input">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="statusGroup" style="display:none;">
                <label class="form-label">Status</label>
                <select name="status" id="f_status" class="form-input">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal" onclick="closeTaskModal()">Cancel</button>
                <button type="submit" id="submitBtn" class="btn-save-modal"><span class="btn-text">Save Task</span></button>
            </div>
        </form>
    </div>
</div>

<script>
function openTaskModal(task) {
    var modal = document.getElementById('taskModal');
    var isEdit = task && task.id;

    document.getElementById('modalTitle').textContent  = isEdit ? 'Edit Task' : 'Add New Task';
    // update btn text but preserve .btn-text span
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.querySelector('.btn-text').textContent = isEdit ? 'Update Task' : 'Save Task';
    // re-enable in case it was disabled from a previous submit
    submitBtn.classList.remove('btn-loading');
    submitBtn.disabled = false;
    document.getElementById('statusGroup').style.display = isEdit ? 'block' : 'none';

    document.getElementById('f_task_id').value    = isEdit ? task.id          : '';
    document.getElementById('f_task_name').value  = isEdit ? task.task_name   : '';
    document.getElementById('f_description').value= isEdit ? (task.description || '') : '';
    document.getElementById('f_category').value   = isEdit ? task.category    : '';
    document.getElementById('f_priority').value   = isEdit ? task.priority    : 'low';
    document.getElementById('f_status').value     = isEdit ? task.status      : 'pending';

    if (isEdit && task.schedule_datetime) {
        document.getElementById('f_schedule').value = task.schedule_datetime.replace(' ', 'T').slice(0, 16);
    } else {
        document.getElementById('f_schedule').value = '';
    }

    modal.style.display = 'flex';
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

// Close modal on backdrop click
document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) closeTaskModal();
});

function confirmDelete(name, href) {
    showConfirm({
        type: 'danger',
        title: 'Delete Task',
        message: 'Delete "' + name + '"? This cannot be undone.',
        okText: 'Yes, Delete',
        onOk: function() { window.location.href = href; }
    });
    return false;
}

// Save/Update task loading
document.getElementById('taskModal').querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.classList.add('btn-loading');
    btn.disabled = true;
});

// Logout loading — sidebar link
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
