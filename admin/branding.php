<?php
$nav = 'branding'; $page_title = 'Branding & Web';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin');

$keys = ['site_name','tagline','hero_title','hero_sub','meta_description','meta_keywords',
         'address','phone','wa','hours','maps_embed','resto_open'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $k) {
        if ($k === 'resto_open') { set_setting($k, isset($_POST[$k]) ? '1' : '0'); continue; }
        if (isset($_POST[$k])) set_setting($k, trim($_POST[$k]));
    }
    if (!empty($_FILES['logo']['name']))   { $f = upload_image($_FILES['logo'], 'branding');   if ($f) set_setting('logo', $f); }
    if (!empty($_FILES['hero_image']['name'])) { $f = upload_image($_FILES['hero_image'], 'branding'); if ($f) set_setting('hero_image', $f); }
    queue_sync('pengaturan', 0);
    log_activity($u['id'], 'branding.update', 'Pengaturan branding & web diperbarui');
    flash('Pengaturan branding tersimpan. Perubahan masuk antrian sinkronisasi web publik.');
    redirect(BASE_URL . '/admin/branding.php');
}
if (($_GET['sync'] ?? '') === '1') {
    // tandai semua antrian sebagai dikirim (simulasi; ganti dengan panggilan API cPanel saat online)
    db()->exec("UPDATE sync_queue SET status='synced' WHERE status='pending'");
    flash('Sinkronisasi ke website publik selesai (mode lokal: antrian ditandai terkirim).');
    redirect(BASE_URL . '/admin/branding.php');
}
?>
<div class="topbar">
  <div><div class="crumb">CMS Website</div><h1>Identitas Resto, Branding &amp; Tampilan Web</h1></div>
  <div class="spacer"></div>
  <a class="btn btn-wine" href="?sync=1">🔄 Sinkronkan Web Publik</a>
</div>

<form method="post" enctype="multipart/form-data">
<div class="grid-2">
  <div class="panel">
    <h2>🎨 Brand &amp; SEO</h2>
    <div class="form-grid">
      <div class="full"><label class="lbl">Logo Resto</label>
        <div class="dropzone">🍃 Upload logo (asets daun botani Kinar)
          <?php if (get_setting('logo')): ?><br><img class="preview-img" src="<?= UPLOAD_URL . e(get_setting('logo')) ?>"><?php endif; ?>
          <input type="file" name="logo" accept="image/*" style="display:block;margin:10px auto 0"></div></div>
      <div><label class="lbl">Nama Restoran</label><input type="text" name="site_name" value="<?= e(get_setting('site_name')) ?>"></div>
      <div><label class="lbl">Tagline</label><input type="text" name="tagline" value="<?= e(get_setting('tagline')) ?>"></div>
      <div class="full"><label class="lbl">Meta Title</label><input type="text" name="meta_title" value="<?= e(get_setting('meta_title')) ?>"></div>
      <div class="full"><label class="lbl">Meta Description</label><input type="text" name="meta_description" value="<?= e(get_setting('meta_description')) ?>"></div>
      <div class="full"><label class="lbl">Meta Keywords</label><input type="text" name="meta_keywords" value="<?= e(get_setting('meta_keywords')) ?>"></div>
    </div>
  </div>
  <div class="panel">
    <h2>🖼️ Tampilan Utama Website (Hero)</h2>
    <div class="form-grid">
      <div class="full"><label class="lbl">Gambar Banner Utama</label>
        <div class="dropzone">🖼️ Upload banner hero
          <?php if (get_setting('hero_image')): ?><br><img class="preview-img" src="<?= UPLOAD_URL . e(get_setting('hero_image')) ?>"><?php endif; ?>
          <input type="file" name="hero_image" accept="image/*" style="display:block;margin:10px auto 0"></div></div>
      <div class="full"><label class="lbl">Judul Hero</label><input type="text" name="hero_title" value="<?= e(get_setting('hero_title')) ?>"></div>
      <div class="full"><label class="lbl">Subjudul / Deskripsi Singkat</label><input type="text" name="hero_sub" value="<?= e(get_setting('hero_sub')) ?>"></div>
      <div><label class="lbl">Status Operasional</label><br>
        <label class="switch"><input type="checkbox" name="resto_open" <?= get_setting('resto_open','1') ? 'checked' : '' ?>><span class="slider"></span></label>
        <small style="color:var(--muted)"> tampil "Buka" di dashboard & web</small></div>
    </div>
  </div>
  <div class="panel">
    <h2>📍 Lokasi, Kontak &amp; Google Maps</h2>
    <div class="form-grid">
      <div class="full"><label class="lbl">Alamat Lengkap</label><input type="text" name="address" value="<?= e(get_setting('address')) ?>"></div>
      <div><label class="lbl">Telepon</label><input type="text" name="phone" value="<?= e(get_setting('phone')) ?>"></div>
      <div><label class="lbl">WhatsApp Concierge</label><input type="text" name="wa" value="<?= e(get_setting('wa')) ?>"></div>
      <div class="full"><label class="lbl">Jam Operasional (footer)</label><input type="text" name="hours" value="<?= e(get_setting('hours')) ?>"></div>
      <div class="full"><label class="lbl">Embed Google Maps (URL iframe)</label><input type="text" name="maps_embed" value="<?= e(get_setting('maps_embed')) ?>"></div>
    </div>
  </div>
  <div class="panel">
    <h2>📡 Status Sinkronisasi</h2>
    <?php $q = db()->query("SELECT entity, status, COUNT(*) n FROM sync_queue GROUP BY entity, status")->fetchAll(); ?>
    <table class="data">
      <tr><th>Entitas</th><th>Status</th><th>Jumlah</th></tr>
      <?php foreach ($q as $r): ?><tr><td><?= e($r['entity']) ?></td>
        <td><span class="pill <?= $r['status']=='synced'?'p-ok':($r['status']=='failed'?'p-off':'p-warn') ?>"><?= e($r['status']) ?></span></td>
        <td><?= $r['n'] ?></td></tr><?php endforeach; ?>
      <?php if (!$q): ?><tr><td colspan="3" style="color:var(--muted)">Belum ada antrian sinkronisasi.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
<div style="margin-bottom:26px"><button class="btn btn-green" type="submit">💾 Simpan Pengaturan</button></div>
</form>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
