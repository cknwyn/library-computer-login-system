// ============================================================
// Library Kiosk — Preload Script
// Secure bridge between the main process and the renderer.
// Only exposes the APIs we deliberately allow.
// ============================================================
'use strict';

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('kiosk', {
  /** Get environment config from main process */
  getConfig: () => ipcRenderer.invoke('get-config'),

  /** Navigate to the compact session bar after login (user can then freely use the PC) */
  goToSessionBar: () => ipcRenderer.invoke('navigate-to-session-bar'),

  /** Navigate to full dashboard (legacy) */
  goToDashboard: () => ipcRenderer.invoke('navigate-to-dashboard'),

  /** Navigate back to login after logout */
  goToLogin: () => ipcRenderer.invoke('navigate-to-login'),

  /** Show a native confirm dialog and return true/false */
  confirm: (message) => ipcRenderer.invoke('show-confirm', message),
});
