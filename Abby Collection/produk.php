<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT s.*, k.nama_kategori, m.nama_merek FROM sepatu s
    JOIN kategori k ON k.id_kategori=s.id_kategori JOIN merek m ON m.id_merek=s.id_merek WHERE s.id_sepatu=?');
$st->execute([$id]);
$s = $st->fetch();
if (!$s) { http_response_code(404); flash('error', 'Produk tidak ditemukan.'); redirect('index.php'); }

$v = $pdo->prepare('SELECT * FROM stok_sepatu WHERE id_sepatu=? ORDER BY CAST(ukuran AS UNSIGNED), warna');
$v->execute([$id]);
$varian = $v->fetchAll();

// Tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = user();
    if (!$u) { flash('info', 'Masuk dulu untuk menambahkan ke keranjang.'); redirect('login.php'); }
    if ($u['peran'] !== 'pelanggan') { flash('error', 'Akun admin tidak dapat berbelanja.'); redirect('produk.php?id=' . $id); }

    $idStok = (int)($_POST['id_stok'] ?? 0);
    $qty = max(1, (int)($_POST['kuantitas'] ?? 1));
    $cek = $pdo->prepare('SELECT jumlah_stok FROM stok_sepatu WHERE id_stok=? AND id_sepatu=?');
    $cek->execute([$idStok, $id]);
    $stok = $cek->fetchColumn();
    if ($stok === false) {
        flash('error', 'Pilih ukuran dan warna terlebih dahulu.');
    } else {
        $ada = $pdo->prepare('SELECT kuantitas FROM keranjang WHERE id_pengguna=? AND id_stok=?');
        $ada->execute([$u['id_pengguna'], $idStok]);
        $sudah = (int)$ada->fetchColumn();
        if ($sudah + $qty > $stok) {
            flash('error', "Stok tidak cukup. Tersedia $stok, di keranjang kamu sudah $sudah.");
        } else {
            $pdo->prepare('INSERT INTO keranjang (id_pengguna,id_stok,kuantitas) VALUES (?,?,?)
                           ON DUPLICATE KEY UPDATE kuantitas = kuantitas + VALUES(kuantitas)')
                ->execute([$u['id_pengguna'], $idStok, $qty]);
            flash('success', 'Ditambahkan ke keranjang.');
            redirect('keranjang.php');
        }
    }
    redirect('produk.php?id=' . $id);
}

$judul = $s['nama_sepatu'];
include __DIR__ . '/includes/header.php';
$pertama = null;
foreach ($varian as $x) if ($x['jumlah_stok'] > 0) { $pertama = $x; break; }
?>
<div class="container detail">
  <div class="detail-img"><?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" alt="<?= e($s['nama_sepatu']) ?>"><?php else: echo shoe_svg(); endif; ?></div>
  <div>
    <p class="muted"><a href="<?= url('index.php?merek=' . $s['id_merek']) ?>"><?= e($s['nama_merek']) ?></a> &middot; <a href="<?= url('index.php?kategori=' . $s['id_kategori']) ?>"><?= e($s['nama_kategori']) ?></a></p>
    <h1 style="font-size:clamp(2rem,4vw,3rem)"><?= e($s['nama_sepatu']) ?></h1>
    <div class="price"><?= rupiah($s['harga']) ?></div>
    <p><?= nl2br(e($s['deskripsi'])) ?></p>

    <?php if (!$varian): ?>
      <div class="alert alert-info">Varian ukuran belum tersedia.</div>
    <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <fieldset style="border:0;padding:0;margin:0 0 1rem">
        <legend style="font-weight:600;margin-bottom:.5rem">Pilih ukuran &amp; warna</legend>
        <div class="varian">
          <?php foreach ($varian as $x): $habis = $x['jumlah_stok'] < 1; ?>
          <label><input type="radio" name="id_stok" value="<?= $x['id_stok'] ?>" data-stok="<?= $x['jumlah_stok'] ?>" <?= $habis ? 'disabled' : '' ?> <?= $pertama && $pertama['id_stok'] == $x['id_stok'] ? 'checked' : '' ?>>
            <span><?= e($x['ukuran']) ?> &middot; <?= e($x['warna']) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <p id="stok-info" class="hint"><?= $pertama ? 'Stok tersedia: ' . $pertama['jumlah_stok'] : 'Semua varian habis.' ?></p>
      <div class="field"><label for="kuantitas" style="font-weight:600">Jumlah</label>
        <div class="qty"><button type="button" data-step="-" aria-label="Kurangi">&minus;</button>
          <input type="number" id="kuantitas" name="kuantitas" value="1" min="1" max="<?= $pertama['jumlah_stok'] ?? 1 ?>" data-qty-for-stock>
          <button type="button" data-step="+" aria-label="Tambah">+</button></div></div>
      <button class="btn" <?= $pertama ? '' : 'disabled' ?>>Tambah ke keranjang</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
