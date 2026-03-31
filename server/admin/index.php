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
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-card fade-in">
    <div class="login-logo">
      <div class="login-logo-icon">📚</div>
      <h1><?= h(APP_NAME) ?></h1>
      <p>Administrator Portal</p>
    </div>

    <?php if ($expired): ?>
      <div class="flash flash-info">⏱ Your session expired. Please log in again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="flash flash-error">⚠ <?= h($error) ?></div>
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
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:8px;">
        Sign In →
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:11px;color:var(--text-faint);">
      Library Computer Login System v<?= APP_VERSION ?>
    </p>
  </div>
</div>
</body>
</html>
