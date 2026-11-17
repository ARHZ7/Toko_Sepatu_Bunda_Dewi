<?php
require_once __DIR__ . '/includes/functions.php';
$u = require_login();
if ($u['peran'] !== 'pelanggan') redirect('admin/index.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('keranjang.php');
csrf_check();
$pdo = db();

$ids = array_values(array_unique(array_filter(array_map('intval', $_POST['ids'] ?? []))));
if (!$ids) { flash('error', 'Pilih minimal satu barang untuk di-checkout.'); redirect('keranjang.php'); }

$in = implode(',', array_fill(0, count($ids), '?'));
$st = $pdo->prepare("SELECT k.id_keranjang,k.kuantitas,st.id_stok,st.ukuran,st.warna,st.jumlah_stok,sp.nama_sepatu,sp.harga,sp.foto
    FROM keranjang k JOIN stok_sepatu st ON st.id_stok=k.id_stok JOIN sepatu sp ON sp.id_sepatu=st.id_sepatu
    WHERE k.id_pengguna=? AND k.id_keranjang IN ($in)");
$st->execute(array_merge([$u['id_pengguna']], $ids));
$items = $st->fetchAll();
if (!$items) { flash('error', 'Barang tidak ditemukan di keranjang.'); redirect('keranjang.php'); }

$subtotal = 0;
foreach ($items as $i) $subtotal += $i['harga'] * $i['kuantitas'];

$a = $pdo->prepare('SELECT * FROM alamat WHERE id_pengguna=? ORDER BY id_alamat');
$a->execute([$u['id_pengguna']]);
$alamatList = $a->fetchAll();

$err = '';
if (isset($_POST['proses'])) {
    $kirim = $_POST['metode_pengiriman'] ?? '';
    $bayar = $_POST['metode_pembayaran'] ?? '';
    $idAlamat = null; $kurir = null; $ongkir = 0;

    if (!in_array($kirim, ['ambil_di_tempat', 'diantar'], true)) $err = 'Pilih metode pengiriman.';
    elseif (!in_array($bayar, ['transfer_bank', 'qris'], true)) $err = 'Pilih metode pembayaran.';
    elseif ($kirim === 'diantar') {
        $idAlamat = (int)($_POST['id_alamat'] ?? 0);
        $kurir = $_POST['kurir'] ?? '';
        $cekA = $pdo->prepare('SELECT 1 FROM alamat WHERE id_alamat=? AND id_pengguna=?');
        $cekA->execute([$idAlamat, $u['id_pengguna']]);
        if (!$cekA->fetch()) $err = 'Pilih alamat pengiriman.';
        elseif (!isset(ONGKIR[$kurir])) $err = 'Pilih kurir.';
        else $ongkir = ONGKIR[$kurir];
    }

    if (!$err) {
        try {
            $pdo->beginTransaction();
            $total = 0;
            foreach ($items as &$i) {
                // kunci baris stok agar tidak terjadi oversell
                $k = $pdo->prepare('SELECT s.jumlah_stok, sp.harga FROM stok_sepatu s JOIN sepatu sp ON sp.id_sepatu=s.id_sepatu WHERE s.id_stok=? FOR UPDATE');
                $k->execute([$i['id_stok']]);
                $row = $k->fetch();
                if (!$row || $row['jumlah_stok'] < $i['kuantitas'])
                    throw new RuntimeException('Stok "' . $i['nama_sepatu'] . '" ukuran ' . $i['ukuran'] . ' tidak mencukupi.');
                $i['harga'] = $row['harga'];
                $total += $row['harga'] * $i['kuantitas'];
            }
            unset($i);

            $pdo->prepare('INSERT INTO pesanan (id_pengguna,id_alamat,metode_pengiriman,total_harga_produk,ongkos_kirim,total_bayar,kurir)
                           VALUES (?,?,?,?,?,?,?)')
                ->execute([$u['id_pengguna'], $idAlamat, $kirim, $total, $ongkir, $total + $ongkir, $kurir]);
            $idPesanan = (int)$pdo->lastInsertId();

            $insD = $pdo->prepare('INSERT INTO detail_pesanan (id_pesanan,id_stok,jumlah,harga_saat_beli) VALUES (?,?,?,?)');
            $upS  = $pdo->prepare('UPDATE stok_sepatu SET jumlah_stok = jumlah_stok - ? WHERE id_stok=?');
            foreach ($items as $i) {
                $insD->execute([$idPesanan, $i['id_stok'], $i['kuantitas'], $i['harga']]);
                $upS->execute([$i['kuantitas'], $i['id_stok']]);
            }
            $pdo->prepare('INSERT INTO pembayaran (id_pesanan,metode_pembayaran) VALUES (?,?)')->execute([$idPesanan, $bayar]);
            $del = $pdo->prepare("DELETE FROM keranjang WHERE id_pengguna=? AND id_keranjang IN ($in)");
            $del->execute(array_merge([$u['id_pengguna']], $ids));
            $pdo->commit();

            flash('success', 'Pesanan dibuat. Selesaikan pembayaran agar pesanan diproses.');
            redirect('pesanan_detail.php?id=' . $idPesanan);
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $ex->getMessage();
        }
    }
}

$judul = 'Checkout';
include __DIR__ . '/includes/header.php';
$selK = $_POST['metode_pengiriman'] ?? 'diantar';
?>
<div class="container page">
  <h2>Checkout</h2>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="two-col">
    <?= csrf_field() ?><input type="hidden" name="proses" value="1">
    <?php foreach ($ids as $id): ?><input type="hidden" name="ids[]" value="<?= $id ?>"><?php endforeach; ?>
    <div>
      <div class="form-card" style="margin-bottom:1.5rem">
        <h3>1. Metode pengiriman</h3>
        <label class="check"><input type="radio" name="metode_pengiriman" value="diantar" <?= $selK === 'diantar' ? 'checked' : '' ?>><span><strong>Diantar ke alamat</strong><br><span class="hint">Pilih alamat dan kurir.</span></span></label>
        <label class="check"><input type="radio" name="metode_pengiriman" value="ambil_di_tempat" <?= $selK === 'ambil_di_tempat' ? 'checked' : '' ?>><span><strong>Ambil di tempat</strong><br><span class="hint">Gratis ongkir.</span></span></label>

        <div id="box-alamat">
          <?php if (!$alamatList): ?>
            <div class="alert alert-info">Kamu belum punya alamat. <a href="<?= url('profil.php#alamat') ?>">Tambah alamat di profil</a>, lalu kembali ke keranjang.</div>
          <?php else: ?>
            <div class="field"><label for="id_alamat">Alamat pengiriman</label><select id="id_alamat" name="id_alamat">
              <?php foreach ($alamatList as $al): ?><option value="<?= $al['id_alamat'] ?>" <?= (int)($_POST['id_alamat'] ?? 0) === (int)$al['id_alamat'] ? 'selected' : '' ?>><?= e($al['label_alamat'] . ' - ' . $al['alamat_lengkap'] . ', ' . $al['kota_kabupaten']) ?></option><?php endforeach; ?></select></div>
          <?php endif; ?>
          <div class="field"><label for="kurir">Kurir</label><select id="kurir" name="kurir">
            <?php foreach (ONGKIR as $nm => $hg): ?><option value="<?= e($nm) ?>" data-ongkir="<?= $hg ?>" <?= ($_POST['kurir'] ?? '') === $nm ? 'selected' : '' ?>><?= e($nm) ?> - <?= rupiah($hg) ?></option><?php endforeach; ?></select></div>
        </div>
        <div id="box-ambil" hidden class="alert alert-info">Ambil pesananmu di: <strong><?= e(LOKASI_AMBIL) ?></strong>. Kami beri tahu lewat status pesanan saat sudah siap diambil.</div>
      </div>

      <div class="form-card">
        <h3>2. Metode pembayaran</h3>
        <label class="check"><input type="radio" name="metode_pembayaran" value="transfer_bank" <?= ($_POST['metode_pembayaran'] ?? 'transfer_bank') === 'transfer_bank' ? 'checked' : '' ?>><span><strong>Transfer bank manual</strong><br><span class="hint">Transfer lalu unggah bukti di halaman pesanan.</span></span></label>
        <label class="check"><input type="radio" name="metode_pembayaran" value="qris" <?= ($_POST['metode_pembayaran'] ?? '') === 'qris' ? 'checked' : '' ?>><span><strong>QRIS</strong><br><span class="hint">Scan kode QRIS di halaman pesanan, lalu unggah bukti.</span></span></label>
      </div>
    </div>

    <aside class="summary">
      <h3>Pesanan kamu</h3>
      <?php foreach ($items as $i): ?>
        <p style="margin:.2rem 0"><strong><?= e($i['nama_sepatu']) ?></strong><br><span class="muted"><?= e($i['ukuran']) ?> &middot; <?= e($i['warna']) ?> &times; <?= $i['kuantitas'] ?> = <?= rupiah($i['harga'] * $i['kuantitas']) ?></span></p>
      <?php endforeach; ?>
      <hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">
      <dl><dt>Subtotal produk</dt><dd><?= rupiah($subtotal) ?></dd><dt>Ongkos kirim</dt><dd id="ongkir-view">Rp 0</dd></dl>
      <dl class="total"><dt>Total bayar</dt><dd id="total-view" data-base="<?= $subtotal ?>"><?= rupiah($subtotal) ?></dd></dl>
      <button class="btn btn-block">Buat pesanan</button>
    </aside>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
