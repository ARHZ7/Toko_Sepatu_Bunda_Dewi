<?php
$judul = 'Dasbor';
include __DIR__ . '/_header.php';
$pdo = db();
$n = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$stat = [
  'Produk' => $n('SELECT COUNT(*) FROM sepatu'),
  'Pelanggan' => $n("SELECT COUNT(*) FROM pengguna WHERE peran='pelanggan'"),
  'Pesanan belum bayar' => $n("SELECT COUNT(*) FROM pesanan WHERE status_pesanan='belum_bayar'"),
  'Pembayaran perlu dicek' => $n("SELECT COUNT(*) FROM pembayaran WHERE status_pembayaran='pending' AND bukti_transfer IS NOT NULL"),
  'Varian stok menipis (<=3)' => $n('SELECT COUNT(*) FROM stok_sepatu WHERE jumlah_stok <= 3'),
];
$omzet = (float)$pdo->query("SELECT COALESCE(SUM(total_bayar),0) FROM pesanan p JOIN pembayaran b ON b.id_pesanan=p.id_pesanan WHERE b.status_pembayaran='berhasil' AND p.status_pesanan<>'dibatalkan'")->fetchColumn();
$terbaru = $pdo->query('SELECT p.*, u.nama FROM pesanan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna ORDER BY p.id_pesanan DESC LIMIT 8')->fetchAll();
?>
<h2>Dasbor</h2>
<div class="stats">
  <?php foreach ($stat as $l => $v): ?><div class="stat"><b><?= $v ?></b><?= e($l) ?></div><?php endforeach; ?>
  <div class="stat"><b><?= rupiah($omzet) ?></b>Pendapatan terkonfirmasi</div>
</div>
<h3>Pesanan terbaru</h3>
<div class="table-wrap"><table>
  <thead><tr><th>No.</th><th>Pelanggan</th><th>Tanggal</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($terbaru as $p): ?>
  <tr><td>#<?= $p['id_pesanan'] ?></td><td><?= e($p['nama']) ?></td><td><?= date('d M Y H:i', strtotime($p['tanggal_pesan'])) ?></td><td><?= rupiah($p['total_bayar']) ?></td><td><?= badge_status($p['status_pesanan']) ?></td>
  <td><a class="btn btn-ghost btn-sm" href="<?= url('admin/pesanan.php?id=' . $p['id_pesanan']) ?>">Buka</a></td></tr>
  <?php endforeach; if (!$terbaru): ?><tr><td colspan="6" class="muted">Belum ada pesanan.</td></tr><?php endif; ?>
  </tbody></table></div>
<?php include __DIR__ . '/_footer.php'; ?>
