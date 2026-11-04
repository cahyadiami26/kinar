<?php
$active = 'warta'; $page_title = 'Warta Kuliner';
require_once __DIR__ . '/../includes/public_header.php';

// simpan reservasi dari form home
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['resv_name'] ?? '') !== '') {
    db()->prepare('INSERT INTO reservations (name, contact, date, time, guests, request) VALUES (?,?,?,?,?,?)')
        ->execute([$_POST['resv_name'], $_POST['resv_contact'], $_POST['resv_date'], $_POST['resv_time'],
                   (int)$_POST['resv_guests'], $_POST['resv_request'] ?? '']);
    queue_sync('reservasi', (int)db()->lastInsertId());
    echo '<div class="order-success" style="margin-top:24px">✅ Permintaan reservasi terkirim. Tim kami akan menghubungi Anda via WhatsApp.</div>';
}

if (($slug = $_GET['slug'] ?? '')) {
    $st = db()->prepare("SELECT * FROM articles WHERE slug=? AND status='terbit'");
    $st->execute([$slug]); $a = $st->fetch();
    if ($a):
?>
<section class="section" style="max-width:760px">
  <span class="tag t-green"><?= e($a['category']) ?></span>
  <h2 style="margin:10px 0"><?= e($a['title']) ?></h2>
  <p class="sub">✍️ <?= e($a['author']) ?> · <?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '' ?></p>
  <?php if ($a['cover_image']): ?><img src="<?= UPLOAD_URL . e($a['cover_image']) ?>" style="width:100%;border-radius:20px;margin-bottom:18px;box-shadow:var(--shadow-soft)"><?php endif; ?>
  <div style="line-height:1.8;font-size:.95rem;white-space:pre-line"><?= e($a['content']) ?></div>
  <p style="margin-top:24px"><a href="<?= BASE_URL ?>/public/warta.php">← Kembali ke semua warta</a></p>
</section>
<?php
    else: ?><section class="section"><p>Artikel tidak ditemukan.</p></section><?php
    endif;
    require_once __DIR__ . '/../includes/public_footer.php';
    exit;
}
$articles = db()->query("SELECT * FROM articles WHERE status='terbit' ORDER BY published_at DESC")->fetchAll();
?>
<section class="order-hero" style="padding:30px 18px 24px">
  <div class="brand-line">🍃 <?= e($site['name']) ?></div>
  <h1>Warta Kuliner &amp; Cerita Rasa</h1>
  <p>Filosofi rempah, bahan lokal, dan tips kuliner dari dapur Kinar Resto.</p>
</section>
<section class="section">
  <div class="grid-3">
    <?php foreach ($articles as $a): ?>
    <a class="pub-card article-card" href="?slug=<?= e($a['slug']) ?>">
      <img src="<?= $a['cover_image'] ? UPLOAD_URL . e($a['cover_image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="">
      <div class="b"><span class="tag t-green"><?= e($a['category']) ?></span>
        <h3><?= e($a['title']) ?></h3>
        <p><?= e(mb_strimwidth($a['content'], 0, 110)) ?></p>
        <div class="meta">✍️ <?= e($a['author']) ?> · <?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '' ?></div>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (!$articles): ?><p style="color:var(--muted)">Belum ada artikel terbit.</p><?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
