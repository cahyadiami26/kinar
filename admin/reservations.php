<?php
$nav = 'reservations'; $page_title = 'Reservasi & Kontak';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin','kasir');

if (($id = (int)($_GET['set'] ?? 0)) > 0 && ($_GET['to'] ?? '') !== '') {
    db()->prepare('UPDATE reservations SET status=? WHERE id=?')->execute([$_GET['to'], $id]);
    log_activity($u['id'], 'reservation.' . $_GET['to'], "Reservasi #$id");
    flash('Status reservasi diperbarui.');
    redirect(BASE_URL . '/admin/reservations.php');
}
if (($del = (int)($_GET['del_msg'] ?? 0)) > 0) {
    db()->prepare('DELETE FROM contact_messages WHERE id=?')->execute([$del]);
    redirect(BASE_URL . '/admin/reservations.php');
}
$resv = db()->query('SELECT * FROM reservations ORDER BY id DESC')->fetchAll();
$msgs = db()->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 20')->fetchAll();
$cap = (int) db()->query("SELECT COALESCE(SUM(capacity),0) FROM resto_tables WHERE table_code!='TAKEAWAY'")->fetchColumn();
?>
<div class="topbar">
  <div><div class="crumb">Reservasi &amp; Kontak</div><h1>Permintaan Reservasi Meja</h1></div>
  <div class="spacer"></div>
  <span class="pill p-info">Kapasitas total: <?= $cap ?> kursi</span>
</div>
<div class="panel">
  <h2>📅 Daftar Reservasi <span class="tag"><?= count($resv) ?></span></h2>
  <table class="data">
    <tr><th>Nama</th><th>Kontak</th><th>Tanggal & Jam</th><th>Tamu</th><th>Permintaan</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($resv as $r): ?>
    <tr>
      <td><strong><?= e($r['name']) ?></strong></td><td><?= e($r['contact']) ?></td>
      <td><?= date('d/m/Y', strtotime($r['date'])) ?> · <?= date('H:i', strtotime($r['time'])) ?></td>
      <td><?= $r['guests'] ?> org</td><td><?= e(mb_strimwidth($r['request'],0,40)) ?></td>
      <td><span class="pill <?= ['baru'=>'p-warn','dikonfirmasi'=>'p-ok','ditolak'=>'p-off','hadir'=>'p-info','tidak_hadir'=>'p-off'][$r['status']] ?>"><?= e($r['status']) ?></span></td>
      <td>
        <?php if ($r['status'] === 'baru'): ?>
          <a class="btn btn-green btn-sm" href="?set=<?= $r['id'] ?>&to=dikonfirmasi">✔ Konfirmasi</a>
          <a class="btn btn-ghost btn-sm" href="?set=<?= $r['id'] ?>&to=ditolak">✖ Tolak</a>
        <?php elseif ($r['status'] === 'dikonfirmasi'): ?>
          <a class="btn btn-green btn-sm" href="?set=<?= $r['id'] ?>&to=hadir">🚶 Hadir</a>
          <a class="btn btn-ghost btn-sm" href="?set=<?= $r['id'] ?>&to=tidak_hadir">Tidak Hadir</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$resv): ?><tr><td colspan="7" style="color:var(--muted)">Belum ada permintaan reservasi.</td></tr><?php endif; ?>
  </table>
</div>
<div class="panel">
  <h2>💬 Pesan Kontak dari Website</h2>
  <table class="data">
    <tr><th>Nama</th><th>Kontak</th><th>Pesan</th><th>Waktu</th><th></th></tr>
    <?php foreach ($msgs as $m): ?>
    <tr><td><?= e($m['name']) ?></td><td><?= e($m['contact']) ?></td><td><?= e(mb_strimwidth($m['message'],0,60)) ?></td>
      <td><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
      <td><a class="btn btn-ghost btn-sm" href="?del_msg=<?= $m['id'] ?>">🗑️</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$msgs): ?><tr><td colspan="5" style="color:var(--muted)">Belum ada pesan.</td></tr><?php endif; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
