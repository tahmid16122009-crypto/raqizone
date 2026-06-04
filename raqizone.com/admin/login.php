<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

if (is_admin()) { header('Location: /admin/products'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (admin_login(trim($_POST['password'] ?? ''))) {
        header('Location: /admin/products'); exit;
    }
    $error = 'ভুল পাসওয়ার্ড!';
}

$cfg = get_all_settings();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login</title>
<link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
<div class="alb">
  <div class="alc">
    <div class="alic">🔐</div>
    <h2>Admin Login</h2>
    <p>Raqizone Admin Panel</p>
    <?php if ($error): ?>
    <div class="aerr"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="/admin/login" method="POST" class="alf">
      <input type="password" name="password" class="ai" placeholder="পাসওয়ার্ড দিন" required autofocus>
      <button type="submit" class="abg" style="width:100%;justify-content:center">লগিন করুন →</button>
    </form>
  </div>
</div>
</body>
</html>