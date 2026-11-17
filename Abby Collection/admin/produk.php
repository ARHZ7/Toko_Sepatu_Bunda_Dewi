<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id_sepatu'] ?? 0);
    if ($aksi === 'hapus') {
        try {
            $f = $pdo->prepare('SELECT foto FROM sepatu WHERE id_sepatu=?'); $f->execute([$id]);
            $foto = $f->fetchColumn();
            $pdo->prepare('DELETE FROM sepatu WHERE id_sepatu=?')->execute([$id]);
            hapus_gambar($foto ?: null, 'produk');
            flash('success', 'Produk dihapus.');
        } catch (PDOException $ex) {
            flash('error', 'Produk tidak bisa dihapus karena sudah ada di riwayat pesanan. Kosongkan stoknya saja.');
        }
        redirect('admin/produk.php');
    }
    if ($aksi === 'simpan') {
        $nama = trim($_POST['nama_sepatu'] ?? ''); $desk = trim($_POST['deskripsi'] ?? '');
        $harga = $_POST['harga'] ?? ''; $kat = (int)($_POST['id_kategori'] ?? 0); $mer = (int)($_POST['id_merek'] ?? 0);
        if ($nama === '' || !is_numeric($harga) || $harga < 0 || !$kat || !$mer) {
            flash('error', 'Nama, harga, kategori, dan merek wajib diisi dengan benar.');
            redirect('admin/produk.php?form=1' . ($id ? "&id=$id" : ''));
        }
        try {
            $fotoBaru = upload_gambar($_FILES['foto'] ?? [], 'produk');
            if ($id) {
                $lama = $pdo->prepare('SELECT foto FROM sepatu WHERE id_sepatu=?'); $lama->execute([$id]);
                $fotoLama = $lama->fetchColumn();
                $pdo->prepare('UPDATE sepatu SET nama_sepatu=?,deskripsi=?,harga=?,id_kategori=?,id_merek=?,foto=COALESCE(?,foto) WHERE id_sepatu=?')
                    ->execute([$nama, $desk, $harga, $kat, $mer, $fotoBaru, $id]);
                if ($fotoBaru) hapus_gambar($fotoLama ?: null, 'produk');
                flash('success', 'Produk diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO sepatu (nama_sepatu,deskripsi,harga,id_kategori,id_merek,foto) VALUES (?,?,?,?,?,?)')
                    ->execute([$nama, $desk, $harga, $kat, $mer, $fotoBaru]);
                flash('success', 'Produk ditambahkan. Lanjutkan dengan menambah varian ukuran & warna di menu Stok.');
            }
        } catch (RuntimeException $ex) { flash('error', $ex->getMessage()); redirect('admin/produk.php?form=1' . ($id ? "&id=$id" : '')); }
        redirect('admin/produk.php');
    }
}

$judul = 'Produk';
$kategoriList = $pdo->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$merekList = $pdo->query('SELECT * FROM merek ORDER BY nama_merek')->fetchAll();
include __DIR__ . '/_header.php';

if (isset($_GET['form'])):
    $row = ['nama_sepatu' => '', 'deskripsi' => '', 'harga' => '', 'id_kategori' => 0, 'id_merek' => 0, 'foto' => null, 'id_sepatu' => 0];
    if (!empty($_GET['id'])) { $s = $pdo->prepare('SELECT * FROM sepatu WHERE id_sepatu=?'); $s->execute([(int)$_GET['id']]); $row = $s->fetch() ?: $row; }
?>
<h2><?= $row['id_sepatu'] ? 'Ubah produk' : 'Tambah produk' ?></h2>
<?php if (!$kategoriList || !$merekList): ?><div class="alert alert-info">Tambahkan minimal satu merek dan satu kategori dulu di menu <a href="<?= url('admin/master.php') ?>">Merek &amp; kategori</a>.</div><?php endif; ?>
<form class="form-card" method="post" enctype="multipart/form-data" style="max-width:680px"><?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id_sepatu" value="<?= $row['id_sepatu'] ?>">
  <div class="field"><label for="nama_sepatu">Nama sepatu</label><input id="nama_sepatu" name="nama_sepatu" value="<?= e($row['nama_sepatu']) ?>" required></div>
  <div class="row">
    <div class="field"><label for="id_merek">Merek</label><select id="id_merek" name="id_merek" required><option value="">Pilih</option><?php foreach ($merekList as $m): ?><option value="<?= $m['id_merek'] ?>" <?= $row['id_merek'] == $m['id_merek'] ? 'selected' : '' ?>><?= e($m['nama_merek']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="id_kategori">Kategori</label><select id="id_kategori" name="id_kategori" required><option value="">Pilih</option><?php foreach ($kategoriList as $k): ?><option value="<?= $k['id_kategori'] ?>" <?= $row['id_kategori'] == $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="field"><label for="harga">Harga (Rp)</label><input type="number" id="harga" name="harga" min="0" step="1000" value="<?= e($row['harga'] !== '' ? (int)$row['harga'] : '') ?>" required></div>
  <div class="field"><label for="deskripsi">Deskripsi</label><textarea id="deskripsi" name="deskripsi"><?= e($row['deskripsi']) ?></textarea></div>
  <div class="field"><label for="foto-input">Foto produk</label>
    <?php if ($row['foto']): ?><img src="<?= e(foto_url($row['foto'])) ?>" alt="Foto saat ini" style="width:120px;border-radius:10px;margin-bottom:.5rem"><?php endif; ?>
    <img id="foto-preview" alt="Pratinjau foto baru" hidden style="width:120px;border-radius:10px;margin-bottom:.5rem">
    <input type="file" id="foto-input" name="foto" accept="image/jpeg,image/png,image/webp"><span class="hint">JPG, PNG, atau WEBP, maks 2 MB. Kosongkan jika tidak ingin mengganti foto.</span></div>
  <button class="btn">Simpan produk</button> <a class="btn btn-ghost" href="<?= url('admin/produk.php') ?>">Batal</a>
</form>
<?php else:
    $list = $pdo->query('SELECT s.*, k.nama_kategori, m.nama_merek, (SELECT COALESCE(SUM(jumlah_stok),0) FROM stok_sepatu x WHERE x.id_sepatu=s.id_sepatu) AS total_stok
        FROM sepatu s JOIN kategori k ON k.id_kategori=s.id_kategori JOIN merek m ON m.id_merek=s.id_merek ORDER BY s.id_sepatu DESC')->fetchAll();
?>
<div class="page-head"><h2>Produk</h2><a class="btn" href="<?= url('admin/produk.php?form=1') ?>">Tambah produk</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>Produk</th><th>Merek</th><th>Kategori</th><th>Harga</th><th>Total stok</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $s): ?>
  <tr><td><div class="cell-prod"><div class="thumb"><?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" alt=""><?php else: echo shoe_svg(); endif; ?></div><strong><?= e($s['nama_sepatu']) ?></strong></div></td>
  <td><?= e($s['nama_merek']) ?></td><td><?= e($s['nama_kategori']) ?></td><td><?= rupiah($s['harga']) ?></td><td><?= $s['total_stok'] ?></td>
  <td><div class="actions"><a class="btn btn-ghost btn-sm" href="<?= url('admin/stok.php?id=' . $s['id_sepatu']) ?>">Stok</a><a class="btn btn-ghost btn-sm" href="<?= url('admin/produk.php?form=1&id=' . $s['id_sepatu']) ?>">Ubah</a>
  <form method="post" data-confirm="Hapus produk ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_sepatu" value="<?= $s['id_sepatu'] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></div></td></tr>
  <?php endforeach; if (!$list): ?><tr><td colspan="6" class="muted">Belum ada produk.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php endif; include __DIR__ . '/_footer.php'; ?>
