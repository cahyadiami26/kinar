<?php
$active = 'minuman'; $page_title = 'Menu Minuman';
require_once __DIR__ . '/../includes/public_header.php';
$items = db()->query("SELECT m.*, c.slug AS cat_slug FROM menu_items m JOIN categories c ON c.id=m.category_id
  WHERE m.is_public=1 AND c.slug IN ('minuman-dingin','minuman-hangat') ORDER BY m.sort_order")->fetchAll();
?>
<section class="order-hero" style="padding:30px 18px 24px;background:linear-gradient(160deg,#6A1B4D,#2D5A27)">
  <div class="brand-line">🍃 <?= e($site['name']) ?></div>
  <h1>Rangkaian Minuman Khas Nusantara</h1>
  <p>Segar dan hangat — dari seduhan rempah hingga perasan buah segar pilihan.</p>
</section>
<main class="menu-grid" style="padding-top:26px">
  <?php foreach ($items as $m): ?>
  <div class="menu-card" style="<?= $m['is_available'] ? '' : 'opacity:.65' ?>">
    <img src="<?= $m['image'] ? UPLOAD_URL . e($m['image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="<?= e($m['name']) ?>">
    <div class="menu-body">
      <h3><?= e($m['name']) ?></h3>
      <p><?= e($m['description']) ?></p>
      <div class="menu-tags"><span class="tag t-green"><?= e($m['cat_slug'] === 'minuman-dingin' ? 'Minuman Dingin' : 'Minuman Hangat') ?></span>
        <?php foreach (array_filter(explode(',', $m['tags'])) as $t): ?><span class="tag t-green"><?= e($t) ?></span><?php endforeach; ?></div>
      <div class="menu-foot">
        <span class="price"><?= rupiah($m['price']) ?></span>
        <span class="tag <?= $m['is_available'] ? 't-green' : 't-wine' ?>"><?= $m['is_available'] ? 'Tersedia' : 'Habis Sementara' ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><p style="color:var(--muted)">Belum ada minuman — atur melalui Dashboard CMS.</p><?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
