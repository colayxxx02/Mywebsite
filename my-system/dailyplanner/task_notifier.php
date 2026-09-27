<!-- Custom Task Alert Modal -->
<div id="taskModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(6px);">
    <div style="background: #131b2e; padding: 1.75rem; border-radius: 20px; width: 90%; max-width: 440px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; border: 1px solid rgba(255,255,255,0.1); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        <!-- Icon Badge (Replaces Emoji) -->
        <div style="width: 56px; height: 56px; background: rgba(91, 103, 247, 0.15); border: 1px solid rgba(91, 103, 247, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; color: #5b67f7;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </div>

        <h3 style="margin: 0 0 0.25rem 0; color: #ffffff; font-size: 1.25rem; font-weight: 700;">Task Schedule Alert</h3>
        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.25rem;">It's time to work on your scheduled activity.</p>

        <div style="background: rgba(11, 15, 25, 0.6); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 1.5rem; text-align: left;">
            <h4 id="modalTaskTitle" style="margin: 0 0 0.35rem 0; font-size: 1rem; color: #ffffff; font-weight: 600;">Task Title</h4>
            <p id="modalTaskDesc" style="margin: 0; font-size: 0.85rem; color: #94a3b8; line-height: 1.4;">Task Description</p>
            <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.75rem; color: #f87171; font-size: 0.8rem; font-weight: 600;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span id="modalTaskTime">Due Time: 00:00</span>
            </div>
        </div>

        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Update Status To</p>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1rem;">
            <button onclick="updateModalTaskStatus('in_progress')" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); flex: 1; padding: 0.45rem 0.5rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">In Progress</button>
            <button onclick="updateModalTaskStatus('completed')" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); flex: 1; padding: 0.45rem 0.5rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">Completed</button>
            <button onclick="updateModalTaskStatus('canceled')" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); flex: 1; padding: 0.45rem 0.5rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">Cancel</button>
        </div>

        <button onclick="dismissTaskModal()" style="background: transparent; border: none; color: #64748b; cursor: pointer; font-size: 0.8rem; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">Remind me later</button>
    </div>
</div>

<style>
@keyframes popIn {
    from { transform: scale(0.9) translateY(10px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
</style>

<script>
let currentNotifyTaskId = null;

// Check immediately on page load, then every 30 seconds
checkDueTasks();
setInterval(checkDueTasks, 30000);

function checkDueTasks() {
    fetch('check_due_tasks.php')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.status === 'success' && data.has_due && data.task) {
                showTaskNotifyModal(data.task);
            }
        })
        .catch(function(err) {
            console.error('Task notification check failed:', err);
        });
}

function showTaskNotifyModal(task) {
    currentNotifyTaskId = task.id;

    document.getElementById('modalTaskTitle').textContent = task.title || 'Untitled Task';
    document.getElementById('modalTaskDesc').textContent  = task.description ? task.description : 'No description provided.';

    // Format the due date/time for display
    var dueDate = task.due_date ? new Date(task.due_date.replace(' ', 'T')) : null;
    if (dueDate && !isNaN(dueDate)) {
        var timeStr = dueDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        var dateStr = dueDate.toLocaleDateString([], { month: 'short', day: 'numeric' });
        document.getElementById('modalTaskTime').textContent = 'Due: ' + dateStr + ' at ' + timeStr;
    } else {
        document.getElementById('modalTaskTime').textContent = 'Due: ' + (task.due_date || '');
    }

    var overlay = document.getElementById('taskModalOverlay');
    overlay.style.display = 'flex';

    // Play a sound alert if the browser supports it
    try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var oscillator = ctx.createOscillator();
        var gainNode = ctx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, ctx.currentTime);
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.6);
    } catch(e) { /* audio not supported, ignore */ }
}

function updateModalTaskStatus(newStatus) {
    if (!currentNotifyTaskId) return;

    var taskId = currentNotifyTaskId;

    fetch('update_task_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'task_id=' + encodeURIComponent(taskId) + '&status=' + encodeURIComponent(newStatus)
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            document.getElementById('taskModalOverlay').style.display = 'none';
            currentNotifyTaskId = null;
            location.reload();
        }
    })
    .catch(function(err) {
        console.error('Failed to update task status:', err);
    });
}

function dismissTaskModal() {
    if (!currentNotifyTaskId) return;

    var taskId = currentNotifyTaskId;

    // Mark as notified so it doesn't pop up again immediately
    fetch('update_task_status_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'task_id=' + encodeURIComponent(taskId) + '&action=dismiss'
    })
    .then(function() {
        document.getElementById('taskModalOverlay').style.display = 'none';
        currentNotifyTaskId = null;
    })
    .catch(function(err) {
        // Still hide the modal even if the request fails
        document.getElementById('taskModalOverlay').style.display = 'none';
        currentNotifyTaskId = null;
    });
}
</script>