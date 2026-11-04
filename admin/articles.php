<?php
$nav = 'articles'; $page_title = 'Warta Kuliner';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin');

$cat_opts = ['Filosofi Rempah','Cerita Bahan Lokal','Tips Kuliner'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_article') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $cat = $_POST['category'];
    $author = trim($_POST['author']);
    $content = trim($_POST['content']);
    $status = $_POST['status'] === 'terbit' ? 'terbit' : 'draft';
    $cover = null;
    if (!empty($_FILES['cover']['name'])) $cover = upload_image($_FILES['cover'], 'articles');
    if ($id > 0) {
        $sql = 'UPDATE articles SET title=?, slug=?, category=?, author=?, content=?, status=?';
        $p = [$title, slugify($title), $cat, $author, $content, $status];
        if ($cover) { $sql .= ', cover_image=?'; $p[] = $cover; }
        if ($status === 'terbit') $sql .= ', published_at=NOW()';
        $sql .= ' WHERE id=?'; $p[] = $id;
        db()->prepare($sql)->execute($p);
        queue_sync('artikel', $id);
        flash('Artikel diperbarui.');
    } else {
        db()->prepare('INSERT INTO articles (title, slug, category, author, cover_image, content, status, published_at)
            VALUES (?,?,?,?,?,?,?, ' . ($status === 'terbit' ? 'NOW()' : 'NULL') . ')')
            ->execute([$title, slugify($title), $cat, $author, $cover, $content, $status]);
        queue_sync('artikel', (int)db()->lastInsertId());
        flash($status === 'terbit' ? 'Artikel diterbitkan ke website.' : 'Draf disimpan.');
    }
    log_activity($u['id'], 'article.save', $title);
    redirect(BASE_URL . '/admin/articles.php');
}
if (($del = (int)($_GET['delete'] ?? 0)) > 0) {
    db()->prepare('DELETE FROM articles WHERE id=?')->execute([$del]);
    queue_sync('artikel', $del, 'delete');
    flash('Artikel dihapus.', 'error');
    redirect(BASE_URL . '/admin/articles.php');
}
$edit = null;
if (($eid = (int)($_GET['edit'] ?? 0)) > 0) {
    $s = db()->prepare('SELECT * FROM articles WHERE id=?'); $s->execute([$eid]); $edit = $s->fetch();
}
$arts = db()->query("SELECT * FROM articles ORDER BY id DESC")->fetchAll();
?>
<div class="topbar">
  <div><div class="crumb">CMS Website</div><h1>Warta Kuliner &amp; Info Kuliner</h1></div>
  <div class="spacer"></div>
  <a class="btn btn-wine" href="?new=1#form">＋ Tulis Artikel Baru</a>
</div>

<?php if ($edit || isset($_GET['new'])): ?>
<div class="panel" id="form">
  <h2><?= $edit ? '✏️ Edit Artikel' : '📰 Editor Warta Boga' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_article">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
    <div class="form-grid">
      <div class="full"><label class="lbl">Judul Artikel</label>
        <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>"></div>
      <div><label class="lbl">Kategori</label>
        <select name="category"><?php foreach ($cat_opts as $c): ?>
          <option <?= ($edit['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div><label class="lbl">Kurator / Penulis</label>
        <input type="text" name="author" value="<?= e($edit['author'] ?? 'Tim Kinar') ?>"></div>
      <div class="full"><label class="lbl">Foto Cover / Header</label>
        <div class="dropzone">🖼️ Upload foto cover artikel (maks 2MB)
          <?php if ($edit && $edit['cover_image']): ?><br><img class="preview-img" src="<?= UPLOAD_URL . e($edit['cover_image']) ?>"><?php endif; ?>
          <input type="file" name="cover" accept="image/*" style="display:block;margin:10px auto 0"></div></div>
      <div class="full"><label class="lbl">Isi Artikel (format editorial)</label>
        <textarea name="content" required><?= e($edit['content'] ?? '') ?></textarea></div>
      <div><label class="lbl">Aksi</label>
        <button class="btn btn-ghost" name="status" value="draft" type="submit">💾 Simpan Draf</button>
        <button class="btn btn-green" name="status" value="terbit" type="submit">🌐 Terbitkan ke Web</button></div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <h2>Daftar Artikel <span class="tag"><?= count($arts) ?></span></h2>
  <table class="data">
    <tr><th>Cover</th><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Tindakan</th></tr>
    <?php foreach ($arts as $a): ?>
    <tr>
      <td><img class="thumb" src="<?= $a['cover_image'] ? UPLOAD_URL . e($a['cover_image']) : BASE_URL . '/assets/img/noimg.svg' ?>"></td>
      <td><strong><?= e($a['title']) ?></strong></td>
      <td><span class="tag t-blue"><?= e($a['category']) ?></span></td>
      <td><?= e($a['author']) ?></td>
      <td><span class="pill <?= $a['status']=='terbit' ? 'p-ok' : 'p-warn' ?>"><?= e($a['status']) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="?edit=<?= $a['id'] ?>#form">✏️</a>
          <a class="btn btn-ghost btn-sm" href="?delete=<?= $a['id'] ?>" onclick="return confirm('Hapus artikel?')">🗑️</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
