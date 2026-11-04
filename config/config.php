<?php
/**
 * Kinar Resto - Konfigurasi Umum Sistem
 */
session_start();

define('BASE_URL', 'http://localhost/kinar');   // sesuaikan saat deploy
define('SITE_NAME', 'Kinar Resto');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Palet warna (mengacu gambar referensi)
define('COLOR_PRIMARY',   '#2D5A27'); // Forest Botanical Green
define('COLOR_ACCENT',    '#6A1B4D'); // Burgundy
define('COLOR_BG',        '#FBF9F5'); // Ivory Warm

require_once __DIR__ . '/database.php';

// Zona waktu Indonesia Barat
 date_default_timezone_set('Asia/Jakarta');
