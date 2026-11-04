<?php
$active = 'makanan'; $page_title = 'Menu Makanan';
require_once __DIR__ . '/../includes/public_header.php';
$items = db()->query("SELECT m.*, c.slug AS cat_slug FROM menu_items m JOIN categories c ON c.id=m.category_id
  WHERE m.is_public=1 AND c.slug IN ('makanan-utama','makanan-ringan') ORDER BY m.is_featured DESC, m.sort_order")->fetchAll();
?>
<section class="order-hero" style="padding:30px 18px 24px">
  <div class="brand-line">🍃 <?= e($site['name']) ?></div>
  <h1>Koleksi Sajian Makanan Nusantara</h1>
  <p>Nikmati racikan resep pusaka dari pelosok negeri — dibuat dengan daging premium dan rempah segar.</p>
</section>
<main class="menu-grid" style="padding-top:26px">
  <?php foreach ($items as $m): ?>
  <div class="menu-card" style="<?= $m['is_available'] ? '' : 'opacity:.65' ?>">
    <img src="<?= $m['image'] ? UPLOAD_URL . e($m['image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="<?= e($m['name']) ?>">
    <div class="menu-body">
      <h3><?= e($m['name']) ?> <?= $m['is_featured'] ? '<span class="tag t-wine">★ Signature</span>' : '' ?></h3>
      <p><?= e($m['description']) ?></p>
      <div class="menu-tags"><?php foreach (array_filter(explode(',', $m['tags'])) as $t): ?><span class="tag t-green"><?= e($t) ?></span><?php endforeach; ?></div>
      <div class="menu-foot">
        <span class="price"><?= rupiah($m['price']) ?></span>
        <span class="tag <?= $m['is_available'] ? 't-green' : 't-wine' ?>"><?= $m['is_available'] ? 'Tersedia' : 'Habis Sementara' ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><p style="color:var(--muted)">Belum ada menu — atur melalui Dashboard CMS.</p><?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
