<?php
$nav = 'tables'; $page_title = 'Meja & QR';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/qrcode.php';

// buat meja baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_table') {
    $code = strtoupper(trim($_POST['table_code']));
    $label = trim($_POST['label']);
    $cap = (int)$_POST['capacity'];
    $token = 'tbl-' . bin2hex(random_bytes(8));
    db()->prepare('INSERT INTO resto_tables (table_code, label, capacity, qr_token) VALUES (?,?,?,?)')
        ->execute([$code, $label, $cap, $token]);
    flash("Meja $label dibuat. QR siap dicetak.");
    redirect(BASE_URL . '/admin/tables.php');
}
$tables = db()->query('SELECT t.*, (SELECT COUNT(*) FROM table_sessions s WHERE s.table_id=t.id AND s.status="aktif") aktif FROM resto_tables t ORDER BY t.table_code')->fetchAll();
?>
<div class="topbar">
  <div><div class="crumb">Manajemen Meja</div><h1>Meja &amp; QR Code</h1></div>
  <div class="spacer"></div>
</div>
<div class="grid-2">
  <div class="panel">
    <h2>➕ Tambah Meja</h2>
    <form method="post" class="form-grid">
      <input type="hidden" name="action" value="add_table">
      <div><label class="lbl">Kode Meja</label><input type="text" name="table_code" placeholder="M05" required></div>
      <div><label class="lbl">Label</label><input type="text" name="label" placeholder="Meja 5" required></div>
      <div class="full"><label class="lbl">Kapasitas</label><input type="number" name="capacity" value="4" min="1"></div>
      <div class="full"><button class="btn btn-green" type="submit">Buat Meja &amp; QR</button></div>
    </form>
  </div>
  <div class="panel">
    <h2>ℹ️ Cara Kerja QR Meja</h2>
    <p style="font-size:.88rem;color:var(--muted);line-height:1.6">
      Saat discan, QR membuka menu digital dengan parameter <code>table_id</code> dan
      <code>table_session_id</code>. Sistem otomatis membuka sesi meja aktif. Semua pesanan
      dari meja itu tergabung dalam satu sesi sehingga kasir dapat menagih gabungan.
    </p>
  </div>
</div>
<div class="panel">
  <h2>Daftar Meja &amp; QR</h2>
  <table class="data">
    <tr><th>QR Code</th><th>Meja</th><th>Kapasitas</th><th>Status</th><th>Sesi Aktif</th><th>URL QR</th></tr>
    <?php foreach ($tables as $t): ?>
    <?php $qr_url = BASE_URL . '/order/?token=' . $t['qr_token']; ?>
    <tr>
      <td><?= qr_svg($qr_url, 3) ?></td>
      <td><strong><?= e($t['label']) ?></strong><br><small style="color:var(--muted)"><?= e($t['table_code']) ?></small></td>
      <td><?= $t['capacity'] ?: '-' ?> org</td>
      <td><span class="pill <?= $t['status']=='kosong' ? 'p-ok' : 'p-warn' ?>"><span class="dot"></span><?= e($t['status']) ?></span></td>
      <td><?= $t['aktif'] ? '<span class="pill p-info">Ya</span>' : '-' ?></td>
      <td><small style="word-break:break-all;color:var(--muted)"><?= e($qr_url) ?></small></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
