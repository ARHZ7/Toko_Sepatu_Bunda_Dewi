<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'data') {
        $nama = trim($_POST['nama'] ?? ''); $hp = trim($_POST['no_hp'] ?? '');
        if ($nama === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $hp)) flash('error', 'Nama wajib diisi dan nomor HP harus valid.');
        else { $pdo->prepare('UPDATE pengguna SET nama=?, no_hp=? WHERE id_pengguna=?')->execute([$nama, $hp, $u['id_pengguna']]); flash('success', 'Data diri diperbarui.'); }
    } elseif ($aksi === 'password') {
        $s = $pdo->prepare('SELECT password FROM pengguna WHERE id_pengguna=?'); $s->execute([$u['id_pengguna']]);
        $baru = $_POST['baru'] ?? '';
        if (!password_verify($_POST['lama'] ?? '', $s->fetchColumn())) flash('error', 'Password lama salah.');
        elseif (strlen($baru) < 8) flash('error', 'Password baru minimal 8 karakter.');
        elseif ($baru !== ($_POST['baru2'] ?? '')) flash('error', 'Konfirmasi password tidak sama.');
        else { $pdo->prepare('UPDATE pengguna SET password=? WHERE id_pengguna=?')->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id_pengguna']]); flash('success', 'Password berhasil diubah.'); }
    } elseif ($aksi === 'alamat_simpan' && $u['peran'] === 'pelanggan') {
        $f = array_map(fn($k) => trim($_POST[$k] ?? ''), ['label_alamat', 'alamat_lengkap', 'kota_kabupaten', 'provinsi', 'kode_pos']);
        if (in_array('', $f, true)) flash('error', 'Semua kolom alamat wajib diisi.');
        else {
            $ida = (int)($_POST['id_alamat'] ?? 0);
            if ($ida) { $pdo->prepare('UPDATE alamat SET label_alamat=?,alamat_lengkap=?,kota_kabupaten=?,provinsi=?,kode_pos=? WHERE id_alamat=? AND id_pengguna=?')->execute([...$f, $ida, $u['id_pengguna']]); flash('success', 'Alamat diperbarui.'); }
            else { $pdo->prepare('INSERT INTO alamat (label_alamat,alamat_lengkap,kota_kabupaten,provinsi,kode_pos,id_pengguna) VALUES (?,?,?,?,?,?)')->execute([...$f, $u['id_pengguna']]); flash('success', 'Alamat ditambahkan.'); }
        }
    } elseif ($aksi === 'alamat_hapus') {
        $pdo->prepare('DELETE FROM alamat WHERE id_alamat=? AND id_pengguna=?')->execute([(int)($_POST['id_alamat'] ?? 0), $u['id_pengguna']]);
        flash('success', 'Alamat dihapus.');
    }
    redirect('profil.php');
}

$a = $pdo->prepare('SELECT * FROM alamat WHERE id_pengguna=? ORDER BY id_alamat'); $a->execute([$u['id_pengguna']]);
$alamatList = $a->fetchAll();
$edit = null;
foreach ($alamatList as $al) if ($al['id_alamat'] == ($_GET['edit'] ?? 0)) $edit = $al;

$judul = 'Profil';
include __DIR__ . '/includes/header.php';
?>
<div class="container page">
  <h2>Profil</h2>
  <div class="row" style="align-items:start">
    <div class="form-card"><h3>Data diri</h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="data">
        <div class="field"><label for="nama">Nama</label><input id="nama" name="nama" value="<?= e($u['nama']) ?>" required></div>
        <div class="field"><label for="email">Email</label><input id="email" value="<?= e($u['email']) ?>" disabled></div>
        <div class="field"><label for="no_hp">Nomor HP</label><input id="no_hp" name="no_hp" value="<?= e($u['no_hp']) ?>" required></div>
        <button class="btn">Simpan data diri</button></form></div>
    <div class="form-card"><h3>Ubah password</h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="password">
        <div class="field"><label for="lama">Password lama</label><input type="password" id="lama" name="lama" required></div>
        <div class="field"><label for="baru">Password baru</label><input type="password" id="baru" name="baru" minlength="8" required></div>
        <div class="field"><label for="baru2">Ulangi password baru</label><input type="password" id="baru2" name="baru2" required></div>
        <button class="btn">Ubah password</button></form></div>
  </div>

  <?php if ($u['peran'] === 'pelanggan'): ?>
  <h2 id="alamat" style="margin-top:3rem">Alamat pengiriman</h2>
  <div class="row" style="align-items:start">
    <div>
      <?php if (!$alamatList): ?><div class="empty">Belum ada alamat tersimpan.</div><?php endif; ?>
      <?php foreach ($alamatList as $al): ?>
      <div class="form-card" style="margin-bottom:1rem;padding:1.2rem">
        <strong><?= e($al['label_alamat']) ?></strong><br><?= e($al['alamat_lengkap']) ?><br><?= e($al['kota_kabupaten']) ?>, <?= e($al['provinsi']) ?> <?= e($al['kode_pos']) ?>
        <div class="actions" style="margin-top:.7rem"><a class="btn btn-ghost btn-sm" href="<?= url('profil.php?edit=' . $al['id_alamat']) ?>#form-alamat">Ubah</a>
          <form method="post" data-confirm="Hapus alamat ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="alamat_hapus"><input type="hidden" name="id_alamat" value="<?= $al['id_alamat'] ?>"><button class="btn btn-ghost btn-sm">Hapus</button></form></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="form-card" id="form-alamat"><h3><?= $edit ? 'Ubah alamat' : 'Tambah alamat' ?></h3>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="alamat_simpan"><input type="hidden" name="id_alamat" value="<?= $edit['id_alamat'] ?? 0 ?>">
        <div class="field"><label for="label_alamat">Label (Rumah, Kantor)</label><input id="label_alamat" name="label_alamat" value="<?= e($edit['label_alamat'] ?? '') ?>" required></div>
        <div class="field"><label for="alamat_lengkap">Alamat lengkap</label><textarea id="alamat_lengkap" name="alamat_lengkap" required><?= e($edit['alamat_lengkap'] ?? '') ?></textarea></div>
        <div class="row"><div class="field"><label for="kota_kabupaten">Kota/Kabupaten</label><input id="kota_kabupaten" name="kota_kabupaten" value="<?= e($edit['kota_kabupaten'] ?? '') ?>" required></div>
        <div class="field"><label for="provinsi">Provinsi</label><input id="provinsi" name="provinsi" value="<?= e($edit['provinsi'] ?? '') ?>" required></div></div>
        <div class="field"><label for="kode_pos">Kode pos</label><input id="kode_pos" name="kode_pos" value="<?= e($edit['kode_pos'] ?? '') ?>" required></div>
        <button class="btn"><?= $edit ? 'Simpan perubahan' : 'Tambah alamat' ?></button>
        <?php if ($edit): ?><a class="btn btn-ghost" href="<?= url('profil.php#alamat') ?>">Batal</a><?php endif; ?></form></div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
