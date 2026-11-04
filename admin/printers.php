<?php
$nav = 'printers'; $page_title = 'Printer LAN';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/printer.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $st = db()->prepare('UPDATE printers SET name=?, location=?, ip_address=?, port=?, type=?, is_active=?, mode=? WHERE id=?');
    $st->execute([$_POST['name'], $_POST['location'], $_POST['ip_address'], (int)$_POST['port'], $_POST['type'],
        isset($_POST['is_active']) ? 1 : 0, $_POST['mode'], $id]);
    flash('Pengaturan printer diperbarui.');
    redirect(BASE_URL . '/admin/printers.php');
}
if (($_GET['reprint'] ?? 0) > 0) {
    $job = db()->prepare('SELECT * FROM print_jobs WHERE id=?'); $job->execute([(int)$_GET['reprint']]);
    if ($j = $job->fetch()) {
        if (printer_send($j['printer_id'], $j['payload']))
            db()->prepare('UPDATE print_jobs SET status="tercetak", printed_at=NOW(), attempts=attempts+1 WHERE id=?')->execute([$j['id']]);
        else
            db()->prepare('UPDATE print_jobs SET status="gagal", attempts=attempts+1 WHERE id=?')->execute([$j['id']]);
    }
    flash('Permintaan cetak ulang dikirim.');
    redirect(BASE_URL . '/admin/printers.php');
}
$printers = db()->query('SELECT * FROM printers')->fetchAll();
$jobs = db()->query('SELECT j.*, p.name AS printer_name, p.location FROM print_jobs j JOIN printers p ON p.id=j.printer_id ORDER BY j.id DESC LIMIT 15')->fetchAll();
?>
<div class="topbar"><div><div class="crumb">Pengaturan Perangkat</div><h1>Printer Thermal LAN</h1></div></div>
<div class="grid-2">
  <div class="panel">
    <h2>⚠️ Mode Pengiriman Pesanan</h2>
    <p style="font-size:.85rem;color:var(--muted);line-height:1.6;margin-bottom:10px">
      <strong>Mode "Semua pesanan ke semua printer"</strong> (2 lokasi: kasir &amp; dapur) — seluruh pesanan dikirim ke semua printer.<br><br>
      <strong>Mode "Makanan ke dapur &amp; minuman ke bar"</strong> — pesanan makanan dicetak di printer dapur, pesanan minuman di printer bar.
    </p>
  </div>
  <div class="panel">
    <h2>Daftar Printer</h2>
    <?php foreach ($printers as $p): ?>
    <form method="post" class="card" style="margin-bottom:12px;box-shadow:var(--shadow-soft)">
      <input type="hidden" name="id" value="<?= $p['id'] ?>">
      <div class="form-grid">
        <div><label class="lbl">Nama</label><input type="text" name="name" value="<?= e($p['name']) ?>"></div>
        <div><label class="lbl">Lokasi</label>
          <select name="location"><option value="kasir" <?= $p['location']=='kasir'?'selected':'' ?>>Kasir</option>
          <option value="dapur" <?= $p['location']=='dapur'?'selected':'' ?>>Dapur</option>
          <option value="bar" <?= $p['location']=='bar'?'selected':'' ?>>Bar</option></select></div>
        <div><label class="lbl">IP Address</label><input type="text" name="ip_address" value="<?= e($p['ip_address']) ?>"></div>
        <div><label class="lbl">Port</label><input type="number" name="port" value="<?= $p['port'] ?>"></div>
        <div><label class="lbl">Jenis</label><input type="text" name="type" value="<?= e($p['type']) ?>"></div>
        <div><label class="lbl">Mode</label>
          <select name="mode"><option value="semua" <?= $p['mode']=='semua'?'selected':'' ?>>Semua ke semua printer</option>
          <option value="makanan_ke_dapur_minuman_ke_bar" <?= $p['mode']=='makanan_ke_dapur_minuman_ke_bar'?'selected':'' ?>>Makanan→Dapur, Minuman→Bar</option></select></div>
        <div class="full"><label class="switch"><input type="checkbox" name="is_active" <?= $p['is_active']?'checked':'' ?>><span class="slider"></span></label> <small style="color:var(--muted)"> aktif</small></div>
      </div>
      <button class="btn btn-green btn-sm" style="margin-top:10px">Simpan</button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<div class="panel">
  <h2>Antrean Cetak Terbaru</h2>
  <table class="data">
    <tr><th>Waktu</th><th>Printer</th><th>Pesanan</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($jobs as $j): ?>
    <tr><td><?= e(date('d/m H:i:s', strtotime($j['created_at']))) ?></td><td><?= e($j['printer_name']) ?> (<?= e($j['location']) ?>)</td>
      <td>#<?= $j['order_id'] ?></td>
      <td><span class="pill <?= $j['status']=='tercetak'?'p-ok':($j['status']=='gagal'?'p-off':'p-warn') ?>"><?= e($j['status']) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="?reprint=<?= $j['id'] ?>">🔁 Cetak Ulang</a></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
