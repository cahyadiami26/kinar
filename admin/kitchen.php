<?php
$nav = 'kitchen'; $page_title = 'Dapur & Bar';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin','dapur','kasir');

// ubah status item / pesanan
if (($iid = (int)($_GET['item_done'] ?? 0)) > 0) {
    db()->prepare("UPDATE order_items SET item_status='siap' WHERE id=?")->execute([$iid]);
    redirect(BASE_URL . '/admin/kitchen.php');
}
if (($iid = (int)($_GET['item_proc'] ?? 0)) > 0) {
    db()->prepare("UPDATE order_items SET item_status='diproses' WHERE id=?")->execute([$iid]);
    redirect(BASE_URL . '/admin/kitchen.php');
}
if (($oid = (int)($_GET['order_ready'] ?? 0)) > 0) {
    db()->prepare("UPDATE orders SET status='siap' WHERE id=?")->execute([$oid]);
    log_activity($u['id'], 'order.ready', "Pesanan #$oid siap disajikan");
    redirect(BASE_URL . '/admin/kitchen.php');
}
$orders = db()->query("SELECT o.*, t.label AS table_label FROM orders o
    LEFT JOIN resto_tables t ON t.id=o.table_id
    WHERE o.status IN ('dikonfirmasi','diproses') ORDER BY o.id")->fetchAll();
?>
<div class="topbar">
  <div><div class="crumb">Dapur &amp; Bar</div><h1>Antrean Pesanan</h1></div>
  <div class="spacer"></div>
  <span class="pill p-info">⟳ auto-refresh 30 detik</span>
</div>
<?php if (!$orders): ?><div class="panel"><p style="color:var(--muted)">Antrean kosong. Semua pesanan sudah siap. 🍃</p></div><?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
  <?php foreach ($orders as $o): ?>
  <?php $items = db()->prepare('SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON m.id=oi.menu_id WHERE oi.order_id=?');
        $items->execute([$o['id']]); ?>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <strong>#<?= e($o['order_code']) ?></strong>
      <span class="pill p-warn"><span class="dot"></span><?= e($o['status']) ?></span>
    </div>
    <small style="color:var(--muted)">📍 <?= e($o['table_label'] ?: 'Takeaway') ?> · <?= e($o['customer_name']) ?> · <?= date('H:i', strtotime($o['created_at'])) ?></small>
    <table class="data" style="margin-top:8px">
      <?php foreach ($items->fetchAll() as $it): ?>
      <tr>
        <td><?= $it['qty'] ?>x <?= e($it['name']) ?></td>
        <td>
          <?php if ($it['item_status'] === 'antrian'): ?>
            <a class="btn btn-ghost btn-sm" href="?item_proc=<?= $it['id'] ?>">🔥 Proses</a>
          <?php elseif ($it['item_status'] === 'diproses'): ?>
            <a class="btn btn-green btn-sm" href="?item_done=<?= $it['id'] ?>">✔ Siap</a>
          <?php else: ?><span class="pill p-ok">✔ Siap</span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php if ($o['note']): ?><p style="font-size:.78rem;color:var(--muted);margin-top:6px">📝 <?= e($o['note']) ?></p><?php endif; ?>
    <a class="btn btn-wine btn-sm" style="margin-top:10px" href="?order_ready=<?= $o['id'] ?>">🍽️ Semua Siap — Sajikan</a>
  </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
