<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (current_user()) redirect(BASE_URL . '/admin/index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $st->execute([$_POST['username'] ?? '']);
    $row = $st->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password_hash'])) {
        login_user($row['id']);
        log_activity($row['id'], 'login', 'Login ke dashboard');
        redirect(BASE_URL . '/admin/index.php');
    }
    $err = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · Kinar Resto</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="login-wrap">
  <form class="login-card" method="post">
    <div class="brand" style="color:var(--green)"><span class="leaf">🍃</span><span>Kinar Resto<small style="color:var(--muted)">Portal Manajemen</small></span></div>
    <h1>Selamat Datang</h1>
    <p>Login melalui jaringan lokal restoran.</p>
    <?php if ($err): ?><div class="flash error"><?= e($err) ?></div><?php endif; ?>
    <div style="margin-bottom:14px"><label class="lbl">Username</label>
      <input type="text" name="username" required autofocus></div>
    <div style="margin-bottom:20px"><label class="lbl">Password</label>
      <input type="password" name="password" required></div>
    <button class="btn btn-green" style="width:100%;justify-content:center" type="submit">Masuk ke Dashboard</button>
  </form>
</div>
</body>
</html>
