<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? ''; $idp = (int)($_POST['id_sepatu'] ?? 0);
    try {
        if ($aksi === 'tambah') {
            $uk = trim($_POST['ukuran'] ?? ''); $wr = trim($_POST['warna'] ?? ''); $jm = (int)($_POST['jumlah_stok'] ?? 0);
            if ($uk === '' || $wr === '' || $jm < 0) flash('error', 'Ukuran, warna, dan jumlah wajib diisi.');
            else { $pdo->prepare('INSERT INTO stok_sepatu (id_sepatu,ukuran,warna,jumlah_stok) VALUES (?,?,?,?)')->execute([$idp, $uk, $wr, $jm]); flash('success', 'Varian ditambahkan.'); }
        } elseif ($aksi === 'ubah') {
            $jm = (int)($_POST['jumlah_stok'] ?? -1);
            if ($jm < 0) flash('error', 'Jumlah stok tidak valid.');
            else { $pdo->prepare('UPDATE stok_sepatu SET jumlah_stok=? WHERE id_stok=? AND id_sepatu=?')->execute([$jm, (int)$_POST['id_stok'], $idp]); flash('success', 'Stok diperbarui.'); }
        } elseif ($aksi === 'hapus') {
            $pdo->prepare('DELETE FROM stok_sepatu WHERE id_stok=? AND id_sepatu=?')->execute([(int)$_POST['id_stok'], $idp]); flash('success', 'Varian dihapus.');
        }
    } catch (PDOException $ex) {
        flash('error', $aksi === 'hapus' ? 'Varian sudah ada di riwayat pesanan, ubah stoknya menjadi 0 saja.' : 'Varian ukuran & warna tersebut sudah ada.');
    }
    redirect('admin/stok.php?id=' . $idp);
}

$judul = 'Stok';
include __DIR__ . '/_header.php';
$produk = $pdo->query('SELECT id_sepatu,nama_sepatu FROM sepatu ORDER BY nama_sepatu')->fetchAll();
$sel = null;
if ($id) { $s = $pdo->prepare('SELECT * FROM sepatu WHERE id_sepatu=?'); $s->execute([$id]); $sel = $s->fetch(); }
?>
<h2>Stok sepatu</h2>
<form method="get" class="inline-form" style="margin-bottom:1.5rem;max-width:520px">
  <select name="id" aria-label="Pilih produk"><option value="">Pilih produk...</option><?php foreach ($produk as $p): ?><option value="<?= $p['id_sepatu'] ?>" <?= $id == $p['id_sepatu'] ? 'selected' : '' ?>><?= e($p['nama_sepatu']) ?></option><?php endforeach; ?></select>
  <button class="btn">Buka</button></form>
<?php if ($sel):
    $v = $pdo->prepare('SELECT * FROM stok_sepatu WHERE id_sepatu=? ORDER BY CAST(ukuran AS UNSIGNED), warna'); $v->execute([$id]); $varian = $v->fetchAll(); ?>
  <h3><?= e($sel['nama_sepatu']) ?></h3>
  <div class="two-col">
    <div class="table-wrap"><table>
      <thead><tr><th>Ukuran</th><th>Warna</th><th>Stok</th><th></th></tr></thead><tbody>
      <?php foreach ($varian as $x): ?>
      <tr><td><?= e($x['ukuran']) ?></td><td><?= e($x['warna']) ?></td>
      <td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id_sepatu" value="<?= $id ?>"><input type="hidden" name="id_stok" value="<?= $x['id_stok'] ?>">
        <input type="number" name="jumlah_stok" min="0" value="<?= $x['jumlah_stok'] ?>" aria-label="Jumlah stok"><button class="btn btn-ghost btn-sm">Simpan</button></form></td>
      <td><form method="post" data-confirm="Hapus varian ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_sepatu" value="<?= $id ?>"><input type="hidden" name="id_stok" value="<?= $x['id_stok'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></td></tr>
      <?php endforeach; if (!$varian): ?><tr><td colspan="4" class="muted">Belum ada varian.</td></tr><?php endif; ?></tbody></table></div>
    <form method="post" class="form-card"><?= csrf_field() ?><input type="hidden" name="aksi" value="tambah"><input type="hidden" name="id_sepatu" value="<?= $id ?>">
      <h3>Tambah varian</h3>
      <div class="field"><label for="ukuran">Ukuran</label><input id="ukuran" name="ukuran" placeholder="42" required></div>
      <div class="field"><label for="warna">Warna</label><input id="warna" name="warna" placeholder="Hitam" required></div>
      <div class="field"><label for="jumlah_stok">Jumlah stok</label><input type="number" id="jumlah_stok" name="jumlah_stok" min="0" value="0" required></div>
      <button class="btn">Tambah varian</button></form>
  </div>
<?php elseif ($id): ?><div class="alert alert-error">Produk tidak ditemukan.</div><?php endif; ?>
<?php include __DIR__ . '/_footer.php'; ?>
