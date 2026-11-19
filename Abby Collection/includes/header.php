<?php
require_once __DIR__ . '/functions.php';
$u = user();
$judul = isset($judul) ? $judul . ' | ' . NAMA_TOKO : NAMA_TOKO . ' - Sepatu pilihan untuk setiap langkah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="logo" href="<?= url('index.php') ?>"><span class="logo-pills"><i></i><i></i><i></i></span><?= e(NAMA_TOKO) ?></a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="menu">Menu</button>
    <nav class="nav-links" id="menu" aria-label="Navigasi utama">
      <a href="<?= url('index.php') ?>">Katalog</a>
      <?php if ($u && $u['peran'] === 'admin'): ?>
        <a href="<?= url('admin/index.php') ?>">Panel admin</a>
        <a href="<?= url('logout.php') ?>">Keluar</a>
      <?php elseif ($u): ?>
        <a href="<?= url('pesanan.php') ?>">Pesanan</a>
        <a href="<?= url('profil.php') ?>">Profil</a>
        <a class="cart-link" href="<?= url('keranjang.php') ?>">Keranjang (<?= cart_count() ?>)</a>
        <a href="<?= url('logout.php') ?>">Keluar</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Masuk</a>
        <a class="cart-link" href="<?= url('register.php') ?>">Daftar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
<div class="container"><?php show_flash(); ?></div>
