<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$site = [
  'name'    => get_setting('site_name', 'Kinar Resto'),
  'tagline' => get_setting('tagline', 'Cita Rasa Lestari Nusantara'),
  'hero_title' => get_setting('hero_title', 'Harmoni Cita Rasa Autentik Nusantara'),
  'hero_sub'   => get_setting('hero_sub', 'Dari rempah pilihan hingga teknik memasak tradisional.'),
  'hero_image' => get_setting('hero_image', ''),
  'address' => get_setting('address', 'Jl. Senopati Raya No. 48, Kebayoran Baru, Jakarta Selatan'),
  'phone'   => get_setting('phone', '+62 812-0000-0000'),
  'wa'      => get_setting('wa', '6281200000000'),
  'hours'   => get_setting('hours', 'Senin–Kamis 11.00–21.30 · Jumat–Minggu 10.00–22.30 WIB'),
  'maps'    => get_setting('maps_embed', ''),
];
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title ?? $site['name']) ?> · <?= e($site['name']) ?></title>
<meta name="description" content="<?= e(get_setting('meta_description', $site['tagline'])) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/public.css">
</head>
<body>
<nav class="pub-nav">
  <span class="logo">🍃 <?= e($site['name']) ?></span>
  <a class="nl" href="<?= BASE_URL ?>/public/index.php">Beranda</a>
  <a class="nl" href="<?= BASE_URL ?>/public/menu-makanan.php">Menu Makanan</a>
  <a class="nl" href="<?= BASE_URL ?>/public/menu-minuman.php">Menu Minuman</a>
  <a class="nl" href="<?= BASE_URL ?>/public/warta.php">Warta Kuliner</a>
  <span class="spacer"></span>
  <a class="btn btn-green" style="padding:9px 18px;font-size:.82rem" href="#reservasi">Reservasi Meja</a>
</nav>
