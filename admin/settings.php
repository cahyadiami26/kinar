<?php
$nav = 'settings'; $page_title = 'Pengaturan';
require_once __DIR__ . '/../includes/admin_header.php';
require_login('admin');

// tambah user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    db()->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)')
        ->execute([$_POST['name'], $_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role']]);
    log_activity($u['id'], 'user.create', $_POST['username']);
    flash('Pengguna baru dibuat.');
    redirect(BASE_URL . '/admin/settings.php');
}
if (($id = (int)($_GET['toggle_user'] ?? 0)) > 0 && $id !== $u['id']) {
    db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id=?')->execute([$id]);
    redirect(BASE_URL . '/admin/settings.php');
}
$users = db()->query('SELECT * FROM users ORDER BY id')->fetchAll();
$logs = db()->query('SELECT l.*, u.name FROM activity_log l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 30')->fetchAll();
?>
<div class="topbar"><div><div class="crumb">Keamanan &amp; Audit</div><h1>Pengaturan Pengguna &amp; Sistem</h1></div></div>
<div class="grid-2">
  <div class="panel">
    <h2>👥 Manajemen Pengguna (Hak Akses)</h2>
    <table class="data"><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr>
      <?php foreach ($users as $usr): ?>
      <tr><td><?= e($usr['name']) ?></td><td><?= e($usr['username']) ?></td><td><span class="tag t-wine"><?= e($usr['role']) ?></span></td>
        <td><span class="pill <?= $usr['is_active'] ? 'p-ok' : 'p-off' ?>"><?= $usr['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
        <td><?php if ($usr['id'] !== $u['id']): ?><a class="btn btn-ghost btn-sm" href="?toggle_user=<?= $usr['id'] ?>">🔄</a><?php endif; ?></td></tr>
      <?php endforeach; ?>
    </table>
    <h2 style="margin-top:18px">➕ Tambah Pengguna</h2>
    <form method="post" class="form-grid">
      <input type="hidden" name="action" value="add_user">
      <div><label class="lbl">Nama</label><input type="text" name="name" required></div>
      <div><label class="lbl">Username</label><input type="text" name="username" required></div>
      <div><label class="lbl">Password</label><input type="password" name="password" required></div>
      <div><label class="lbl">Role</label>
        <select name="role"><option value="kasir">Kasir</option><option value="pelayan">Pelayan</option>
        <option value="dapur">Dapur</option><option value="admin">Admin</option></select></div>
      <div class="full"><button class="btn btn-green btn-sm" type="submit">Buat Pengguna</button></div>
    </form>
  </div>
  <div class="panel">
    <h2>📜 Riwayat Audit (30 terakhir)</h2>
    <table class="data"><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Detail</th></tr>
      <?php foreach ($logs as $l): ?>
      <tr><td><?= date('d/m H:i', strtotime($l['created_at'])) ?></td><td><?= e($l['name'] ?? '-') ?></td>
        <td><?= e($l['action']) ?></td><td><?= e(mb_strimwidth($l['detail'],0,45)) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
