<?php
/**
 * Kinar Resto - Autentikasi & Hak Akses
 */

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function login_user(int $id): void {
    $st = db()->prepare('SELECT id, name, username, role FROM users WHERE id = ? AND is_active = 1');
    $st->execute([$id]);
    $_SESSION['user'] = $st->fetch();
}

function require_login(string ...$roles): void {
    $u = current_user();
    if (!$u) {
        redirect(BASE_URL . '/admin/login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }
}
