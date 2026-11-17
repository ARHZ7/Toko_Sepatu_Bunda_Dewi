<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$STATUS = ['belum_bayar', 'dikemas', 'dikirim', 'siap_diambil', 'selesai', 'dibatalkan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    csrf_check();
    $s = $pdo->prepare('SELECT p.status_pesanan, pb.id_pembayaran FROM pesanan p LEFT JOIN pembayaran pb ON pb.id_pesanan=p.id_pesanan WHERE p.id_pesanan=?');
    $s->execute([$id]); $now = $s->fetch();
    if ($now) {
        $aksi = $_POST['aksi'] ?? '';
        if ($now['status_pesanan'] === 'dibatalkan') {
            flash('error', 'Pesanan yang sudah dibatalkan tidak dapat diubah.');
        } elseif ($aksi === 'konfirmasi') {
            $pdo->prepare('UPDATE pembayaran SET status_pembayaran="berhasil" WHERE id_pembayaran=?')->execute([$now['id_pembayaran']]);
            if ($now['status_pesanan'] === 'belum_bayar') $pdo->prepare('UPDATE pesanan SET status_pesanan="dikemas" WHERE id_pesanan=?')->execute([$id]);
            flash('success', 'Pembayaran dikonfirmasi. Pesanan masuk tahap dikemas.');
        } elseif ($aksi === 'tolak') {
            $pdo->prepare('UPDATE pembayaran SET status_pembayaran="gagal" WHERE id_pembayaran=?')->execute([$now['id_pembayaran']]);
            flash('success', 'Pembayaran ditandai gagal. Pelanggan dapat mengunggah ulang bukti.');
        } elseif ($aksi === 'status') {
            $baru = $_POST['status_pesanan'] ?? '';
            if (in_array($baru, $STATUS, true)) {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE pesanan SET status_pesanan=?, kurir=NULLIF(?,""), no_resi=NULLIF(?,"") WHERE id_pesanan=?')
                    ->execute([$baru, trim($_POST['kurir'] ?? ''), trim($_POST['no_resi'] ?? ''), $id]);
                if ($baru === 'dibatalkan') kembalikan_stok($id);
                $pdo->commit();
                flash('success', 'Pesanan diperbarui.');
            }
        }
    }
    redirect('admin/pesanan.php?id=' . $id);
}

$judul = $id ? "Pesanan #$id" : 'Pesanan & pembayaran';
include __DIR__ . '/_header.php';

if ($id):
    $s = $pdo->prepare('SELECT p.*, u.nama, u.email, u.no_hp, pb.metode_pembayaran, pb.status_pembayaran, pb.bukti_transfer, pb.tanggal_bayar, pb.jumlah_transfer,
        a.label_alamat,a.alamat_lengkap,a.kota_kabupaten,a.provinsi,a.kode_pos
        FROM pesanan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna LEFT JOIN pembayaran pb ON pb.id_pesanan=p.id_pesanan LEFT JOIN alamat a ON a.id_alamat=p.id_alamat WHERE p.id_pesanan=?');
    $s->execute([$id]); $p = $s->fetch();
    if (!$p) { echo '<div class="alert alert-error">Pesanan tidak ditemukan.</div>'; include __DIR__ . '/_footer.php'; exit; }
    $d = $pdo->prepare('SELECT d.*, st.ukuran, st.warna, sp.nama_sepatu FROM detail_pesanan d JOIN stok_sepatu st ON st.id_stok=d.id_stok JOIN sepatu sp ON sp.id_sepatu=st.id_sepatu WHERE d.id_pesanan=?');
    $d->execute([$id]); $detail = $d->fetchAll();
?>
<div class="page-head"><div><h2>Pesanan #<?= $id ?></h2><span class="muted"><?= date('d M Y H:i', strtotime($p['tanggal_pesan'])) ?></span></div><a class="btn btn-ghost" href="<?= url('admin/pesanan.php') ?>">Kembali</a></div>
<div class="two-col">
  <div>
    <div class="table-wrap" style="margin-bottom:1.5rem"><table><thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead><tbody>
      <?php foreach ($detail as $x): ?><tr><td><strong><?= e($x['nama_sepatu']) ?></strong><br><span class="muted"><?= e($x['ukuran']) ?> &middot; <?= e($x['warna']) ?></span></td><td><?= rupiah($x['harga_saat_beli']) ?></td><td><?= $x['jumlah'] ?></td><td><?= rupiah($x['harga_saat_beli'] * $x['jumlah']) ?></td></tr><?php endforeach; ?>
      <tr><td colspan="3" style="text-align:right">Ongkos kirim</td><td><?= rupiah($p['ongkos_kirim']) ?></td></tr>
      <tr><td colspan="3" style="text-align:right"><strong>Total bayar</strong></td><td><strong><?= rupiah($p['total_bayar']) ?></strong></td></tr></tbody></table></div>
    <div class="form-card" style="margin-bottom:1.5rem"><h3>Pelanggan &amp; pengiriman</h3>
      <p><strong><?= e($p['nama']) ?></strong><br><?= e($p['email']) ?> &middot; <?= e($p['no_hp']) ?></p>
      <?php if ($p['metode_pengiriman'] === 'diantar'): ?><p>Diantar ke <strong><?= e($p['label_alamat']) ?></strong>: <?= e($p['alamat_lengkap']) ?>, <?= e($p['kota_kabupaten']) ?>, <?= e($p['provinsi']) ?> <?= e($p['kode_pos']) ?></p>
      <?php else: ?><p>Ambil di tempat (<?= e(LOKASI_AMBIL) ?>)</p><?php endif; ?></div>
    <div class="form-card"><h3>Pembayaran <?= badge_status($p['status_pembayaran'] ?? 'pending') ?></h3>
      <p>Metode: <strong><?= ($p['metode_pembayaran'] ?? '') === 'qris' ? 'QRIS' : 'Transfer bank' ?></strong></p>
      <?php if ($p['bukti_transfer']): ?>
        <p>Dikirim <?= date('d M Y H:i', strtotime($p['tanggal_bayar'])) ?> sebesar <?= rupiah($p['jumlah_transfer']) ?></p>
        <a href="<?= url('assets/uploads/bukti/' . $p['bukti_transfer']) ?>" target="_blank" rel="noopener"><img src="<?= url('assets/uploads/bukti/' . $p['bukti_transfer']) ?>" alt="Bukti pembayaran" style="max-width:280px;border-radius:12px;margin-bottom:1rem"></a>
        <?php if ($p['status_pesanan'] !== 'dibatalkan'): ?>
        <div class="actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="konfirmasi"><button class="btn">Konfirmasi pembayaran</button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><button class="btn btn-danger">Tolak bukti</button></form></div><?php endif; ?>
      <?php else: ?><p class="muted">Pelanggan belum mengunggah bukti pembayaran.</p><?php endif; ?></div>
  </div>
  <aside class="summary"><h3>Status pesanan</h3><p><?= badge_status($p['status_pesanan']) ?></p>
    <?php if ($p['status_pesanan'] !== 'dibatalkan'): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="status">
      <div class="field"><label for="status_pesanan">Ubah status</label><select id="status_pesanan" name="status_pesanan"><?php foreach ($STATUS as $st): ?><option value="<?= $st ?>" <?= $p['status_pesanan'] === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option><?php endforeach; ?></select><span class="hint">Memilih "Dibatalkan" mengembalikan stok.</span></div>
      <?php if ($p['metode_pengiriman'] === 'diantar'): ?>
      <div class="field"><label for="kurir">Kurir</label><input id="kurir" name="kurir" value="<?= e($p['kurir']) ?>"></div>
      <div class="field"><label for="no_resi">No. resi</label><input id="no_resi" name="no_resi" value="<?= e($p['no_resi']) ?>"></div>
      <?php endif; ?>
      <button class="btn btn-block">Simpan</button></form>
    <?php endif; ?></aside>
</div>
<?php else:
    $f = $_GET['status'] ?? '';
    $sql = 'SELECT p.*, u.nama, pb.status_pembayaran, pb.bukti_transfer FROM pesanan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna LEFT JOIN pembayaran pb ON pb.id_pesanan=p.id_pesanan';
    $par = [];
    if (in_array($f, $STATUS, true)) { $sql .= ' WHERE p.status_pesanan=?'; $par[] = $f; }
    $q = $pdo->prepare($sql . ' ORDER BY p.id_pesanan DESC'); $q->execute($par); $list = $q->fetchAll();
?>
<div class="page-head"><h2>Pesanan &amp; pembayaran</h2>
  <form method="get" class="inline-form"><select name="status" aria-label="Filter status"><option value="">Semua status</option><?php foreach ($STATUS as $st): ?><option value="<?= $st ?>" <?= $f === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option><?php endforeach; ?></select><button class="btn btn-ghost">Filter</button></form></div>
<div class="table-wrap"><table><thead><tr><th>No.</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($list as $p): ?>
  <tr><td>#<?= $p['id_pesanan'] ?></td><td><?= e($p['nama']) ?></td><td><?= date('d M Y H:i', strtotime($p['tanggal_pesan'])) ?></td><td><?= rupiah($p['total_bayar']) ?></td>
  <td><?= badge_status($p['status_pembayaran'] ?? 'pending') ?><?= $p['bukti_transfer'] && ($p['status_pembayaran'] ?? '') === 'pending' ? ' <span class="hint">bukti masuk</span>' : '' ?></td>
  <td><?= badge_status($p['status_pesanan']) ?></td><td><a class="btn btn-ghost btn-sm" href="<?= url('admin/pesanan.php?id=' . $p['id_pesanan']) ?>">Buka</a></td></tr>
  <?php endforeach; if (!$list): ?><tr><td colspan="7" class="muted">Tidak ada pesanan.</td></tr><?php endif; ?></tbody></table></div>
<?php endif; include __DIR__ . '/_footer.php'; ?>
