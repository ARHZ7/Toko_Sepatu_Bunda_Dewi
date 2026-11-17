<?php
$judul = 'Pengguna';
include __DIR__ . '/_header.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id):
    $s = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna=? AND peran='pelanggan'"); $s->execute([$id]); $u = $s->fetch();
    if (!$u) { echo '<div class="alert alert-error">Pelanggan tidak ditemukan.</div>'; include __DIR__ . '/_footer.php'; exit; }
    $a = $pdo->prepare('SELECT * FROM alamat WHERE id_pengguna=?'); $a->execute([$id]); $alamat = $a->fetchAll();
    $o = $pdo->prepare('SELECT * FROM pesanan WHERE id_pengguna=? ORDER BY id_pesanan DESC'); $o->execute([$id]); $pesanan = $o->fetchAll();
?>
<div class="page-head"><h2><?= e($u['nama']) ?></h2><a class="btn btn-ghost" href="<?= url('admin/pengguna.php') ?>">Kembali</a></div>
<div class="form-card" style="margin-bottom:1.5rem"><p><strong>Email:</strong> <?= e($u['email']) ?><br><strong>No. HP:</strong> <?= e($u['no_hp']) ?><br><strong>Terdaftar:</strong> <?= date('d M Y', strtotime($u['dibuat_pada'])) ?></p></div>
<h3>Alamat</h3>
<?php foreach ($alamat as $al): ?><div class="form-card" style="margin-bottom:.8rem;padding:1rem"><strong><?= e($al['label_alamat']) ?></strong><br><?= e($al['alamat_lengkap']) ?>, <?= e($al['kota_kabupaten']) ?>, <?= e($al['provinsi']) ?> <?= e($al['kode_pos']) ?></div><?php endforeach; if (!$alamat) echo '<p class="muted">Belum ada alamat.</p>'; ?>
<h3 style="margin-top:1.5rem">Pesanan</h3>
<div class="table-wrap"><table><thead><tr><th>No.</th><th>Tanggal</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($pesanan as $p): ?><tr><td>#<?= $p['id_pesanan'] ?></td><td><?= date('d M Y', strtotime($p['tanggal_pesan'])) ?></td><td><?= rupiah($p['total_bayar']) ?></td><td><?= badge_status($p['status_pesanan']) ?></td><td><a class="btn btn-ghost btn-sm" href="<?= url('admin/pesanan.php?id=' . $p['id_pesanan']) ?>">Buka</a></td></tr><?php endforeach; if (!$pesanan): ?><tr><td colspan="5" class="muted">Belum ada pesanan.</td></tr><?php endif; ?></tbody></table></div>
<?php else:
    $list = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM pesanan p WHERE p.id_pengguna=u.id_pengguna) AS jml FROM pengguna u WHERE u.peran='pelanggan' ORDER BY u.id_pengguna DESC")->fetchAll();
?>
<h2>Akun pelanggan</h2>
<div class="table-wrap"><table><thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Terdaftar</th><th>Pesanan</th><th></th></tr></thead><tbody>
<?php foreach ($list as $u): ?><tr><td><?= e($u['nama']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['no_hp']) ?></td><td><?= date('d M Y', strtotime($u['dibuat_pada'])) ?></td><td><?= $u['jml'] ?></td><td><a class="btn btn-ghost btn-sm" href="<?= url('admin/pengguna.php?id=' . $u['id_pengguna']) ?>">Detail</a></td></tr><?php endforeach; if (!$list): ?><tr><td colspan="6" class="muted">Belum ada pelanggan.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; include __DIR__ . '/_footer.php'; ?>
