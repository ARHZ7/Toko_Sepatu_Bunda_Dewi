<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect('index.php');
$err = []; $old = ['nama' => '', 'email' => '', 'no_hp' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = ['nama' => trim($_POST['nama'] ?? ''), 'email' => strtolower(trim($_POST['email'] ?? '')), 'no_hp' => trim($_POST['no_hp'] ?? '')];
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
    if ($old['nama'] === '') $err[] = 'Nama wajib diisi.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Format email tidak valid.';
    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $old['no_hp'])) $err[] = 'Nomor HP tidak valid.';
    if (strlen($pw) < 8) $err[] = 'Password minimal 8 karakter.';
    if ($pw !== $pw2) $err[] = 'Konfirmasi password tidak sama.';
    if (!$err) {
        $c = db()->prepare('SELECT 1 FROM pengguna WHERE email=?'); $c->execute([$old['email']]);
        if ($c->fetch()) $err[] = 'Email sudah terdaftar.';
    }
    if (!$err) {
        db()->prepare('INSERT INTO pengguna (nama,email,password,no_hp,peran) VALUES (?,?,?,?,"pelanggan")')
            ->execute([$old['nama'], $old['email'], password_hash($pw, PASSWORD_DEFAULT), $old['no_hp']]);
        flash('success', 'Akun berhasil dibuat. Silakan masuk.');
        redirect('login.php');
    }
}
$judul = 'Daftar';
include __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="auth form-card">
  <h2>Buat akun</h2>
  <?php foreach ($err as $m): ?><div class="alert alert-error"><?= e($m) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label for="nama">Nama lengkap</label><input id="nama" name="nama" value="<?= e($old['nama']) ?>" required></div>
    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required></div>
    <div class="field"><label for="no_hp">Nomor HP</label><input id="no_hp" name="no_hp" value="<?= e($old['no_hp']) ?>" required></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" minlength="8" required><span class="hint">Minimal 8 karakter.</span></div>
    <div class="field"><label for="password2">Ulangi password</label><input type="password" id="password2" name="password2" required></div>
    <button class="btn btn-block">Daftar</button>
  </form>
  <p class="muted" style="margin-top:1rem">Sudah punya akun? <a href="<?= url('login.php') ?>">Masuk</a></p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
