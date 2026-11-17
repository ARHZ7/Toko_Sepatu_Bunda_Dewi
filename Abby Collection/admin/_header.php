<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$cur = basename($_SERVER['SCRIPT_NAME']);
$menu = ['index.php' => 'Dasbor', 'produk.php' => 'Produk', 'master.php' => 'Merek & kategori', 'stok.php' => 'Stok', 'pesanan.php' => 'Pesanan & pembayaran', 'pengguna.php' => 'Pengguna'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($judul ?? 'Admin') . ' | Admin ' . NAMA_TOKO) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<script src="<?= url('assets/js/main.js') ?>" defer></script>
</head>
<body>
<div class="admin-wrap">
  <aside class="sidebar">
    <a class="logo" href="<?= url('admin/index.php') ?>"><span class="logo-pills"><i></i><i></i><i></i></span>Admin</a>
    <?php foreach ($menu as $f => $nama): ?>
      <a class="item <?= $cur === $f ? 'active' : '' ?>" href="<?= url('admin/' . $f) ?>"><?= e($nama) ?></a>
    <?php endforeach; ?>
    <a class="item" href="<?= url('index.php') ?>">Lihat toko</a>
    <a class="item" href="<?= url('logout.php') ?>">Keluar</a>
  </aside>
  <main class="admin-main">
    <?php show_flash(); ?>
