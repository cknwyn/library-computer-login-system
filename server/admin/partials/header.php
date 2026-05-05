<?php
// ============================================================
// Admin — Shared Header partial
// Usage: include __DIR__ . '/partials/header.php';
// Requires $page (string) and $admin from parent scope
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= ucfirst($page) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css?v=1.2.4">

  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .btn-create { background: #10B981 !important; color: white !important; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
    .btn-create:hover { background: #059669 !important; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3); }

    .btn-edit { background: #6366F1 !important; color: white !important; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2); }
    .btn-edit:hover { background: #4F46E5 !important; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3); }

    .btn-delete { background: #EF4444 !important; color: white !important; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }
    .btn-delete:hover { background: #DC2626 !important; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3); }
  </style>
</head>
<body>
<div class="layout" id="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="main-sidebar">
    <div class="sidebar-header" style="display: flex; justify-content: flex-end; padding: 12px 12px 0;">
      <button class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Toggle Sidebar">
        <i data-lucide="menu"></i>
      </button>
    </div>
    <div class="sidebar-logo" style="justify-content: center; padding: 10px 24px 40px;">
      <div class="logo-image" style="width: 210px; height: auto; flex-shrink: 0;">
        <img src="<?= ASSETS_URL ?>/img/auf_ul_logo.png" alt="AUF Logo" class="logo-wide" style="width: 100%; height: auto; object-fit: contain;">
        <img src="<?= ASSETS_URL ?>/img/logo.png" alt="AUF Logo" class="logo-compact">
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-label">Overview</span>
      <a href="dashboard.php" class="nav-item <?= $page==='dashboard' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span> <span>Dashboard</span>
      </a>

      <span class="nav-section-label">Management</span>
      <a href="users.php" class="nav-item <?= $page==='users' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="users"></i></span> <span>Users</span>
      </a>
      <a href="sessions.php" class="nav-item <?= $page==='sessions' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="monitor"></i></span> <span>Sessions</span>
      </a>
      <a href="terminals.php" class="nav-item <?= $page==='terminals' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="monitor-dot"></i></span> <span>Terminals</span>
      </a>
      <a href="websites.php" class="nav-item <?= $page==='websites' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="globe"></i></span> <span>Website Tracking</span>
      </a>
      <a href="classifications.php" class="nav-item <?= $page==='classifications' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="layers"></i></span> <span>Classifications</span>
      </a>

      <span class="nav-section-label">Analytics</span>
      <a href="reports.php" class="nav-item <?= $page==='reports' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="bar-chart-3"></i></span> <span>Reports</span>
      </a>
      <a href="logs.php" class="nav-item <?= $page==='logs' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="history"></i></span> <span>System Logs</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-chip">
        <div class="admin-avatar"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
        <div class="admin-info">
          <div class="admin-name"><?= h($admin['name']) ?></div>
          <div class="admin-role">Administrator</div>
        </div>
      </div>
      <a href="logout.php" class="btn-logout">
        <i data-lucide="log-out" style="width:18px"></i> <span>Sign Out</span>
      </a>
    </div>
  </aside>

  <!-- Main content -->
  <div class="main-content">
    <div class="topbar">
      <div>
        <div class="topbar-title"><?= ucfirst($page) ?></div>
        <div class="topbar-subtitle"><?= date('l, F j, Y') ?></div>
      </div>
      <div class="topbar-right">
        <span id="live-clock" style="font-size:13px;color:var(--text-muted);font-variant-numeric:tabular-nums;font-weight:600"></span>
        <div class="live-badge"><span class="live-dot"></span> System Live</div>
      </div>
    </div>

    <script>
      function toggleSidebar() {
          const sidebar = document.getElementById('main-sidebar');
          const layout = document.getElementById('app-layout');
          
          if (window.innerWidth <= 640) {
              sidebar.classList.toggle('open');
          } else {
              sidebar.classList.toggle('collapsed');
              layout.classList.toggle('collapsed-sidebar');
              // Save preference
              localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
          }
      }

      // Apply saved preference on load
      (function() {
          if (window.innerWidth > 1024) {
              const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
              if (isCollapsed) {
                  document.getElementById('main-sidebar').classList.add('collapsed');
                  document.getElementById('app-layout').classList.add('collapsed-sidebar');
              }
          }
      })();
    </script>

    <div class="page-content fade-in">

