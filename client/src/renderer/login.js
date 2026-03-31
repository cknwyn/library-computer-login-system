// ============================================================
// Login Screen Logic — Light mode
// ============================================================
'use strict';

let config = {};

async function init() {
  config = await window.kiosk.getConfig();
  document.getElementById('terminal-code').textContent = config.terminalCode;
  startClock();
  setupForm();
  document.getElementById('pw-toggle').addEventListener('click', () => {
    const pw = document.getElementById('password');
    const btn = document.getElementById('pw-toggle');
    if (pw.type === 'password') { pw.type = 'text'; btn.textContent = 'Hide'; }
    else                        { pw.type = 'password'; btn.textContent = 'Show'; }
  });
}

function startClock() {
  const el = document.getElementById('clock');
  const tick = () => {
    el.textContent = new Date().toLocaleTimeString('en-PH', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
  };
  tick();
  setInterval(tick, 1000);
}

function setupForm() {
  // Clear validation state on input
  ['user-id', 'password'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => {
      document.getElementById(id).classList.remove('invalid');
      hideMessages();
    });
  });

  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    await doLogin();
  });
}

async function doLogin() {
  const userId   = document.getElementById('user-id').value.trim();
  const password = document.getElementById('password').value;
  const btn      = document.getElementById('btn-login');

  hideMessages();

  if (!userId) {
    showError('Please enter your Student or Staff ID.');
    document.getElementById('user-id').classList.add('invalid');
    document.getElementById('user-id').focus();
    return;
  }
  if (!password) {
    showError('Please enter your password.');
    document.getElementById('password').classList.add('invalid');
    document.getElementById('password').focus();
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Signing in…';

  try {
    const res  = await fetch(`${config.apiBaseUrl}/login.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({
        user_id:       userId,
        password:      password,
        terminal_code: config.terminalCode,
      }),
    });
    const data = await res.json();

    if (data.success) {
      sessionStorage.setItem('session_token', data.session_token);
      sessionStorage.setItem('session_id',    data.session_id);
      sessionStorage.setItem('user',          JSON.stringify(data.user));
      sessionStorage.setItem('terminal',      JSON.stringify(data.terminal));
      sessionStorage.setItem('login_time',    data.login_time);

      showSuccess('Login successful. Starting session…');
      setTimeout(() => window.kiosk.goToSessionBar(), 800);
    } else {
      showError(data.error || 'Incorrect ID or password. Please try again.');
      document.getElementById('password').value = '';
      document.getElementById('password').classList.add('invalid');
    }
  } catch (err) {
    showError('Cannot reach the server. Please notify the librarian.');
    console.error('[Login] Network error:', err);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Sign In';
  }
}

function showError(msg) {
  const el = document.getElementById('msg-error');
  el.textContent = msg;
  el.style.display = 'block';
}

function showSuccess(msg) {
  const el = document.getElementById('msg-success');
  el.textContent = msg;
  el.style.display = 'block';
}

function hideMessages() {
  document.getElementById('msg-error').style.display   = 'none';
  document.getElementById('msg-success').style.display = 'none';
}

init();
