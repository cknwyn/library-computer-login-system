// ============================================================
// Library Kiosk — Main Process
// Manages the BrowserWindow and kiosk lockdown
// ============================================================
'use strict';

const { app, BrowserWindow, globalShortcut, ipcMain, dialog } = require('electron');
const path          = require('path');
const http          = require('http');
const { execSync, spawn } = require('child_process');
const dotenv = require('dotenv');

// Load .env file from the client root
dotenv.config({ path: path.join(__dirname, '..', '.env') });

// ── Config ────────────────────────────────────────────────────
const KIOSK_MODE        = process.env.KIOSK_MODE !== 'false';  // Default: true
const API_BASE_URL      = process.env.API_BASE_URL || 'http://localhost/library-system/api';
const TERMINAL_CODE     = process.env.TERMINAL_CODE || 'LOCAL-TEST';
const HEARTBEAT_SECONDS = parseInt(process.env.HEARTBEAT_INTERVAL || '30', 10);

let mainWindow  = null;
let isQuitting  = false;  // flag to distinguish user-close from programmatic quit

// ── Graceful logout helper (called on window close / app quit) ─
async function attemptGracefulLogout() {
  if (!mainWindow) return;
  try {
    const token = await mainWindow.webContents.executeJavaScript(
      'sessionStorage.getItem("session_token")'
    );
    if (!token) return;

    // Fire-and-forget HTTP logout using Node's built-in http module
    // (can't use fetch here — we're in the main process)
    const apiUrl = new URL(API_BASE_URL + '/logout.php');
    await new Promise((resolve) => {
      const body = JSON.stringify({});
      const req  = http.request({
        hostname: apiUrl.hostname,
        port:     apiUrl.port || 80,
        path:     apiUrl.pathname,
        method:   'POST',
        headers:  {
          'Content-Type':    'application/json',
          'Content-Length':  Buffer.byteLength(body),
          'X-Session-Token': token,
        },
        timeout: 2000,   // give up after 2 s
      }, resolve);
      req.on('error',   resolve);  // resolve on error — we're quitting anyway
      req.on('timeout', resolve);
      req.write(body);
      req.end();
    });

    console.log('[Main] Graceful logout sent for token', token.slice(0, 8) + '…');
  } catch (e) {
    console.warn('[Main] Graceful logout failed:', e.message);
  }
}

// ── Kiosk OS-level protections (KIOSK_MODE only) ─────────────
function applyKioskProtections() {
  // 1. Disable Task Manager for this user via registry
  try {
    execSync(
      'reg add "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\System" /v DisableTaskMgr /t REG_DWORD /d 1 /f',
      { stdio: 'ignore' }
    );
    console.log('[Main] Task Manager disabled via registry.');
  } catch (e) {
    console.warn('[Main] Could not disable Task Manager:', e.message);
  }

  // 2. Spawn a detached watchdog that survives this process
  //    (secondary protection — the Scheduled Task watchdog is the primary one)
  try {
    const watchdogScript = path.join(__dirname, '..', '..', 'watchdog', 'watchdog.ps1');
    const watchdog = spawn(
      'powershell.exe',
      [
        '-ExecutionPolicy', 'Bypass',
        '-WindowStyle',     'Hidden',
        '-NonInteractive',
        '-File',            watchdogScript,
        '-ExePath',         process.execPath,
      ],
      { detached: true, stdio: 'ignore' }
    );
    watchdog.unref(); // detach — survives if Electron is killed
    console.log('[Main] Detached watchdog spawned.');
  } catch (e) {
    console.warn('[Main] Could not spawn watchdog process:', e.message);
  }
}

function releaseKioskProtections() {
  // Re-enable Task Manager on clean exit so IT staff aren't locked out
  try {
    execSync(
      'reg add "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\System" /v DisableTaskMgr /t REG_DWORD /d 0 /f',
      { stdio: 'ignore' }
    );
    console.log('[Main] Task Manager re-enabled.');
  } catch (e) {
    console.warn('[Main] Could not re-enable Task Manager:', e.message);
  }
}


// ── Create Window ─────────────────────────────────────────────
function createWindow() {
  mainWindow = new BrowserWindow({
    width:          1024,
    height:         768,
    fullscreen:     KIOSK_MODE,
    kiosk:          KIOSK_MODE,
    frame:          false,              // Always frameless — kiosk has its own UI chrome
    resizable:      !KIOSK_MODE,
    alwaysOnTop:    KIOSK_MODE,
    autoHideMenuBar: true,
    title:          'Library Computer System',
    backgroundColor:'#0B0D14',
    webPreferences: {
      preload:          path.join(__dirname, 'preload.js'),
      nodeIntegration:  false,
      contextIsolation: true,
      sandbox:          true,
      webSecurity:      true,
    },
  });

  // Load the login page
  mainWindow.loadFile(path.join(__dirname, 'renderer', 'login.html'));

  // Open DevTools only in dev mode
  if (!KIOSK_MODE) {
    mainWindow.webContents.openDevTools({ mode: 'detach' });
  }

  // Prevent navigation to other pages via the renderer
  mainWindow.webContents.on('will-navigate', (event, url) => {
    const allowed = ['login.html', 'dashboard.html', 'session-bar.html'];
    const isAllowed = allowed.some(p => url.includes(p));
    if (!isAllowed) {
      console.warn('[Main] Blocked navigation to:', url);
      event.preventDefault();
    }
  });

  // Prevent opening new windows
  mainWindow.webContents.setWindowOpenHandler(() => ({ action: 'deny' }));

  // Graceful close: intercept window close → logout → then really quit
  mainWindow.on('close', async (event) => {
    if (isQuitting) return;           // already handling quit, let it close
    event.preventDefault();           // hold the close
    isQuitting = true;
    await attemptGracefulLogout();
    mainWindow.destroy();             // force-destroy after logout attempt
  });

  mainWindow.on('closed', () => { mainWindow = null; });
}

// ── App lifecycle ─────────────────────────────────────────────
app.whenReady().then(() => {
  createWindow();

  // Block all system shortcuts in kiosk mode
  if (KIOSK_MODE) {
    globalShortcut.registerAll(
      ['Alt+F4', 'Alt+Tab', 'Super', 'F11', 'Ctrl+Alt+Delete',
       'Ctrl+W', 'Ctrl+Shift+I', 'Ctrl+R', 'F5', 'Ctrl+F5',
       'Ctrl+Shift+Escape'],  // <-- also block Task Manager shortcut
      () => {}
    );
    applyKioskProtections();
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('before-quit', async (event) => {
  if (!isQuitting) {
    isQuitting = true;
    event.preventDefault();
    await attemptGracefulLogout();
    if (KIOSK_MODE) releaseKioskProtections();
    app.exit(0);
  }
});


app.on('will-quit', () => {
  globalShortcut.unregisterAll();
});

// On macOS, keep app running even when all windows are closed
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

// ── IPC Handlers (renderer → main) ───────────────────────────

/**
 * Navigate to the compact always-on-top session bar after a successful login.
 * Exits kiosk/fullscreen so the user can use the PC freely beneath it.
 */
ipcMain.handle('navigate-to-session-bar', async () => {
  if (!mainWindow) return false;

  const { screen } = require('electron');
  const { width }  = screen.getPrimaryDisplay().workAreaSize;

  // Exit kiosk / fullscreen so the window can be resized
  if (KIOSK_MODE) {
    mainWindow.setKiosk(false);
    mainWindow.setFullScreen(false);
  }

  // Resize to a thin top bar
  mainWindow.setResizable(true);
  mainWindow.setMinimizable(false);
  mainWindow.setSize(width, 60);
  mainWindow.setPosition(0, 0);
  mainWindow.setResizable(false);
  mainWindow.setAlwaysOnTop(true, 'screen-saver');

  await mainWindow.loadFile(path.join(__dirname, 'renderer', 'session-bar.html'));
  return true;
});

/**
 * Navigate to the dashboard after a successful login (legacy — kept for reference).
 */
ipcMain.handle('navigate-to-dashboard', async () => {
  if (mainWindow) {
    await mainWindow.loadFile(path.join(__dirname, 'renderer', 'dashboard.html'));
  }
  return true;
});

/**
 * Navigate back to the login screen after logout.
 * Restores fullscreen / kiosk mode.
 */
ipcMain.handle('navigate-to-login', async () => {
  if (!mainWindow) return false;

  mainWindow.setAlwaysOnTop(false);

  if (KIOSK_MODE) {
    mainWindow.setResizable(true);
    mainWindow.setFullScreen(true);
    mainWindow.setKiosk(true);
  } else {
    mainWindow.setResizable(true);
    mainWindow.setSize(1024, 768);
    mainWindow.center();
  }

  await mainWindow.loadFile(path.join(__dirname, 'renderer', 'login.html'));
  return true;
});

/**
 * Expose config values to the renderer (via preload).
 */
ipcMain.handle('get-config', () => ({
  apiBaseUrl:        API_BASE_URL,
  terminalCode:      TERMINAL_CODE,
  heartbeatSeconds:  HEARTBEAT_SECONDS,
  kioskMode:         KIOSK_MODE,
}));

/**
 * Show a native confirm dialog (for logout confirmation).
 */
ipcMain.handle('show-confirm', async (event, message) => {
  const result = await dialog.showMessageBox(mainWindow, {
    type:    'question',
    buttons: ['Cancel', 'Logout'],
    defaultId: 0,
    message: message,
    title:   'Confirm',
  });
  return result.response === 1; // true = user chose "Logout"
});
