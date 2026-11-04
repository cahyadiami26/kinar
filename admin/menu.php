<?php
$nav = 'menu'; $page_title = 'Kelola Menu';
require_once __DIR__ . '/../includes/admin_header.php';

$tags_all = ['Signature Chef','Pedas Gurih','Halal MUI','Bebas Santan','Best Seller','Rekomendasi Tamu'];
$cats = db()->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

// ---- Simpan / update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_menu') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $cat  = (int)$_POST['category_id'];
    $price = (int)$_POST['price'];
    $portion = trim($_POST['portion'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $tags = implode(',', array_intersect($tags_all, $_POST['tags'] ?? []));
    $avail = isset($_POST['is_available']) ? 1 : 0;
    $pub   = isset($_POST['is_public']) ? 1 : 0;
    $feat  = isset($_POST['is_featured']) ? 1 : 0;
    $img = null;
    if (!empty($_FILES['image']['name'])) {
        $img = upload_image($_FILES['image'], 'menu');
    }
    if ($id > 0) {
        $sql = 'UPDATE menu_items SET category_id=?,name=?,slug=?,description=?,price=?,portion=?,tags=?,is_available=?,is_public=?,is_featured=?';
        $p = [$cat,$name,slugify($name),$desc,$price,$portion,$tags,$avail,$pub,$feat];
        if ($img) { $sql .= ',image=?'; $p[] = $img; }
        $sql .= ' WHERE id=?'; $p[] = $id;
        db()->prepare($sql)->execute($p);
        queue_sync('menu', $id);
        flash('Menu diperbarui.');
    } else {
        db()->prepare('INSERT INTO menu_items (category_id,name,slug,description,price,portion,image,tags,is_available,is_public,is_featured,sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,(SELECT COALESCE(MAX(sort_order),0)+1 FROM menu_items m))')
            ->execute([$cat,$name,slugify($name),$desc,$price,$portion,$img,$tags,$avail,$pub,$feat]);
        queue_sync('menu', (int)db()->lastInsertId());
        flash('Menu baru ditambahkan ke katalog.');
    }
    log_activity($u['id'], 'menu.save', $name);
    redirect(BASE_URL . '/admin/menu.php');
}
// ---- Hapus ----
if (($_GET['delete'] ?? 0) > 0) {
    $id = (int)$_GET['delete'];
    db()->prepare('DELETE FROM menu_items WHERE id=?')->execute([$id]);
    queue_sync('menu', $id, 'delete');
    flash('Menu dihapus.', 'error');
    redirect(BASE_URL . '/admin/menu.php');
}
// ---- Data edit ----
$edit = null;
if (($edit_id = (int)($_GET['edit'] ?? 0)) > 0) {
    $st = db()->prepare('SELECT * FROM menu_items WHERE id=?'); $st->execute([$edit_id]); $edit = $st->fetch();
}
$items = db()->query('SELECT m.*, c.name AS category FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY m.sort_order, m.id DESC')->fetchAll();
?>
<div class="topbar">
  <div><div class="crumb">CMS Website · Modul Restoran</div><h1>Katalog Sajian Nusantara</h1></div>
  <div class="spacer"></div>
  <a class="btn btn-green" href="?new=1#form">＋ Tambah Sajian Baru</a>
</div>

<?php if ($edit || isset($_GET['new'])): ?>
<div class="panel" id="form">
  <h2><?= $edit ? '✏️ Edit Sajian' : '🍽️ Formulir Sajian Menu Eksklusif' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_menu">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
    <div class="form-grid">
      <div class="full">
        <label class="lbl">Dokumentasi Visual Hidangan (foto, maks 2MB, rasio 4:3)</label>
        <div class="dropzone">📷 Tarik & letakkan berkas foto di sini, atau klik untuk memilih
          <?php if ($edit && $edit['image']): ?><br><img class="preview-img" src="<?= UPLOAD_URL . e($edit['image']) ?>"><?php endif; ?>
          <input type="file" name="image" accept="image/*" style="display:block;margin:10px auto 0">
        </div>
      </div>
      <div><label class="lbl">Nama Hidangan / Minuman</label>
        <input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div><label class="lbl">Pilih Kategori</label>
        <select name="category_id">
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($edit['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label class="lbl">Harga Jual (IDR)</label>
        <input type="number" name="price" min="0" required value="<?= e($edit['price'] ?? '') ?>"></div>
      <div><label class="lbl">Porsi / Ukuran Sajian</label>
        <input type="text" name="portion" placeholder="cth: 2 Orang (260 gr)" value="<?= e($edit['portion'] ?? '') ?>"></div>
      <div class="full"><label class="lbl">Tag Menu &amp; Atribut Gastronomi</label><br>
        <?php foreach ($tags_all as $t): $on = $edit && in_array($t, explode(',', $edit['tags'])); ?>
          <label class="tag <?= $on ? 't-wine' : 't-gray' ?>" style="cursor:pointer">
            <input type="checkbox" name="tags[]" value="<?= e($t) ?>" <?= $on ? 'checked' : '' ?> style="display:none"> <?= e($t) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div><label class="lbl">Status Ketersediaan (operasional dapur)</label><br>
        <label class="switch"><input type="checkbox" name="is_available" <?= ($edit['is_available'] ?? 1) ? 'checked' : '' ?>><span class="slider"></span></label>
        <small style="color:var(--muted)"> <?= ($edit['is_available'] ?? 1) ? 'Tersedia di Dapur' : 'Habis Sementara' ?></small></div>
      <div><label class="lbl">Status Publikasi Website</label><br>
        <label class="switch"><input type="checkbox" name="is_public" <?= ($edit['is_public'] ?? 1) ? 'checked' : '' ?>><span class="slider"></span></label>
        <small style="color:var(--muted)"> tampil di katalog web walau sedang habis</small></div>
      <div class="full"><label class="lbl">Deskripsi Rempah &amp; Bahan Utama</label>
        <textarea name="description"><?= e($edit['description'] ?? '') ?></textarea></div>
    </div>
    <div style="margin-top:16px">
      <button class="btn btn-green" type="submit">💾 Simpan ke Menu</button>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/admin/menu.php">Batal</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Tabel Katalog Sajian <span class="tag"><?= count($items) ?> item</span></h2>
  <table class="data">
    <tr><th>Hidangan & Visual</th><th>Kategori</th><th>Harga</th><th>Atribut / Tag</th><th>Ketersediaan</th><th>Web</th><th>Tindakan</th></tr>
    <?php foreach ($items as $m): ?>
    <tr>
      <td><div style="display:flex;gap:10px;align-items:center">
        <img class="thumb" src="<?= $m['image'] ? UPLOAD_URL . e($m['image']) : BASE_URL . '/assets/img/noimg.svg' ?>" alt="">
        <div><strong><?= e($m['name']) ?></strong><br><small style="color:var(--muted)"><?= e($m['portion']) ?></small></div></div></td>
      <td><?= e($m['category']) ?></td>
      <td><strong><?= rupiah($m['price']) ?></strong></td>
      <td><?php foreach (array_filter(explode(',', $m['tags'])) as $t): ?><span class="tag t-green"><?= e($t) ?></span><?php endforeach; ?></td>
      <td><span class="pill <?= $m['is_available'] ? 'p-ok' : 'p-off' ?>"><span class="dot"></span><?= $m['is_available'] ? 'Tersedia' : 'Habis' ?></span></td>
      <td><span class="pill <?= $m['is_public'] ? 'p-info' : 'p-warn' ?>"><?= $m['is_public'] ? 'Tampil' : 'Sembunyi' ?></span></td>
      <td>
        <a class="btn btn-ghost btn-sm" href="?edit=<?= $m['id'] ?>#form">✏️</a>
        <a class="btn btn-ghost btn-sm" href="?delete=<?= $m['id'] ?>" onclick="return confirm('Hapus menu ini?')">🗑️</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
