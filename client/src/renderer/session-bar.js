// ============================================================
// Session Bar — Logic (light mode)
// ============================================================
'use strict';

let config        = {};
let sessionToken  = '';
let loginTime     = null;
let heartbeatTimer  = null;
let timerInterval   = null;

async function init() {
  config       = await window.kiosk.getConfig();
  sessionToken = sessionStorage.getItem('session_token');
  const user      = JSON.parse(sessionStorage.getItem('user')     || '{}');
  const terminal  = JSON.parse(sessionStorage.getItem('terminal') || '{}');
  const loginStr  = sessionStorage.getItem('login_time');
  loginTime = loginStr ? new Date(loginStr) : new Date();

  if (!sessionToken) {
    window.kiosk.goToLogin();
    return;
  }

  // ── Populate UI ──────────────────────────────────────────────
  document.getElementById('bar-terminal').textContent  = terminal.code || config.terminalCode;
  document.getElementById('bar-user-name').textContent = user.name    || '—';
  document.getElementById('bar-uid').textContent       = user.user_id || '—';
  document.getElementById('bar-avatar').textContent    = (user.name || 'U').charAt(0).toUpperCase();
  document.getElementById('bar-role').textContent      = user.role    || 'user';

  // ── Session timer ────────────────────────────────────────────
  const timerEl = document.getElementById('bar-timer');
  timerInterval = setInterval(() => {
    const elapsed = Math.floor((Date.now() - loginTime.getTime()) / 1000);
    timerEl.textContent = formatTime(elapsed);
  }, 1000);

  // ── Heartbeat ────────────────────────────────────────────────
  startHeartbeat();

  // ── End Session button ───────────────────────────────────────
  document.getElementById('btn-end').addEventListener('click', handleLogout);
}

function formatTime(s) {
  const h   = Math.floor(s / 3600).toString().padStart(2, '0');
  const m   = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
  const sec = (s % 60).toString().padStart(2, '0');
  return `${h}:${m}:${sec}`;
}

function startHeartbeat() {
  const ms = (config.heartbeatSeconds || 30) * 1000;
  heartbeatTimer = setInterval(sendHeartbeat, ms);
}

async function sendHeartbeat() {
  try {
    await fetch(`${config.apiBaseUrl}/heartbeat.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-Session-Token': sessionToken },
      body:    JSON.stringify({}),
    });
    // Brief visual pulse on success
    const dot = document.getElementById('hb-dot');
    if (dot) { dot.style.background = '#2B6CB0'; setTimeout(() => dot.style.background = '', 400); }
  } catch (e) {
    console.warn('[Heartbeat] Network error:', e);
  }
}

async function handleLogout() {
  const confirmed = await window.kiosk.confirm(
    'Are you sure you want to end your session?\n\nYou will be logged out and the screen will lock.'
  );
  if (!confirmed) return;

  clearInterval(heartbeatTimer);
  clearInterval(timerInterval);

  try {
    await fetch(`${config.apiBaseUrl}/logout.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-Session-Token': sessionToken },
      body:    JSON.stringify({}),
    });
  } catch (e) {
    console.error('[Logout] Error:', e);
  }

  sessionStorage.clear();
  window.kiosk.goToLogin();
}

init();
