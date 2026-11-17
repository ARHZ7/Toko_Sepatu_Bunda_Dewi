<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
$st = db()->prepare('SELECT p.*, pb.status_pembayaran, pb.metode_pembayaran FROM pesanan p
    LEFT JOIN pembayaran pb ON pb.id_pesanan=p.id_pesanan
    WHERE p.id_pengguna=? ORDER BY p.id_pesanan DESC');
$st->execute([$u['id_pengguna']]);
$list = $st->fetchAll();
$judul = 'Riwayat pesanan';
include __DIR__ . '/includes/header.php';
?>
<div class="container page">
  <h2>Riwayat pesanan</h2>
  <?php if (!$list): ?>
    <div class="empty"><h3>Belum ada pesanan</h3><a class="btn" href="<?= url('index.php') ?>">Mulai belanja</a></div>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>No. pesanan</th><th>Tanggal</th><th>Total</th><th>Pengiriman</th><th>Pembayaran</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach ($list as $p): ?>
      <tr><td>#<?= $p['id_pesanan'] ?></td><td><?= date('d M Y H:i', strtotime($p['tanggal_pesan'])) ?></td><td><?= rupiah($p['total_bayar']) ?></td>
      <td><?= $p['metode_pengiriman'] === 'diantar' ? 'Diantar' : 'Ambil di tempat' ?></td>
      <td><?= badge_status($p['status_pembayaran'] ?? 'pending') ?></td><td><?= badge_status($p['status_pesanan']) ?></td>
      <td><a class="btn btn-ghost btn-sm" href="<?= url('pesanan_detail.php?id=' . $p['id_pesanan']) ?>">Detail</a></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
