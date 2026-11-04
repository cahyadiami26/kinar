<?php
$nav = 'pos'; $page_title = 'Kasir & POS';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/printer.php';
require_login('admin','kasir','pelayan');

// konfirmasi pesanan -> dapur + antrian cetak
if (($cid = (int)($_GET['confirm'] ?? 0)) > 0) {
    db()->prepare("UPDATE orders SET status='dikonfirmasi', confirmed_at=NOW() WHERE id=?")->execute([$cid]);
    queue_order_print($cid);
    log_activity($u['id'], 'order.confirm', "Pesanan #$cid dikonfirmasi & dicetak ke dapur");
    flash('Pesanan dikonfirmasi dan tiket dicetak ke dapur.');
    redirect(BASE_URL . '/admin/pos.php');
}
// proses pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $oid = (int)$_POST['order_id'];
    $paid = (int)$_POST['amount_paid'];
    $o = db()->prepare('SELECT * FROM orders WHERE id=?'); $o->execute([$oid]); $order = $o->fetch();
    $code = 'PAY-' . date('YmdHis') . '-' . $oid;
    db()->prepare('INSERT INTO payments (payment_code, order_id, cashier_id, method, amount_paid, change_amount) VALUES (?,?,?,?,?,?)')
        ->execute([$code, $oid, $u['id'], $_POST['method'], $paid, max(0, $paid - $order['total'])]);
    db()->prepare("UPDATE orders SET status='selesai', completed_at=NOW() WHERE id=?")->execute([$oid]);
    if ($order['table_id']) {
        db()->prepare("UPDATE resto_tables SET status='kosong' WHERE id=?")->execute([$order['table_id']]);
        db()->prepare("UPDATE table_sessions SET status='ditutup', closed_at=NOW() WHERE id=? AND status='aktif'")
            ->execute([$order['table_session_id']]);
    }
    print_receipt($oid);
    log_activity($u['id'], 'payment', "Pembayaran $code sebesar " . rupiah($order['total']));
    flash("Pembayaran berhasil. Struk $code dicetak.");
    redirect(BASE_URL . '/admin/pos.php');
}
$orders = db()->query("SELECT o.*, t.label AS table_label FROM orders o
    LEFT JOIN resto_tables t ON t.id=o.table_id
    WHERE o.status IN ('menunggu','dikonfirmasi','diproses','siap','disajikan')
    ORDER BY o.id DESC")->fetchAll();
?>
<div class="topbar">
  <div><div class="crumb">Kasir &amp; POS</div><h1>Konfirmasi Pesanan &amp; Pembayaran</h1></div>
  <div class="spacer"></div>
</div>
<div class="panel">
  <h2>Pesanan Aktif <span class="tag"><?= count($orders) ?></span></h2>
  <?php foreach ($orders as $o): ?>
  <?php $items = db()->prepare('SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON m.id=oi.menu_id WHERE oi.order_id=?');
        $items->execute([$o['id']]); ?>
  <div class="card" style="margin-bottom:14px">
    <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start">
      <div style="min-width:220px">
        <strong>#<?= e($o['order_code']) ?></strong>
        <span class="pill <?= $o['status']=='menunggu'?'p-warn':'p-info' ?>"><span class="dot"></span><?= e($o['status']) ?></span><br>
        <small style="color:var(--muted)">
          <?= e($o['table_label'] ?: 'Takeaway') ?> · <?= e($o['order_type']) ?> · <?= e($o['customer_name']) ?><br>
          <?= date('d/m H:i', strtotime($o['created_at'])) ?>
        </small>
      </div>
      <div style="flex:1;min-width:260px">
        <table class="data">
          <?php foreach ($items->fetchAll() as $it): ?>
          <tr><td><?= $it['qty'] ?>x <?= e($it['name']) ?></td><td><?= rupiah($it['price'] * $it['qty']) ?></td></tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div style="text-align:right;min-width:230px">
        <div style="font-size:1.2rem;font-weight:800"><?= rupiah($o['total']) ?></div>
        <?php if ($o['status'] === 'menunggu'): ?>
          <a class="btn btn-wine btn-sm" href="?confirm=<?= $o['id'] ?>">✔ Konfirmasi &amp; Cetak</a>
        <?php elseif (in_array($o['status'], ['siap','disajikan','dikonfirmasi','diproses'])): ?>
          <form method="post" style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
            <input type="hidden" name="action" value="pay">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <select name="method" style="width:110px;padding:6px"><option value="tunai">Tunai</option><option value="qris">QRIS</option><option value="transfer">Transfer</option><option value="kartu">Kartu</option></select>
            <input type="number" name="amount_paid" value="<?= $o['total'] ?>" style="width:120px;padding:6px">
            <button class="btn btn-green btn-sm" type="submit">💰 Bayar &amp; Cetak Struk</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$orders): ?><p style="color:var(--muted)">Tidak ada pesanan aktif.</p><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
