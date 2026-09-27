<!-- ── CUSTOM CONFIRMATION MODAL ───────────────────────────── -->
<div id="confirmModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:99999;justify-content:center;align-items:center;backdrop-filter:blur(6px);">
    <div style="background:#131b2e;border:1px solid rgba(255,255,255,0.1);border-radius:20px;width:90%;max-width:400px;padding:2rem;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,0.6);animation:confirmPopIn 0.25s cubic-bezier(0.175,0.885,0.32,1.275);">

        <!-- Icon -->
        <div id="confirmIconWrap" style="width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem auto;">
            <svg id="confirmIcon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></svg>
        </div>

        <!-- Text -->
        <h3 id="confirmTitle" style="margin:0 0 0.4rem;font-size:1.2rem;font-weight:800;color:#fff;"></h3>
        <p id="confirmMessage" style="margin:0 0 1.75rem;font-size:0.875rem;color:#94a3b8;line-height:1.6;"></p>

        <!-- Buttons -->
        <div style="display:flex;gap:0.65rem;justify-content:center;">
            <button id="confirmCancelBtn" onclick="closeConfirmModal()" style="flex:1;padding:0.65rem 1rem;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:transparent;color:#94a3b8;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.color='#fff';this.style.borderColor='rgba(255,255,255,0.3)'" onmouseout="this.style.color='#94a3b8';this.style.borderColor='rgba(255,255,255,0.1)'">Cancel</button>
            <button id="confirmOkBtn" style="flex:1;padding:0.65rem 1rem;border-radius:10px;border:none;font-weight:700;font-size:0.9rem;cursor:pointer;transition:opacity 0.2s;position:relative;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                <span class="btn-text" id="confirmOkText"></span>
            </button>
        </div>

    </div>
</div>

<style>
@keyframes confirmPopIn {
    from { transform: scale(0.88) translateY(12px); opacity: 0; }
    to   { transform: scale(1) translateY(0);       opacity: 1; }
}
</style>

<script>
var _confirmCallback = null;

/**
 * showConfirm(options)
 *
 * options = {
 *   type    : 'danger' | 'warning'   — controls icon & button colour
 *   title   : string                 — bold heading
 *   message : string                 — subtitle text
 *   okText  : string                 — confirm button label  (default 'Confirm')
 *   onOk    : function               — called when user clicks confirm
 * }
 */
function showConfirm(options) {
    var type    = options.type    || 'danger';
    var title   = options.title   || 'Are you sure?';
    var message = options.message || '';
    var okText  = options.okText  || 'Confirm';

    _confirmCallback = options.onOk || null;

    // Icon path & colours per type
    var iconPath, iconBg, iconBorder, iconColor, btnBg, btnHover;
    if (type === 'warning') {
        iconPath   = '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
        iconBg     = 'rgba(245,158,11,0.15)';
        iconBorder = '1px solid rgba(245,158,11,0.3)';
        iconColor  = '#f59e0b';
        btnBg      = '#f59e0b';
        btnHover   = '#d97706';
    } else {
        iconPath   = '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>';
        iconBg     = 'rgba(239,68,68,0.15)';
        iconBorder = '1px solid rgba(239,68,68,0.3)';
        iconColor  = '#ef4444';
        btnBg      = '#ef4444';
        btnHover   = '#dc2626';
    }

    // Apply
    var iconWrap = document.getElementById('confirmIconWrap');
    iconWrap.style.background = iconBg;
    iconWrap.style.border     = iconBorder;

    var iconEl = document.getElementById('confirmIcon');
    iconEl.style.color  = iconColor;
    iconEl.innerHTML    = iconPath;

    document.getElementById('confirmTitle').textContent   = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmOkText').textContent  = okText;

    var okBtn = document.getElementById('confirmOkBtn');
    okBtn.style.background = btnBg;
    okBtn.style.color      = '#fff';
    okBtn.classList.remove('btn-loading');
    okBtn.disabled = false;
    okBtn.onmouseover = function(){ this.style.background = btnHover; };
    okBtn.onmouseout  = function(){ this.style.background = btnBg; };
    okBtn.onclick = function() {
        // Show spinner on confirm button
        okBtn.classList.add('btn-loading');
        okBtn.disabled = true;
        if (typeof _confirmCallback === 'function') {
            _confirmCallback();
        }
    };

    var overlay = document.getElementById('confirmModalOverlay');
    overlay.style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModalOverlay').style.display = 'none';
    _confirmCallback = null;
}

// Close on backdrop click
document.getElementById('confirmModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeConfirmModal();
});
</script>
