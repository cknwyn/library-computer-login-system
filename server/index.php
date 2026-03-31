<?php
// Root index — redirect to admin login
require_once __DIR__ . '/config.php';
header('Location: ' . APP_URL . '/admin/index.php');
exit;
