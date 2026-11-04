<?php require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
$nav = $nav ?? 'dashboard';
function nav_item($key, $label, $url, $icon = '', $badge = null) {
    global $nav;
    $b = $badge !== null ? ' <span class="badge">' . (int)$badge . '</span>' : '';
    return '<a class="' . ($nav === $key ? 'active' : '') . '" href="' . BASE_URL . '/admin/' . $url . '">' . $icon . '<span class="txt">' . e($label) . '</span>' . $b . '</a>';
}
$open_orders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('menunggu','dikonfirmasi','diproses')")->fetchColumn();
$new_resv = (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status='baru'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? 'Dashboard') ?> · Kinar Resto</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="layout">
<aside class="sidebar">
  <div class="brand"><span class="leaf">🍃</span><span>Kinar Resto<small>Portal Manajemen</small></span></div>
  <div class="nav-title">Menu Utama</div>
  <nav class="nav">
    <?= nav_item('dashboard','Dashboard','index.php','📊') ?>
    <?= nav_item('pos','Kasir & POS','pos.php','🧾',$open_orders ?: null) ?>
    <?= nav_item('kitchen','Dapur & Bar','kitchen.php','🍳') ?>
    <?= nav_item('tables','Meja & QR','tables.php','🪑') ?>
    <?= nav_item('menu','Kelola Menu','menu.php','🍽️') ?>
    <?= nav_item('articles','Warta Kuliner','articles.php','📰') ?>
    <?= nav_item('branding','Branding & Web','branding.php','🎨') ?>
    <?= nav_item('reservations','Reservasi & Kontak','reservations.php','📅',$new_resv ?: null) ?>
    <?= nav_item('printers','Printer LAN','printers.php','🖨️') ?>
    <?= nav_item('reports','Laporan','reports.php','📈') ?>
    <?= nav_item('settings','Pengaturan','settings.php','⚙️') ?>
  </nav>
  <div class="status-card">
    <strong>Status Operasional</strong><br>
    <?php $open = get_setting('resto_open', '1'); ?>
    <span class="pill <?= $open ? 'p-ok' : 'p-off' ?>"><span class="dot"></span><?= $open ? 'Buka Normal' : 'Tutup' ?></span>
  </div>
  <div class="user"><span>👤</span><span class="txt"><?= e($u['name']) ?><br><small><?= e($u['role']) ?></small></span></div>
</aside>
<main class="main">
<?php if ($f = get_flash()): ?>
  <div class="flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endif; ?>
