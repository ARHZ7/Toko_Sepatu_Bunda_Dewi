<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$ambil = function () use ($pdo, $id, $u) {
    $s = $pdo->prepare('SELECT p.*, pb.id_pembayaran, pb.metode_pembayaran, pb.status_pembayaran, pb.bukti_transfer, pb.tanggal_bayar,
        a.label_alamat,a.alamat_lengkap,a.kota_kabupaten,a.provinsi,a.kode_pos
        FROM pesanan p LEFT JOIN pembayaran pb ON pb.id_pesanan=p.id_pesanan LEFT JOIN alamat a ON a.id_alamat=p.id_alamat
        WHERE p.id_pesanan=? AND p.id_pengguna=?');
    $s->execute([$id, $u['id_pengguna']]);
    return $s->fetch();
};
$p = $ambil();
if (!$p) { flash('error', 'Pesanan tidak ditemukan.'); redirect('pesanan.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'bukti' && $p['status_pesanan'] === 'belum_bayar' && $p['status_pembayaran'] !== 'berhasil') {
        try {
            $nama = upload_gambar($_FILES['bukti'] ?? [], 'bukti');
            if (!$nama) throw new RuntimeException('Pilih file bukti pembayaran.');
            hapus_gambar($p['bukti_transfer'], 'bukti');
            $pdo->prepare('UPDATE pembayaran SET bukti_transfer=?, jumlah_transfer=?, tanggal_bayar=NOW(), status_pembayaran="pending" WHERE id_pembayaran=?')
                ->execute([$nama, $p['total_bayar'], $p['id_pembayaran']]);
            flash('success', 'Bukti terkirim. Admin akan memeriksa pembayaranmu.');
        } catch (RuntimeException $ex) { flash('error', $ex->getMessage()); }
    } elseif ($aksi === 'batal' && $p['status_pesanan'] === 'belum_bayar') {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE pesanan SET status_pesanan="dibatalkan" WHERE id_pesanan=?')->execute([$id]);
        kembalikan_stok($id);
        $pdo->commit();
        flash('success', 'Pesanan dibatalkan.');
    } elseif ($aksi === 'terima' && in_array($p['status_pesanan'], ['dikirim', 'siap_diambil'], true)) {
        $pdo->prepare('UPDATE pesanan SET status_pesanan="selesai" WHERE id_pesanan=?')->execute([$id]);
        flash('success', 'Terima kasih! Pesanan selesai.');
    }
    redirect('pesanan_detail.php?id=' . $id);
}

$d = $pdo->prepare('SELECT d.*, st.ukuran, st.warna, sp.id_sepatu, sp.nama_sepatu, sp.foto FROM detail_pesanan d
    JOIN stok_sepatu st ON st.id_stok=d.id_stok JOIN sepatu sp ON sp.id_sepatu=st.id_sepatu WHERE d.id_pesanan=?');
$d->execute([$id]);
$detail = $d->fetchAll();

$judul = 'Pesanan #' . $id;
include __DIR__ . '/includes/header.php';
$bisaBayar = $p['status_pesanan'] === 'belum_bayar' && $p['status_pembayaran'] !== 'berhasil';
?>
<div class="container page">
  <div class="page-head"><div><h2>Pesanan #<?= $id ?></h2><span class="muted"><?= date('d M Y H:i', strtotime($p['tanggal_pesan'])) ?></span></div>
    <div><?= badge_status($p['status_pesanan']) ?> <?= badge_status($p['status_pembayaran'] ?? 'pending') ?></div></div>
  <div class="two-col">
    <div>
      <div class="table-wrap" style="margin-bottom:1.5rem"><table>
        <thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead><tbody>
        <?php foreach ($detail as $x): ?>
        <tr><td><div class="cell-prod"><div class="thumb"><?php if ($x['foto']): ?><img src="<?= e(foto_url($x['foto'])) ?>" alt=""><?php else: echo shoe_svg(); endif; ?></div>
          <div><strong><?= e($x['nama_sepatu']) ?></strong><br><span class="muted"><?= e($x['ukuran']) ?> &middot; <?= e($x['warna']) ?></span></div></div></td>
          <td><?= rupiah($x['harga_saat_beli']) ?></td><td><?= $x['jumlah'] ?></td><td><?= rupiah($x['harga_saat_beli'] * $x['jumlah']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>

      <div class="form-card" style="margin-bottom:1.5rem">
        <h3>Pengiriman</h3>
        <?php if ($p['metode_pengiriman'] === 'diantar'): ?>
          <p>Diantar ke <strong><?= e($p['label_alamat']) ?></strong>:<br><?= e($p['alamat_lengkap']) ?>, <?= e($p['kota_kabupaten']) ?>, <?= e($p['provinsi']) ?> <?= e($p['kode_pos']) ?></p>
          <p>Kurir: <strong><?= e($p['kurir']) ?></strong> &middot; No. resi: <strong><?= e($p['no_resi'] ?: 'belum tersedia') ?></strong></p>
        <?php else: ?>
          <p>Ambil di tempat: <strong><?= e(LOKASI_AMBIL) ?></strong></p>
          <?php if ($p['status_pesanan'] === 'siap_diambil'): ?><div class="alert alert-success">Pesananmu sudah siap diambil.</div><?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($bisaBayar): ?>
      <div class="form-card">
        <h3>Pembayaran (<?= $p['metode_pembayaran'] === 'qris' ? 'QRIS' : 'Transfer bank' ?>)</h3>
        <p>Total yang harus dibayar: <strong><?= rupiah($p['total_bayar']) ?></strong></p>
        <?php if ($p['metode_pembayaran'] === 'qris'): ?>
          <?php if (file_exists(__DIR__ . '/assets/img/qris.png')): ?><img src="<?= url('assets/img/qris.png') ?>" alt="Kode QRIS Abby Collection" style="max-width:260px;border-radius:12px;margin:.5rem 0"><?php else: ?><div class="alert alert-info">Letakkan gambar QRIS toko di <code>assets/img/qris.png</code>.</div><?php endif; ?>
        <?php else: ?>
          <p>Transfer ke: <strong><?= e(REKENING_TOKO) ?></strong></p>
        <?php endif; ?>
        <?php if ($p['status_pembayaran'] === 'gagal'): ?><div class="alert alert-error">Pembayaran sebelumnya ditolak. Unggah bukti yang benar.</div><?php endif; ?>
        <?php if ($p['bukti_transfer']): ?><p class="hint">Bukti terakhir dikirim <?= date('d M Y H:i', strtotime($p['tanggal_bayar'])) ?>. Menunggu pemeriksaan admin.</p><?php endif; ?>
        <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="aksi" value="bukti">
          <div class="field"><label for="bukti">Unggah bukti pembayaran</label><input type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,image/webp" required><span class="hint">JPG, PNG, atau WEBP, maks 2 MB.</span></div>
          <button class="btn">Kirim bukti</button></form>
      </div>
      <?php endif; ?>
    </div>

    <aside class="summary">
      <h3>Ringkasan</h3>
      <dl><dt>Subtotal produk</dt><dd><?= rupiah($p['total_harga_produk']) ?></dd><dt>Ongkos kirim</dt><dd><?= rupiah($p['ongkos_kirim']) ?></dd></dl>
      <dl class="total"><dt>Total bayar</dt><dd><?= rupiah($p['total_bayar']) ?></dd></dl>
      <?php if (in_array($p['status_pesanan'], ['dikirim', 'siap_diambil'], true)): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="terima"><button class="btn btn-block">Pesanan sudah diterima</button></form>
      <?php endif; ?>
      <?php if ($p['status_pesanan'] === 'belum_bayar'): ?>
        <form method="post" data-confirm="Batalkan pesanan ini?" style="margin-top:.6rem"><?= csrf_field() ?><input type="hidden" name="aksi" value="batal"><button class="btn btn-ghost btn-block">Batalkan pesanan</button></form>
      <?php endif; ?>
    </aside>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
