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
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css?v=1.2.0">

  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-image" style="width: 40px; height: 40px; flex-shrink: 0;">
        <img src="<?= ASSETS_URL ?>/img/logo.png" alt="AUF Logo" style="width: 100%; height: 100%; object-fit: contain;">
      </div>

      <div>

        <h1>AUF Library</h1>
        <span>Admin System</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-label">Overview</span>
      <a href="dashboard.php" class="nav-item <?= $page==='dashboard' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span> Dashboard
      </a>

      <span class="nav-section-label">Management</span>
      <a href="users.php" class="nav-item <?= $page==='users' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="users"></i></span> Users
      </a>
      <a href="sessions.php" class="nav-item <?= $page==='sessions' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="monitor"></i></span> Sessions
      </a>
      <a href="terminals.php" class="nav-item <?= $page==='terminals' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="monitor-dot"></i></span> Terminals
      </a>
      <a href="apps.php" class="nav-item <?= $page==='apps' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="package-search"></i></span> App Requests
      </a>

      <span class="nav-section-label">Analytics</span>
      <a href="reports.php" class="nav-item <?= $page==='reports' ? 'active' : '' ?>">
        <span class="nav-icon"><i data-lucide="bar-chart-3"></i></span> Reports
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
        <i data-lucide="log-out" style="width:18px"></i> Sign Out
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

    <div class="page-content fade-in">

