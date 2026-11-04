<?php
$nav = 'dashboard'; $page_title = 'Dashboard Operasional';
require_once __DIR__ . '/../includes/admin_header.php';

$today = date('Y-m-d');
$stats = [
  'menu_aktif'   => (int) db()->query("SELECT COUNT(*) FROM menu_items WHERE is_available=1")->fetchColumn(),
  'artikel'      => (int) db()->query("SELECT COUNT(*) FROM articles WHERE status='terbit'")->fetchColumn(),
  'reservasi'    => (int) db()->query("SELECT COUNT(*) FROM reservations WHERE status='baru'")->fetchColumn(),
  'pesanan_hari' => (int) db()->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=?")->execute([$today]) ?: (int) db()->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)='$today'")->fetchColumn(),
];
$penjualan = (int) db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)='$today' AND status IN ('siap','disajikan','selesai')")->fetchColumn();
$meja_terisi = (int) db()->query("SELECT COUNT(*) FROM resto_tables WHERE status!='kosong'")->fetchColumn();
$total_meja  = (int) db()->query("SELECT COUNT(*) FROM resto_tables WHERE table_code!='TAKEAWAY'")->fetchColumn();

// Grafik penjualan 7 hari terakhir
$rows = db()->query("SELECT DATE(created_at) d, SUM(total) s FROM orders
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status NOT IN ('dibatalkan')
  GROUP BY d ORDER BY d")->fetchAll();
$map = array_column($rows, 's', 'd');
$days = []; $vals = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[] = date('d/m', strtotime($d));
    $vals[] = (int)($map[$d] ?? 0);
}
$max = max($vals) ?: 1;
?>
<div class="topbar">
  <div><div class="crumb">Pusat Kendali Restoran</div><h1>Dashboard Operasional &amp; Manajemen Konten</h1></div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php" target="_blank">🌐 Lihat Website Publik</a>
  <a class="btn btn-wine" href="<?= BASE_URL ?>/admin/menu.php?new=1">＋ Entri Baru Cepat</a>
</div>

<div class="cards">
  <div class="card"><span class="kpi-icon ic-green">🍽️</span>
    <div class="kpi-label">Katalog Sajian Aktif</div><div class="kpi-value"><?= $stats['menu_aktif'] ?></div>
    <div class="kpi-sub">dari total menu katalog</div></div>
  <div class="card"><span class="kpi-icon ic-blue">📰</span>
    <div class="kpi-label">Artikel Terbit</div><div class="kpi-value"><?= $stats['artikel'] ?></div>
    <div class="kpi-sub">warta kuliner website</div></div>
  <div class="card"><span class="kpi-icon ic-wine">📅</span>
    <div class="kpi-label">Reservasi Baru</div><div class="kpi-value"><?= $stats['reservasi'] ?></div>
    <div class="kpi-sub">perlu konfirmasi</div></div>
  <div class="card"><span class="kpi-icon ic-gold">📦</span>
    <div class="kpi-label">Pesanan Hari Ini</div><div class="kpi-value"><?= $stats['pesanan_hari'] ?></div>
    <div class="kpi-sub">penjualan <?= rupiah($penjualan) ?></div></div>
  <div class="card"><span class="kpi-icon ic-green">🪑</span>
    <div class="kpi-label">Status Operasional</div><div class="kpi-value" style="font-size:1.2rem;padding-top:8px">
      <span class="pill <?= get_setting('resto_open','1') ? 'p-ok':'p-off' ?>"><span class="dot"></span><?= get_setting('resto_open','1') ? 'Buka Normal' : 'Tutup' ?></span></div>
    <div class="kpi-sub">Meja terisi <?= $meja_terisi ?>/<?= $total_meja ?></div></div>
</div>

<div class="grid-2">
  <div class="panel">
    <h2>Grafik Penjualan 7 Hari <span class="tag">Harian</span></h2>
    <div class="chart-wrap"><div class="chart-bars">
      <?php foreach ($vals as $i => $v): ?>
        <div class="bar <?= $i === 6 ? 'wine' : '' ?>" style="height:<?= max(4, round($v / $max * 150)) ?>px" title="<?= rupiah($v) ?>"><span><?= e($days[$i]) ?></span></div>
      <?php endforeach; ?>
    </div></div>
  </div>
  <div class="panel">
    <h2>Aktivitas Terbaru</h2>
    <table class="data">
      <tr><th>Waktu</th><th>Aksi</th><th>Detail</th></tr>
      <?php foreach (db()->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT 6")->fetchAll() as $a): ?>
      <tr><td><?= e(date('d/m H:i', strtotime($a['created_at']))) ?></td><td><?= e($a['action']) ?></td><td><?= e(mb_strimwidth($a['detail'],0,60)) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<div class="panel">
  <h2>Aksi Cepat</h2>
  <a class="btn btn-green btn-sm" href="<?= BASE_URL ?>/admin/menu.php">🍽️ Kelola Menu</a>
  <a class="btn btn-green btn-sm" href="<?= BASE_URL ?>/admin/pos.php">🧾 Buka Kasir</a>
  <a class="btn btn-green btn-sm" href="<?= BASE_URL ?>/admin/kitchen.php">🍳 Antrean Dapur</a>
  <a class="btn btn-wine btn-sm" href="<?= BASE_URL ?>/admin/articles.php?new=1">📰 Tulis Artikel</a>
  <a class="btn btn-wine btn-sm" href="<?= BASE_URL ?>/admin/branding.php">🎨 Sinkronkan Web Publik</a>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
