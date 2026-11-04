<?php
$active = 'home'; $page_title = 'Beranda';
require_once __DIR__ . '/../includes/public_header.php';

$featured = db()->query("SELECT m.*, c.slug AS cat_slug FROM menu_items m JOIN categories c ON c.id=m.category_id
  WHERE m.is_public=1 AND m.is_available=1 AND m.is_featured=1 ORDER BY m.sort_order LIMIT 6")->fetchAll();
$all = db()->query("SELECT m.*, c.slug AS cat_slug FROM menu_items m JOIN categories c ON c.id=m.category_id
  WHERE m.is_public=1 ORDER BY m.sort_order LIMIT 6")->fetchAll();
$featured = $featured ?: $all;
$articles = db()->query("SELECT * FROM articles WHERE status='terbit' ORDER BY published_at DESC LIMIT 3")->fetchAll();
$hero_bg = $site['hero_image'] ? UPLOAD_URL . e($site['hero_image']) : 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1600';
?>
<section class="hero" style="background-image:url('<?= $hero_bg ?>')">
  <div class="inner">
    <span class="kicker">Pengalaman Tradisi &amp; Warisan Rasa</span>
    <h1><?= e($site['hero_title']) ?></h1>
    <p><?= e($site['hero_sub']) ?></p>
    <a class="btn btn-green" href="<?= BASE_URL ?>/public/menu-makanan.php">🍽️ Lihat Menu</a>
    <a class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4)" href="#reservasi">📅 Reservasi</a>
  </div>
</section>

<section class="section">
  <h2>Menu Best Seller Nusantara</h2>
  <p class="sub">Hidangan unggulan yang paling dicari tamu Kinar Resto.</p>
  <div class="grid-3">
    <?php foreach ($featured as $m): ?>
    <div class="pub-card">
      <img src="<?= $m['image'] ? UPLOAD_URL . e($m['image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="<?= e($m['name']) ?>">
      <div class="b"><h3><?= e($m['name']) ?></h3>
        <div><?php foreach (array_filter(explode(',', $m['tags'])) as $t): ?><span class="tag t-wine"><?= e($t) ?></span><?php endforeach; ?></div>
        <p><?= e(mb_strimwidth($m['description'], 0, 100)) ?></p>
        <div class="pr"><?= rupiah($m['price']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section" id="reservasi" style="background:#fff;border-radius:26px;box-shadow:var(--shadow-soft)">
  <h2>Reservasi Meja Kinar Resto</h2>
  <p class="sub">Isi formulir, tim kami akan mengonfirmasi melalui WhatsApp.</p>
  <form method="post" action="<?= BASE_URL ?>/public/warta.php#reservasi" style="max-width:560px" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div><label class="lbl">Nama Lengkap</label><input type="text" name="resv_name" required></div>
    <div><label class="lbl">Kontak (WA)</label><input type="text" name="resv_contact" required></div>
    <div><label class="lbl">Tanggal</label><input type="date" name="resv_date" required></div>
    <div><label class="lbl">Jam</label><input type="time" name="resv_time" required></div>
    <div><label class="lbl">Jumlah Tamu</label><input type="number" name="resv_guests" value="2" min="1" max="20"></div>
    <div><label class="lbl">Permintaan Khusus</label><input type="text" name="resv_request" placeholder="cth: dekat jendela"></div>
    <div style="grid-column:1/-1"><button class="btn btn-green" type="submit">Kirim Permintaan Reservasi</button></div>
  </form>
</section>

<section class="section">
  <h2>Kunjungi Nusantara Rasa di Kinar</h2>
  <p class="sub"><?= e($site['address']) ?></p>
  <?php if ($site['maps']): ?>
    <iframe src="<?= e($site['maps_embed']) ?>" style="width:100%;height:340px;border:0;border-radius:22px;box-shadow:var(--shadow-soft)" loading="lazy"></iframe>
  <?php else: ?>
    <div style="background:#fff;border-radius:22px;padding:30px;box-shadow:var(--shadow-soft);font-size:.9rem;color:var(--muted)">
      🗺️ Sematkan iframe Google Maps melalui CMS (Pengaturan → Lokasi &amp; Google Maps).
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <h2>Kisah Rasa &amp; Info Kuliner</h2>
  <p class="sub">Warta terbaru dari dapur Kinar Resto.</p>
  <div class="grid-3">
    <?php foreach ($articles as $a): ?>
    <a class="pub-card article-card" href="<?= BASE_URL ?>/public/warta.php?slug=<?= e($a['slug']) ?>">
      <img src="<?= $a['cover_image'] ? UPLOAD_URL . e($a['cover_image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="">
      <div class="b"><span class="tag t-green"><?= e($a['category']) ?></span>
        <h3><?= e($a['title']) ?></h3>
        <div class="meta">✍️ <?= e($a['author']) ?> · <?= $a['published_at'] ? date('d M Y', strtotime($a['published_at'])) : '' ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
