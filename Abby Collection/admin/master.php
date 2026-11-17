<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();
// Konfigurasi dua tabel master: nama tabel, kolom id, kolom nama (nilai tetap, bukan dari input)
$cfg = [
  'merek'    => ['tabel' => 'merek',    'id' => 'id_merek',    'nama' => 'nama_merek',    'label' => 'Merek',    'pakai' => 'id_merek'],
  'kategori' => ['tabel' => 'kategori', 'id' => 'id_kategori', 'nama' => 'nama_kategori', 'label' => 'Kategori', 'pakai' => 'id_kategori'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = $cfg[$_POST['t'] ?? ''] ?? null;
    if ($t) {
        $aksi = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0); $nama = trim($_POST['nama'] ?? '');
        try {
            if ($aksi === 'tambah' && $nama !== '') { $pdo->prepare("INSERT INTO {$t['tabel']} ({$t['nama']}) VALUES (?)")->execute([$nama]); flash('success', $t['label'] . ' ditambahkan.'); }
            elseif ($aksi === 'ubah' && $nama !== '') { $pdo->prepare("UPDATE {$t['tabel']} SET {$t['nama']}=? WHERE {$t['id']}=?")->execute([$nama, $id]); flash('success', $t['label'] . ' diperbarui.'); }
            elseif ($aksi === 'hapus') {
                $c = $pdo->prepare("SELECT COUNT(*) FROM sepatu WHERE {$t['pakai']}=?"); $c->execute([$id]);
                if ($c->fetchColumn() > 0) flash('error', $t['label'] . ' masih dipakai produk, tidak bisa dihapus.');
                else { $pdo->prepare("DELETE FROM {$t['tabel']} WHERE {$t['id']}=?")->execute([$id]); flash('success', $t['label'] . ' dihapus.'); }
            } else flash('error', 'Nama tidak boleh kosong.');
        } catch (PDOException $ex) { flash('error', 'Nama sudah ada atau tidak valid.'); }
    }
    redirect('admin/master.php');
}

$judul = 'Merek & kategori';
include __DIR__ . '/_header.php';
?>
<h2>Merek &amp; kategori</h2>
<div class="row" style="align-items:start">
<?php foreach ($cfg as $key => $t):
    $rows = $pdo->query("SELECT * FROM {$t['tabel']} ORDER BY {$t['nama']}")->fetchAll(); ?>
  <section>
    <h3><?= $t['label'] ?></h3>
    <form method="post" class="inline-form" style="margin-bottom:1rem"><?= csrf_field() ?><input type="hidden" name="t" value="<?= $key ?>"><input type="hidden" name="aksi" value="tambah">
      <input name="nama" placeholder="Tambah <?= strtolower($t['label']) ?> baru" aria-label="Nama <?= strtolower($t['label']) ?> baru" required><button class="btn">Tambah</button></form>
    <div class="table-wrap"><table><tbody>
      <?php foreach ($rows as $r): ?>
      <tr><td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="t" value="<?= $key ?>"><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?= $r[$t['id']] ?>">
        <input name="nama" value="<?= e($r[$t['nama']]) ?>" aria-label="Nama" required><button class="btn btn-ghost btn-sm">Simpan</button></form></td>
      <td><form method="post" data-confirm="Hapus <?= strtolower($t['label']) ?> ini?"><?= csrf_field() ?><input type="hidden" name="t" value="<?= $key ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r[$t['id']] ?>"><button class="btn btn-danger btn-sm">Hapus</button></form></td></tr>
      <?php endforeach; if (!$rows): ?><tr><td class="muted">Belum ada data.</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
