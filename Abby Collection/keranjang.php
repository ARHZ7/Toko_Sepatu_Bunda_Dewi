<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
if ($u['peran'] !== 'pelanggan') redirect('admin/index.php');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? ''; $idk = (int)($_POST['id_keranjang'] ?? 0);
    if ($aksi === 'hapus') {
        $pdo->prepare('DELETE FROM keranjang WHERE id_keranjang=? AND id_pengguna=?')->execute([$idk, $u['id_pengguna']]);
        flash('success', 'Barang dihapus dari keranjang.');
    } elseif ($aksi === 'ubah') {
        $qty = max(1, (int)($_POST['kuantitas'] ?? 1));
        $c = $pdo->prepare('SELECT s.jumlah_stok FROM keranjang k JOIN stok_sepatu s ON s.id_stok=k.id_stok WHERE k.id_keranjang=? AND k.id_pengguna=?');
        $c->execute([$idk, $u['id_pengguna']]);
        $stok = $c->fetchColumn();
        if ($stok === false) flash('error', 'Item tidak ditemukan.');
        elseif ($qty > $stok) flash('error', "Stok hanya tersedia $stok.");
        else { $pdo->prepare('UPDATE keranjang SET kuantitas=? WHERE id_keranjang=? AND id_pengguna=?')->execute([$qty, $idk, $u['id_pengguna']]); flash('success', 'Jumlah diperbarui.'); }
    }
    redirect('keranjang.php');
}

$st = $pdo->prepare('SELECT k.id_keranjang,k.kuantitas,st.id_stok,st.ukuran,st.warna,st.jumlah_stok,sp.id_sepatu,sp.nama_sepatu,sp.harga,sp.foto
    FROM keranjang k JOIN stok_sepatu st ON st.id_stok=k.id_stok JOIN sepatu sp ON sp.id_sepatu=st.id_sepatu
    WHERE k.id_pengguna=? ORDER BY k.id_keranjang DESC');
$st->execute([$u['id_pengguna']]);
$items = $st->fetchAll();

$judul = 'Keranjang';
include __DIR__ . '/includes/header.php';
?>
<div class="container page">
  <h2>Keranjang belanja</h2>
  <?php if (!$items): ?>
    <div class="empty"><h3>Keranjang masih kosong</h3><p class="muted">Pilih sepatu favoritmu dulu.</p><a class="btn" href="<?= url('index.php') ?>">Lihat katalog</a></div>
  <?php else: ?>
  <div class="two-col">
    <div class="table-wrap"><table>
      <thead><tr><th><input type="checkbox" id="pilih-semua" checked aria-label="Pilih semua" style="width:auto"></th><th>Produk</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $i): $sub = $i['harga'] * $i['kuantitas']; $habis = $i['jumlah_stok'] < 1; ?>
      <tr>
        <td><input class="pilih-item" form="form-checkout" type="checkbox" name="ids[]" value="<?= $i['id_keranjang'] ?>" data-subtotal="<?= $sub ?>" <?= $habis ? 'disabled' : 'checked' ?> style="width:auto" aria-label="Pilih <?= e($i['nama_sepatu']) ?>"></td>
        <td><div class="cell-prod">
          <div class="thumb"><?php if ($i['foto']): ?><img src="<?= e(foto_url($i['foto'])) ?>" alt=""><?php else: echo shoe_svg(); endif; ?></div>
          <div><a href="<?= url('produk.php?id=' . $i['id_sepatu']) ?>"><strong><?= e($i['nama_sepatu']) ?></strong></a><br>
          <span class="muted">Ukuran <?= e($i['ukuran']) ?> &middot; <?= e($i['warna']) ?></span><br>
          <span class="muted"><?= rupiah($i['harga']) ?><?= $habis ? ' &middot; stok habis' : '' ?></span></div></div></td>
        <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id_keranjang" value="<?= $i['id_keranjang'] ?>">
          <input type="number" name="kuantitas" value="<?= $i['kuantitas'] ?>" min="1" max="<?= max(1, $i['jumlah_stok']) ?>" aria-label="Jumlah" style="width:76px"><button class="btn btn-ghost btn-sm">Ubah</button></form></td>
        <td><strong><?= rupiah($sub) ?></strong></td>
        <td><form method="post" data-confirm="Hapus barang ini dari keranjang?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_keranjang" value="<?= $i['id_keranjang'] ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form></td>
      </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <aside class="summary">
      <h3>Ringkasan</h3>
      <dl class="total"><dt>Subtotal terpilih</dt><dd id="subtotal-terpilih">Rp 0</dd></dl>
      <p class="hint">Ongkos kirim dihitung di halaman checkout.</p>
      <form id="form-checkout" method="post" action="<?= url('checkout.php') ?>"><?= csrf_field() ?><button class="btn btn-block">Lanjut ke checkout</button></form>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
