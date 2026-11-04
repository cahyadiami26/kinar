<?php
$nav = 'reports'; $page_title = 'Laporan Keuangan';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin','kasir');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$where = "DATE(o.created_at) BETWEEN ? AND ? AND o.status NOT IN ('dibatalkan')";

$total = db()->prepare("SELECT COALESCE(SUM(total),0) FROM orders o WHERE $where");
$total->execute([$from, $to]); $grand = (int)$total->fetchColumn();

$by_method = db()->prepare("SELECT p.method, COUNT(*) n, SUM(p.amount_paid) s FROM payments p
    JOIN orders o ON o.id=p.order_id WHERE $where GROUP BY p.method");
$by_method->execute([$from, $to]);

$by_cashier = db()->prepare("SELECT u.name, COUNT(*) n, SUM(p.amount_paid) s FROM payments p
    JOIN orders o ON o.id=p.order_id JOIN users u ON u.id=p.cashier_id
    WHERE $where GROUP BY u.name");
$by_cashier->execute([$from, $to]);

$daily = db()->prepare("SELECT DATE(o.created_at) d, COUNT(*) n, SUM(o.total) s FROM orders o
    WHERE $where GROUP BY d ORDER BY d");
$daily->execute([$from, $to]);

$top_menu = db()->prepare("SELECT m.name, SUM(oi.qty) q, SUM(oi.qty*oi.price) s FROM order_items oi
    JOIN orders o ON o.id=oi.order_id JOIN menu_items m ON m.id=oi.menu_id
    WHERE $where GROUP BY m.name ORDER BY q DESC LIMIT 8");
$top_menu->execute([$from, $to]);
?>
<div class="topbar">
  <div><div class="crumb">Laporan Keuangan</div><h1>Laporan Penjualan &amp; Transaksi</h1></div>
</div>
<div class="panel">
  <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <div><label class="lbl">Dari</label><input type="date" name="from" value="<?= e($from) ?>" style="width:170px"></div>
    <div><label class="lbl">Sampai</label><input type="date" name="to" value="<?= e($to) ?>" style="width:170px"></div>
    <button class="btn btn-green btn-sm" type="submit">Terapkan Periode</button>
    <span class="pill p-ok" style="font-size:.9rem">Total: <?= rupiah($grand) ?></span>
  </form>
</div>
<div class="grid-2">
  <div class="panel"><h2>📊 Penjualan Harian</h2>
    <table class="data"><tr><th>Tanggal</th><th>Transaksi</th><th>Omzet</th></tr>
      <?php foreach ($daily->fetchAll() as $d): ?>
      <tr><td><?= date('d/m/Y', strtotime($d['d'])) ?></td><td><?= $d['n'] ?></td><td><?= rupiah($d['s']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  <div class="panel"><h2>💳 Per Metode Pembayaran</h2>
    <table class="data"><tr><th>Metode</th><th>Transaksi</th><th>Nominal</th></tr>
      <?php foreach ($by_method->fetchAll() as $m): ?>
      <tr><td><?= e(ucfirst($m['method'])) ?></td><td><?= $m['n'] ?></td><td><?= rupiah($m['s']) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <h2 style="margin-top:18px">🧑‍🍳 Per Kasir</h2>
    <table class="data"><tr><th>Kasir</th><th>Transaksi</th><th>Nominal</th></tr>
      <?php foreach ($by_cashier->fetchAll() as $c): ?>
      <tr><td><?= e($c['name']) ?></td><td><?= $c['n'] ?></td><td><?= rupiah($c['s']) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
</div>
<div class="panel"><h2>🏆 Menu Terlaris</h2>
  <table class="data"><tr><th>Menu</th><th>Terjual</th><th>Pendapatan</th></tr>
    <?php foreach ($top_menu->fetchAll() as $t): ?>
    <tr><td><?= e($t['name']) ?></td><td><?= $t['q'] ?></td><td><?= rupiah($t['s']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
