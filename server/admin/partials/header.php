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
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">📚</div>
      <h1><?= h(APP_NAME) ?></h1>
      <span>Admin Portal</span>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-label">Overview</span>
      <a href="dashboard.php" class="nav-item <?= $page==='dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span> Dashboard
      </a>

      <span class="nav-section-label">Management</span>
      <a href="users.php" class="nav-item <?= $page==='users' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Users
      </a>
      <a href="sessions.php" class="nav-item <?= $page==='sessions' ? 'active' : '' ?>">
        <span class="nav-icon">🖥️</span> Sessions
      </a>
      <a href="terminals.php" class="nav-item <?= $page==='terminals' ? 'active' : '' ?>">
        <span class="nav-icon">💻</span> Terminals
      </a>
      <a href="apps.php" class="nav-item <?= $page==='apps' ? 'active' : '' ?>">
        <span class="nav-icon">📦</span> App Requests
      </a>

      <span class="nav-section-label">Reports</span>
      <a href="reports.php" class="nav-item <?= $page==='reports' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Reports
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
        <span>🚪</span> Sign Out
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
        <span id="live-clock" style="font-size:13px;color:var(--text-muted);font-variant-numeric:tabular-nums;"></span>
        <div class="live-badge"><span class="live-dot"></span> System Online</div>
      </div>
    </div>

    <div class="page-content fade-in">
