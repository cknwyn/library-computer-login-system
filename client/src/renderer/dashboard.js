// ============================================================
// Library Kiosk — Dashboard Logic
// Session timer, heartbeat, apps list, app request form
// ============================================================
'use strict';

let config       = {};
let sessionToken = '';
let sessionId    = '';
let loginTime    = null;
let heartbeatTimer = null;
let elapsedTimer   = null;

// Category → emoji icon map
const CATEGORY_ICONS = {
  'Productivity': '📄',
  'Internet':     '🌐',
  'Research':     '🔬',
  'Development':  '💻',
  'Media':        '🎬',
  'Utilities':    '🔧',
  'default':      '📦',
};

// ── Init ──────────────────────────────────────────────────────
async function init() {
  config = await window.kiosk.getConfig();

  // Restore session from sessionStorage
  sessionToken = sessionStorage.getItem('session_token');
  sessionId    = sessionStorage.getItem('session_id');
  const user      = JSON.parse(sessionStorage.getItem('user')     || '{}');
  const terminal  = JSON.parse(sessionStorage.getItem('terminal') || '{}');
  const loginTimeStr = sessionStorage.getItem('login_time');
  loginTime = loginTimeStr ? new Date(loginTimeStr) : new Date();

  // Redirect to login if there's no session
  if (!sessionToken) {
    window.kiosk.goToLogin();
    return;
  }

  // Populate UI
  populateUserCard(user, terminal);
  startSessionTimer();
  startClock();
  loadApps();
  setupRequestForm();
  startHeartbeat();
  setupLogout();
}

// ── User Card ─────────────────────────────────────────────────
function populateUserCard(user, terminal) {
  document.getElementById('user-name').textContent = user.name || '—';
  document.getElementById('user-id-display').textContent = user.user_id || '—';
  document.getElementById('user-dept').textContent = user.department || '';
  document.getElementById('terminal-code-display').textContent = `Terminal: ${terminal.code || config.terminalCode}`;

  const roleBadge = document.getElementById('user-role');
  roleBadge.textContent = user.role || 'user';
  roleBadge.className = `user-role-badge ${user.role || ''}`;

  const avatar = document.getElementById('user-avatar');
  avatar.textContent = (user.name || 'U').charAt(0).toUpperCase();

  document.getElementById('login-time-display').textContent =
    loginTime.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

// ── Session Timer ─────────────────────────────────────────────
function startSessionTimer() {
  const timerEl = document.getElementById('session-timer');
  elapsedTimer = setInterval(() => {
    const elapsed = Math.floor((Date.now() - loginTime.getTime()) / 1000);
    timerEl.textContent = formatTime(elapsed);
  }, 1000);
}

function formatTime(totalSeconds) {
  const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
  const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
  const s = (totalSeconds % 60).toString().padStart(2, '0');
  return `${h}:${m}:${s}`;
}

// ── Clock ─────────────────────────────────────────────────────
function startClock() {
  const clockEl = document.getElementById('topbar-clock');
  const update = () => {
    clockEl.textContent = new Date().toLocaleTimeString('en-PH',
      { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
  };
  update();
  setInterval(update, 1000);
}

// ── Heartbeat ─────────────────────────────────────────────────
function startHeartbeat() {
  const intervalMs = (config.heartbeatSeconds || 30) * 1000;
  heartbeatTimer = setInterval(sendHeartbeat, intervalMs);
}

async function sendHeartbeat() {
  try {
    const res  = await apiPost('heartbeat.php', {});
    const data = await res.json();
    if (!data.success) {
      console.warn('[Heartbeat] Server rejected heartbeat:', data.error);
    } else {
      // Flash the heartbeat dot
      const dot = document.querySelector('.heartbeat-pulse');
      if (dot) { dot.style.background = '#F59E0B'; setTimeout(() => dot.style.background = '', 400); }
    }
  } catch (e) {
    console.error('[Heartbeat] Network error:', e);
  }
}

// ── Apps ──────────────────────────────────────────────────────
async function loadApps() {
  const grid = document.getElementById('apps-grid');
  try {
    const res  = await apiFetch('apps.php');
    const data = await res.json();

    if (!data.success || !data.apps.length) {
      grid.innerHTML = '<div class="loading-state">No apps listed at this time.</div>';
      return;
    }

    // Group by category
    const byCategory = {};
    data.apps.forEach(app => {
      const cat = app.category || 'Other';
      if (!byCategory[cat]) byCategory[cat] = [];
      byCategory[cat].push(app);
    });

    grid.innerHTML = '';
    Object.entries(byCategory).forEach(([cat, apps]) => {
      // Category label
      const catEl = document.createElement('div');
      catEl.style.cssText = 'grid-column:1/-1;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-muted);padding-top:8px';
      catEl.textContent = cat;
      grid.appendChild(catEl);

      apps.forEach(app => {
        const icon = CATEGORY_ICONS[cat] || CATEGORY_ICONS.default;
        const el = document.createElement('div');
        el.className = 'app-item';
        el.innerHTML = `
          <div class="app-icon">${icon}</div>
          <div class="app-name">${escHtml(app.name)}</div>
          <div class="app-category">${escHtml(app.version || '')}</div>
        `;
        grid.appendChild(el);
      });
    });

    // Show my requests history
    if (data.my_requests && data.my_requests.length) {
      renderMyRequests(data.my_requests);
    }

  } catch (e) {
    grid.innerHTML = '<div class="loading-state">Could not load app list.</div>';
    console.error('[Apps] Load error:', e);
  }
}

// ── App Request Form ──────────────────────────────────────────
function setupRequestForm() {
  // Type toggle buttons
  document.querySelectorAll('.type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('req-type').value = btn.dataset.type;
    });
  });

  document.getElementById('request-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    await submitRequest();
  });
}

async function submitRequest() {
  const appName = document.getElementById('req-app-name').value.trim();
  const reqType = document.getElementById('req-type').value;
  const reason  = document.getElementById('req-reason').value.trim();

  document.getElementById('req-success').style.display = 'none';
  document.getElementById('req-error').style.display   = 'none';

  if (!appName) {
    showReqError('Please enter an app name.');
    return;
  }

  const btn = document.querySelector('.btn-request');
  btn.disabled = true;
  btn.textContent = 'Submitting…';

  try {
    const res  = await apiPost('apps.php', { app_name: appName, request_type: reqType, reason });
    const data = await res.json();

    if (data.success) {
      showReqSuccess(data.message);
      document.getElementById('req-app-name').value = '';
      document.getElementById('req-reason').value   = '';
      // Reload apps to update my_requests
      setTimeout(loadApps, 600);
    } else {
      showReqError(data.error || 'Request failed. Please try again.');
    }
  } catch (e) {
    showReqError('Network error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Submit Request';
  }
}

function showReqSuccess(msg) {
  const el = document.getElementById('req-success');
  el.textContent = '✔ ' + msg;
  el.style.display = 'block';
}

function showReqError(msg) {
  const el = document.getElementById('req-error');
  el.textContent = '⚠ ' + msg;
  el.style.display = 'block';
}

// ── My Requests ───────────────────────────────────────────────
function renderMyRequests(requests) {
  const section = document.getElementById('my-requests-section');
  const list    = document.getElementById('my-requests-list');
  section.style.display = 'block';
  list.innerHTML = '';
  requests.slice(0, 5).forEach(r => {
    const badges = { pending:'req-badge-pending', approved:'req-badge-approved', denied:'req-badge-denied' };
    const badgeClass = badges[r.status] || 'req-badge-pending';
    const el = document.createElement('div');
    el.className = 'req-history-item';
    el.innerHTML = `
      <div>
        <div class="req-item-name">${escHtml(r.app_name)}</div>
        <div class="req-item-type">${escHtml(r.request_type)}</div>
      </div>
      <span class="req-badge ${badgeClass}">${capitalise(r.status)}</span>
    `;
    list.appendChild(el);
  });
}

// ── Logout ────────────────────────────────────────────────────
function setupLogout() {
  document.getElementById('btn-logout').addEventListener('click', async () => {
    const confirmed = await window.kiosk.confirm('Are you sure you want to end your session and log out?');
    if (!confirmed) return;
    await logout();
  });
}

async function logout() {
  clearInterval(heartbeatTimer);
  clearInterval(elapsedTimer);

  try {
    await apiPost('logout.php', {});
  } catch (e) {
    console.error('[Logout] Error:', e);
  }

  sessionStorage.clear();
  window.kiosk.goToLogin();
}

// ── API Helpers ───────────────────────────────────────────────
function apiPost(endpoint, body) {
  return fetch(`${config.apiBaseUrl}/${endpoint}`, {
    method:  'POST',
    headers: {
      'Content-Type':    'application/json',
      'X-Session-Token': sessionToken,
    },
    body: JSON.stringify(body),
  });
}

function apiFetch(endpoint) {
  return fetch(`${config.apiBaseUrl}/${endpoint}`, {
    method:  'GET',
    headers: { 'X-Session-Token': sessionToken },
  });
}

// ── Utils ─────────────────────────────────────────────────────
function escHtml(str) {
  return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function capitalise(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// ── Boot ──────────────────────────────────────────────────────
init();
