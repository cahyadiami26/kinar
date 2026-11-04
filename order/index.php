<?php
/**
 * Kinar Resto - Pemesanan via QR Code Meja / Area Tunggu
 * URL: /order/?token=<qr_token>
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';
$st = db()->prepare('SELECT * FROM resto_tables WHERE qr_token = ?');
$st->execute([$token]);
$table = $st->fetch();
if (!$table) {
    http_response_code(404);
    echo '<h2 style="font-family:sans-serif;text-align:center;margin-top:60px">QR Code tidak valid 🍃</h2>';
    exit;
}

// ---- buka / ambil sesi meja aktif ----
if ($table['table_code'] !== 'TAKEAWAY') {
    $s = db()->prepare('SELECT * FROM table_sessions WHERE table_id=? AND status="aktif" ORDER BY id DESC LIMIT 1');
    $s->execute([$table['id']]);
    $session = $s->fetch();
    if (!$session) {
        $code = 'SES-' . date('YmdHis') . '-' . $table['id'];
        db()->prepare('INSERT INTO table_sessions (table_id, session_code) VALUES (?,?)')->execute([$table['id'], $code]);
        db()->prepare("UPDATE resto_tables SET status='terisi' WHERE id=?")->execute([$table['id']]);
        $session = ['id' => (int)db()->lastInsertId(), 'session_code' => $code];
    }
    $session_id = $session['id'];
} else {
    $session_id = null; // takeaway tidak memakai sesi meja
}

// ---- aksi keranjang ----
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (($_POST['action'] ?? '') === 'add') {
    $mid = (int)$_POST['menu_id'];
    $qty = max(1, (int)$_POST['qty']);
    $note = trim($_POST['note'] ?? '');
    $key = $mid . '|' . md5($note);
    if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty'] += $qty;
    else {
        $m = db()->prepare('SELECT id, name, price FROM menu_items WHERE id=? AND is_available=1');
        $m->execute([$mid]); $mi = $m->fetch();
        if ($mi) $_SESSION['cart'][$key] = ['menu_id'=>$mi['id'],'name'=>$mi['name'],'price'=>$mi['price'],'qty'=>$qty,'note'=>$note];
    }
    redirect(BASE_URL . '/order/?token=' . $token . '#menu');
}
if (($_GET['remove'] ?? '') !== '') { unset($_SESSION['cart'][$_GET['remove']]); redirect(BASE_URL . '/order/?token=' . $token . '#menu'); }
if (($_GET['clear'] ?? '') === '1') { $_SESSION['cart'] = []; redirect(BASE_URL . '/order/?token=' . $token . '#menu'); }

// ---- submit pesanan ----
$order_code = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    $name = trim($_POST['customer_name'] ?? '');
    if ($name !== '' && $_SESSION['cart']) {
        $total = 0; foreach ($_SESSION['cart'] as $c) $total += $c['price'] * $c['qty'];
        $code = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);
        $type = $table['table_code'] === 'TAKEAWAY' ? 'takeaway' : 'dine_in';
        db()->prepare('INSERT INTO orders (order_code, table_id, table_session_id, customer_name, order_type, status, note, total)
            VALUES (?,?,?,?,?,"menunggu",?,?)')
            ->execute([$code, $table['id'], $session_id, $name, $type, trim($_POST['order_note'] ?? ''), $total]);
        $oid = (int)db()->lastInsertId();
        $ins = db()->prepare('INSERT INTO order_items (order_id, menu_id, qty, price, note) VALUES (?,?,?,?,?)');
        foreach ($_SESSION['cart'] as $c) $ins->execute([$oid, $c['menu_id'], $c['qty'], $c['price'], $c['note']]);
        $_SESSION['cart'] = [];
        log_activity(null, 'order.create', "Pesanan $code oleh $name ({$table['label']})");
        $order_code = $code;
    }
}

$cats = db()->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$cat_filter = $_GET['cat'] ?? 'semua';
$menu = db()->query("SELECT m.*, c.slug AS cat_slug FROM menu_items m JOIN categories c ON c.id=m.category_id
    WHERE m.is_public=1 AND m.is_available=1 ORDER BY m.sort_order, m.id DESC")->fetchAll();
$cart_total = 0; foreach ($_SESSION['cart'] as $c) $cart_total += $c['price'] * $c['qty'];
$cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menu · <?= e(get_setting('site_name', 'Kinar Resto')) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public.css">
</head>
<body class="order-page">
<header class="order-hero">
  <div class="brand-line">🍃 <?= e(get_setting('site_name', 'Kinar Resto')) ?></div>
  <h1><?= e($table['label']) ?></h1>
  <p><?= $table['table_code'] === 'TAKEAWAY' ? 'Mode Takeaway — ambil pesanan di kasir' : 'Mode Dine-in — pesanan diantar ke meja Anda' ?></p>
</header>

<?php if ($order_code): ?>
<div class="order-success">
  <div style="font-size:3rem">✅</div>
  <h2>Pesanan diterima!</h2>
  <p>Kode pesanan Anda: <strong><?= e($order_code) ?></strong></p>
  <p style="font-size:.85rem">Tunjukkan kode ini ke kasir saat membayar. Simpan struk Anda.</p>
</div>
<?php endif; ?>

<nav class="cat-filter" id="menu">
  <a class="chip <?= $cat_filter==='semua'?'on':'' ?>" href="?token=<?= e($token) ?>#menu">Semua</a>
  <?php foreach ($cats as $c): ?>
  <a class="chip <?= $cat_filter===$c['slug']?'on':'' ?>" href="?token=<?= e($token) ?>&cat=<?= e($c['slug']) ?>#menu"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</nav>

<main class="menu-grid">
  <?php foreach ($menu as $m): ?>
  <?php if ($cat_filter !== 'semua' && $m['cat_slug'] !== $cat_filter) continue; ?>
  <div class="menu-card">
    <img src="<?= $m['image'] ? UPLOAD_URL . e($m['image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="<?= e($m['name']) ?>">
    <div class="menu-body">
      <h3><?= e($m['name']) ?></h3>
      <p><?= e(mb_strimwidth($m['description'], 0, 90)) ?></p>
      <div class="menu-tags"><?php foreach (array_filter(explode(',', $m['tags'])) as $t): ?><span class="tag t-green"><?= e($t) ?></span><?php endforeach; ?></div>
      <div class="menu-foot">
        <span class="price"><?= rupiah($m['price']) ?></span>
        <form method="post" style="display:flex;gap:6px;align-items:center">
          <input type="hidden" name="action" value="add"><input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
          <input type="number" name="qty" value="1" min="1" style="width:56px;padding:6px;border-radius:10px;border:1.5px solid #E2DCCB">
          <input type="text" name="note" placeholder="catatan…" style="width:110px;padding:6px;border-radius:10px;border:1.5px solid #E2DCCB">
          <button class="btn-add" type="submit">＋ Tambah</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</main>

<aside class="cart-panel">
  <h2>🛒 Keranjang <span class="tag t-wine"><?= $cart_count ?> item</span></h2>
  <?php if ($_SESSION['cart']): ?>
    <?php foreach ($_SESSION['cart'] as $key => $c): ?>
    <div class="cart-item">
      <div><strong><?= $c['qty'] ?>x <?= e($c['name']) ?></strong>
        <?php if ($c['note']): ?><br><small style="color:var(--muted)">📝 <?= e($c['note']) ?></small><?php endif; ?></div>
      <div style="text-align:right"><?= rupiah($c['price'] * $c['qty']) ?><br>
        <a href="?token=<?= e($token) ?>&remove=<?= urlencode($key) ?>#menu" style="font-size:.72rem;color:var(--wine)">hapus</a></div>
    </div>
    <?php endforeach; ?>
    <div class="cart-total">Total <strong><?= rupiah($cart_total) ?></strong></div>
    <form method="post" class="checkout-form">
      <input type="hidden" name="action" value="checkout">
      <label class="lbl">Nama Anda</label>
      <input type="text" name="customer_name" required placeholder="cth: Andi">
      <label class="lbl">Catatan pesanan (opsional)</label>
      <textarea name="order_note" placeholder="cth: tanpa pedas, alergi kacang…"></textarea>
      <button class="btn btn-green" type="submit" style="width:100%;justify-content:center">📨 Kirim Pesanan ke Kasir</button>
      <a href="?token=<?= e($token) ?>&clear=1#menu" style="font-size:.75rem;color:var(--muted)">Kosongkan keranjang</a>
    </form>
  <?php else: ?>
    <p style="color:var(--muted);font-size:.85rem">Keranjang masih kosong. Pilih menu di atas 👆</p>
  <?php endif; ?>
</aside>
<script src="<?= BASE_URL ?>/assets/js/public.js"></script>
</body>
</html>
