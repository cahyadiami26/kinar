<?php
/**
 * Kinar Resto - Fungsi Bantuan
 */

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    return trim($s, '-') ?: bin2hex(random_bytes(4));
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function log_activity(?int $user_id, string $action, string $detail = ''): void {
    $st = db()->prepare('INSERT INTO activity_log (user_id, action, detail) VALUES (?,?,?)');
    $st->execute([$user_id, $action, $detail]);
}

/** Antrian sinkronisasi ke website publik (cPanel) */
function queue_sync(string $entity, int $entity_id, string $action = 'upsert'): void {
    $st = db()->prepare('INSERT INTO sync_queue (entity, entity_id, action) VALUES (?,?,?)');
    $st->execute([$entity, $entity_id, $action]);
}

/** Ambil pengaturan CMS dari tabel settings */
function get_setting(string $key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $rows = db()->query('SELECT `key`, value FROM settings')->fetchAll();
        $cache = array_column($rows, 'value', 'key');
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings (`key`, value) VALUES (?,?)
                         ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $st->execute([$key, $value]);
}

/** Upload gambar dengan batas ukuran & rasio opsional */
function upload_image(array $file, string $subdir, int $maxBytes = 2097152): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxBytes) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;
    $name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . $subdir . '/' . $name;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
    return move_uploaded_file($file['tmp_name'], $dest) ? $subdir . '/' . $name : null;
}
