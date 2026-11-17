<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

$q      = trim($_GET['q'] ?? '');
$kat    = (int)($_GET['kategori'] ?? 0);
$merek  = (int)($_GET['merek'] ?? 0);
$ukuran = trim($_GET['ukuran'] ?? '');
$warna  = trim($_GET['warna'] ?? '');
$hmin   = $_GET['harga_min'] ?? '';
$hmax   = $_GET['harga_max'] ?? '';
$filtered = $q !== '' || $kat || $merek || $ukuran !== '' || $warna !== '' || $hmin !== '' || $hmax !== '';

$where = []; $p = [];
if ($q !== '')      { $where[] = 's.nama_sepatu LIKE ?'; $p[] = "%$q%"; }
if ($kat)           { $where[] = 's.id_kategori = ?';    $p[] = $kat; }
if ($merek)         { $where[] = 's.id_merek = ?';       $p[] = $merek; }
if ($ukuran !== '') { $where[] = 'EXISTS (SELECT 1 FROM stok_sepatu x WHERE x.id_sepatu=s.id_sepatu AND x.ukuran=? AND x.jumlah_stok>0)'; $p[] = $ukuran; }
if ($warna !== '')  { $where[] = 'EXISTS (SELECT 1 FROM stok_sepatu x WHERE x.id_sepatu=s.id_sepatu AND x.warna=? AND x.jumlah_stok>0)'; $p[] = $warna; }
if (is_numeric($hmin)) { $where[] = 's.harga >= ?'; $p[] = $hmin; }
if (is_numeric($hmax)) { $where[] = 's.harga <= ?'; $p[] = $hmax; }

$sql = 'SELECT s.*, k.nama_kategori, m.nama_merek,
        (SELECT COALESCE(SUM(jumlah_stok),0) FROM stok_sepatu x WHERE x.id_sepatu=s.id_sepatu) AS total_stok
        FROM sepatu s JOIN kategori k ON k.id_kategori=s.id_kategori JOIN merek m ON m.id_merek=s.id_merek'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY s.id_sepatu DESC';
$st = $pdo->prepare($sql); $st->execute($p);
$produk = $st->fetchAll();

$kategoriList = $pdo->query('SELECT * FROM kategori ORDER BY nama_kategori')->fetchAll();
$merekList    = $pdo->query('SELECT * FROM merek ORDER BY nama_merek')->fetchAll();
$ukuranList   = $pdo->query('SELECT DISTINCT ukuran FROM stok_sepatu ORDER BY CAST(ukuran AS UNSIGNED), ukuran')->fetchAll(PDO::FETCH_COLUMN);
$warnaList    = $pdo->query('SELECT DISTINCT warna FROM stok_sepatu ORDER BY warna')->fetchAll(PDO::FETCH_COLUMN);

$judul = 'Katalog';
include __DIR__ . '/includes/header.php';
?>
<?php if (!$filtered): ?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <h1>Sepatu yang pas, dari pagi sampai malam.</h1>
      <p>Sneakers, sepatu formal, dan sepatu olahraga dari merek pilihan. Pilih ukuran dan warna, bayar lewat transfer atau QRIS, lalu ambil di toko atau kami antar.</p>
      <a class="btn" href="#katalog">Lihat katalog</a>
      <?php if (!user()): ?><a class="btn btn-ghost" href="<?= url('register.php') ?>">Buat akun</a><?php endif; ?>
    </div>
    <div class="hero-pills" aria-hidden="true">
      <div class="p1">Sneakers</div><div class="p2">Formal</div><div class="p3">Olahraga</div><div class="p4">Sandal</div><div class="p5">Abby Collection</div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="page" id="katalog" style="padding-top:<?= $filtered ? '2.5rem' : '1rem' ?>">
  <div class="container">
    <h2>Katalog sepatu</h2>
    <form class="filter" method="get" action="<?= url('index.php') ?>#katalog">
      <div class="f-wide"><label for="q">Cari nama sepatu</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="Contoh: Air Force"></div>
      <div><label for="kategori">Kategori</label>
        <select id="kategori" name="kategori"><option value="">Semua</option>
        <?php foreach ($kategoriList as $k): ?><option value="<?= $k['id_kategori'] ?>" <?= $kat == $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select></div>
      <div><label for="merek">Merek</label>
        <select id="merek" name="merek"><option value="">Semua</option>
        <?php foreach ($merekList as $m): ?><option value="<?= $m['id_merek'] ?>" <?= $merek == $m['id_merek'] ? 'selected' : '' ?>><?= e($m['nama_merek']) ?></option><?php endforeach; ?></select></div>
      <div><label for="ukuran">Ukuran</label>
        <select id="ukuran" name="ukuran"><option value="">Semua</option>
        <?php foreach ($ukuranList as $x): ?><option <?= $ukuran === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
      <div><label for="warna">Warna</label>
        <select id="warna" name="warna"><option value="">Semua</option>
        <?php foreach ($warnaList as $x): ?><option <?= $warna === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
      <div><label for="harga_min">Harga min (Rp)</label><input type="number" id="harga_min" name="harga_min" min="0" value="<?= e($hmin) ?>"></div>
      <div><label for="harga_max">Harga maks (Rp)</label><input type="number" id="harga_max" name="harga_max" min="0" value="<?= e($hmax) ?>"></div>
      <div style="display:flex;gap:.4rem"><button class="btn">Terapkan</button><?php if ($filtered): ?><a class="btn btn-ghost" href="<?= url('index.php') ?>#katalog">Reset</a><?php endif; ?></div>
    </form>

    <?php if (!$produk): ?>
      <div class="empty"><h3>Sepatu tidak ditemukan</h3><p class="muted">Coba kata kunci lain atau longgarkan filter.</p></div>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($produk as $s): ?>
      <a class="card" href="<?= url('produk.php?id=' . $s['id_sepatu']) ?>">
        <div class="card-img"><?php if ($s['foto']): ?><img src="<?= e(foto_url($s['foto'])) ?>" alt="<?= e($s['nama_sepatu']) ?>" loading="lazy"><?php else: echo shoe_svg(); endif; ?></div>
        <div class="card-body">
          <span class="card-meta"><?= e($s['nama_merek']) ?> &middot; <?= e($s['nama_kategori']) ?></span>
          <h3><?= e($s['nama_sepatu']) ?></h3>
          <span class="card-price"><?= rupiah($s['harga']) ?></span>
          <span><?= $s['total_stok'] > 0 ? '<span class="tag">Stok tersedia</span>' : '<span class="tag tag-out">Stok habis</span>' ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
