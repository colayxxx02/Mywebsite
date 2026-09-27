<?php
require_once 'db.php';
require_once 'avatars.php';
checkLogin();

$user_id     = $_SESSION['user_id'];
$currentPage = basename($_SERVER['PHP_SELF']);
$error = $success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// ── HANDLE FORM SUBMIT ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name']);
    $username         = trim($_POST['username']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $avatar_name      = $_POST['default_avatar'] ?? $user['avatar'];

    if (empty($full_name) || empty($username)) {
        $error = "Full name and username are required.";
    } else {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $chk->execute([$username, $user_id]);
        if ($chk->fetch()) {
            $error = "That username is already taken.";
        } else {
            $hashed_password = $user['password'];
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = "Enter your current password to set a new one.";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }

            if (empty($error) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $new_name = uniqid('avatar_', true) . '.' . $ext;
                    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/'.$new_name)) {
                        if ($user['avatar'] && strpos($user['avatar'],'avatar_')===0 && file_exists('uploads/'.$user['avatar']))
                            unlink('uploads/'.$user['avatar']);
                        $avatar_name = $new_name;
                    }
                }
            }

            if (empty($error)) {
                $upd = $pdo->prepare("UPDATE users SET full_name=?, username=?, avatar=?, password=? WHERE id=?");
                if ($upd->execute([$full_name, $username, $avatar_name, $hashed_password, $user_id])) {
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['avatar']    = $avatar_name;
                    $success = "Profile updated successfully!";
                    $user['full_name'] = $full_name;
                    $user['username']  = $username;
                    $user['avatar']    = $avatar_name;
                    $user['password']  = $hashed_password;
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }
}

// ── USER TASK STATS ───────────────────────────────────────────
$joined  = date('M j, Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile – Daily Planner</title>
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

/* MAIN PROFILE GRID */
.profile-grid{display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;}
@media(max-width:820px){.profile-grid{grid-template-columns:1fr;}}

/* LEFT PANEL */
.left-panel{display:flex;flex-direction:column;gap:1.25rem;}
.avatar-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.75rem;text-align:center;}
.avatar-img{width:110px;height:110px;border-radius:50%;object-fit:cover;border:3px solid #5b67f7;margin-bottom:1rem;display:block;margin-left:auto;margin-right:auto;}
.user-displayname{font-size:1.15rem;font-weight:800;color:#f1f5f9;margin:0 0 .2rem;}
.user-handle{font-size:.85rem;color:#64748b;margin:0 0 1rem;}
.joined-tag{display:inline-flex;align-items:center;gap:.35rem;background:rgba(91,103,247,.12);border:1px solid rgba(91,103,247,.2);border-radius:20px;padding:.3rem .75rem;font-size:.75rem;color:#818cf8;}

/* PRESET AVATARS */
.preset-label{font-size:.78rem;color:#64748b;text-align:left;margin:.9rem 0 .4rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.preset-row{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;}
.preset-row label{cursor:pointer;position:relative;}
.preset-row input[type=radio]{display:none;}
.preset-row img{width:44px;height:44px;border-radius:50%;border:2px solid transparent;transition:border-color .2s,transform .15s;}
.preset-row input[type=radio]:checked+img{border-color:#5b67f7;transform:scale(1.1);}
.preset-row label:hover img{border-color:rgba(91,103,247,.5);}

/* UPLOAD */
.upload-label{display:flex;align-items:center;justify-content:center;gap:.5rem;background:rgba(255,255,255,.04);border:1px dashed rgba(255,255,255,.12);border-radius:10px;padding:.65rem 1rem;cursor:pointer;font-size:.82rem;color:#94a3b8;margin-top:.75rem;transition:border-color .2s;}
.upload-label:hover{border-color:#5b67f7;color:#fff;}
.upload-label input{display:none;}

/* STATS CARD - removed */

/* RIGHT PANEL: FORM */
.form-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:1.75rem;}
.section-title{font-size:1rem;font-weight:700;color:#e2e8f0;margin:0 0 1.1rem;display:flex;align-items:center;gap:.5rem;}
.section-title span{font-size:1rem;}
.form-divider{height:1px;background:rgba(255,255,255,.06);margin:1.5rem 0;}
.form-group{margin-bottom:1rem;}
.form-label{display:block;font-size:.8rem;color:#94a3b8;margin-bottom:.3rem;font-weight:600;}
.form-input{width:100%;padding:.65rem .9rem;border-radius:10px;border:1px solid rgba(255,255,255,.09);background:#0b0f19;color:#fff;font-size:.9rem;outline:none;transition:border-color .2s;}
.form-input:focus{border-color:#5b67f7;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.85rem;}
@media(max-width:500px){.form-row{grid-template-columns:1fr;}}
.pw-hint{font-size:.75rem;color:#475569;margin-top:.3rem;}

/* ALERT BOXES */
.alert{padding:.75rem 1rem;border-radius:10px;font-size:.87rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.6rem;}
.alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;}
.alert-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34d399;}

/* BUTTONS */
.btn-row{display:flex;justify-content:flex-end;gap:.75rem;margin-top:1.5rem;}
.btn-cancel{padding:.6rem 1.2rem;border-radius:10px;border:1px solid rgba(255,255,255,.1);background:transparent;color:#94a3b8;cursor:pointer;font-weight:600;text-decoration:none;font-size:.9rem;}
.btn-cancel:hover{color:#fff;}
.btn-save{padding:.6rem 1.5rem;border-radius:10px;border:none;background:#5b67f7;color:#fff;cursor:pointer;font-weight:700;font-size:.9rem;transition:background .2s;}
.btn-save:hover{background:#4c56e0;}
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
<?php include 'confirm_modal.php'; ?>

<main class="main-content">

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="page-heading">
            <h1>My Profile</h1>
            <p>Manage your account details and preferences</p>
        </div>
    </div>

    <div class="profile-grid">

        <!-- LEFT: AVATAR + STATS -->
        <div class="left-panel">

            <!-- Avatar card -->
            <div class="avatar-card">
                <img id="previewAvatar" src="<?=getAvatarUrl($user['avatar'],$user['full_name'])?>" alt="Avatar" class="avatar-img">
                <p class="user-displayname"><?=htmlspecialchars($user['full_name'])?></p>
                <p class="user-handle">@<?=htmlspecialchars($user['username'])?></p>
                <span class="joined-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Joined <?=$joined?>
                </span>

                <p class="preset-label">Choose Avatar</p>
                <div class="preset-row">
                    <?php
                    $presets = ['man1','man2','woman1','woman2'];
                    foreach($presets as $pk):
                        $checked = ($user['avatar']===$pk) ? 'checked' : '';
                    ?>
                    <label>
                        <input type="radio" name="default_avatar" value="<?=$pk?>" form="profileForm" <?=$checked?> onchange="updatePreview('<?=getAvatarUrl($pk,$user['full_name'])?>');this.form.querySelector('[name=default_avatar]').value='<?=$pk?>'">
                        <img src="<?=getAvatarUrl($pk,$user['full_name'])?>" alt="<?=$pk?>">
                    </label>
                    <?php endforeach; ?>
                </div>

                <label class="upload-label" for="avatarUpload">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                    Upload custom photo
                    <input type="file" id="avatarUpload" name="avatar" accept="image/*" form="profileForm" onchange="previewFile(this)">
                </label>
            </div>

        </div><!-- end left-panel -->

        <!-- RIGHT: EDIT FORM -->
        <div class="form-card">

            <?php if($error): ?>
            <div class="alert alert-err">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?=htmlspecialchars($error)?>
            </div>
            <?php endif; ?>
            <?php if($success): ?>
            <div class="alert alert-ok">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <?=htmlspecialchars($success)?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <input type="hidden" name="default_avatar" id="hiddenAvatar" value="<?=htmlspecialchars($user['avatar'])?>">

                <!-- Basic Info -->
                <p class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Basic Information
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" value="<?=htmlspecialchars($user['full_name'])?>" class="form-input" required placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" value="<?=htmlspecialchars($user['username'])?>" class="form-input" required placeholder="@username">
                    </div>
                </div>

                <div class="form-divider"></div>

                <!-- Change Password -->
                <p class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Change Password
                </p>
                <p style="font-size:.82rem;color:#64748b;margin:-.5rem 0 1rem;">Leave blank if you don't want to change your password.</p>

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" placeholder="Enter current password" autocomplete="current-password">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="newPw" class="form-input" placeholder="Min. 6 characters" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirmPw" class="form-input" placeholder="Repeat new password" autocomplete="new-password" oninput="checkPwMatch()">
                        <p class="pw-hint" id="pwHint" style="display:none;"></p>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="dashboard.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save" id="profileSaveBtn"><span class="btn-text">Save Changes</span></button>
                </div>
            </form>
        </div>

    </div><!-- end profile-grid -->
</main>
</div>

<script>
function updatePreview(url){
    document.getElementById('previewAvatar').src = url;
}
function previewFile(input){
    if(input.files && input.files[0]){
        var reader = new FileReader();
        reader.onload = function(e){ document.getElementById('previewAvatar').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}

// Sync radio selection to hidden input
document.querySelectorAll('.preset-row input[type=radio]').forEach(function(r){
    r.addEventListener('change', function(){
        document.getElementById('hiddenAvatar').value = this.value;
        updatePreview(this.nextElementSibling.src);
    });
});

function checkPwMatch(){
    var hint = document.getElementById('pwHint');
    var nw   = document.getElementById('newPw').value;
    var cf   = document.getElementById('confirmPw').value;
    if(!cf){ hint.style.display='none'; return; }
    if(nw === cf){
        hint.style.display='block'; hint.style.color='#34d399'; hint.textContent='Passwords match';
    } else {
        hint.style.display='block'; hint.style.color='#f87171'; hint.textContent='Passwords do not match';
    }
}

// Save Changes loading
document.getElementById('profileForm').addEventListener('submit', function() {
    var btn = document.getElementById('profileSaveBtn');
    btn.classList.add('btn-loading');
    btn.disabled = true;
});

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
</script>
</body>
</html>
