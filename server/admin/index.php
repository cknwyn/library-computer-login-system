<?php
// ============================================================
// Admin Login Page — /admin/index.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if (admin_is_logged_in()) redirect(admin_url('dashboard.php'));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = admin_login(
        trim($_POST['username'] ?? ''),
        $_POST['password'] ?? ''
    );
    if ($admin) {
        log_activity('ADMIN_LOGIN', 'Admin panel access', null, $admin['id']);
        redirect(admin_url('dashboard.php'));
    } else {
        $error = 'Invalid username or password.';
    }
}

$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css?v=1.1.2">
  <script src="https://unpkg.com/lucide@latest"></script>

</head>
<body>
<div class="login-page" style="background: var(--bg-base); display: flex; align-items: center; justify-content: center; min-height: 100vh;">
  <div class="login-card fade-in" style="background: white; padding: 48px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); width: 100%; max-width: 400px; border: 1px solid var(--border-light);">
    <div class="login-logo" style="text-align: center; margin-bottom: 32px;">
      <div class="sidebar-logo logo-image" style="width: 320px; height: auto; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
        <img src="<?= ASSETS_URL ?>/img/auf_ul_logo.png" alt="AUF Logo" style="width:100%;height:auto;object-fit:contain">
      </div>
    </div>

    <?php if ($expired): ?>
      <div class="flash flash-info" style="font-size: 13px; margin-bottom: 24px;">⏱ Your session expired. Please log in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="flash flash-error" style="font-size: 13px; margin-bottom: 24px;">
        <i data-lucide="alert-circle" style="width:16px;vertical-align:middle;margin-right:4px"></i> <?= h($error) ?>
      </div>
    <?php endif; ?>


    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input
          id="username" name="username" type="text"
          class="form-control" placeholder="admin"
          value="<?= h($_POST['username'] ?? '') ?>"
          autocomplete="username" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input
          id="password" name="password" type="password"
          class="form-control" placeholder="••••••••"
          autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; padding: 14px; margin-top: 8px;">
        Login
      </button>
    </form>

    <div style="text-align:center;margin-top:32px;font-size:11px;color:var(--text-faint); font-weight: 600;">
      SYSTEM VERSION <?= APP_VERSION ?>
    </div>
  </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>

