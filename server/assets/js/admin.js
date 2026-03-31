/* ============================================================
   Admin Panel — Shared JavaScript
   ============================================================ */

// ── Live Clock ──────────────────────────────────────────────
function startClock(el) {
  if (!el) return;
  const update = () => {
    const now = new Date();
    el.textContent = now.toLocaleTimeString('en-PH', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
  };
  update();
  setInterval(update, 1000);
}

// ── Duration formatter ──────────────────────────────────────
function formatDuration(seconds) {
  seconds = parseInt(seconds, 10);
  if (isNaN(seconds) || seconds < 0) return '0s';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  const parts = [];
  if (h > 0) parts.push(`${h}h`);
  if (m > 0) parts.push(`${m}m`);
  parts.push(`${s}s`);
  return parts.join(' ');
}

// ── Live session timers ─────────────────────────────────────
function startSessionTimers() {
  document.querySelectorAll('[data-login-time]').forEach(el => {
    const loginTime = new Date(el.dataset.loginTime);
    setInterval(() => {
      const elapsed = Math.floor((Date.now() - loginTime.getTime()) / 1000);
      el.textContent = formatDuration(elapsed);
    }, 1000);
  });
}

// ── Table search filter ─────────────────────────────────────
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Modal helpers ───────────────────────────────────────────
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
  }
});

// ── Flash auto-dismiss ──────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => el.style.opacity = '0', 4000);
  setTimeout(() => el.remove(), 4500);
});

// ── Confirm helper ──────────────────────────────────────────
function confirmAction(message, callback) {
  if (window.confirm(message)) callback();
}

// ── Auto-refresh for active sessions page ──────────────────
function autoRefresh(intervalSeconds) {
  setInterval(() => location.reload(), intervalSeconds * 1000);
}

// ── Init ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  startClock(document.getElementById('live-clock'));
  startSessionTimers();
});
