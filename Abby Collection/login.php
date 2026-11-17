<?php
require_once __DIR__ . '/includes/functions.php';
if ($u = user()) redirect($u['peran'] === 'admin' ? 'admin/index.php' : 'index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $s = db()->prepare('SELECT * FROM pengguna WHERE email=?'); $s->execute([$email]);
    $row = $s->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $row['id_pengguna'];
        flash('success', 'Selamat datang, ' . $row['nama'] . '.');
        redirect($row['peran'] === 'admin' ? 'admin/index.php' : 'index.php');
    }
    $err = 'Email atau password salah.';
}
$judul = 'Masuk';
include __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="auth form-card">
  <h2>Masuk</h2>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required autofocus></div>
    <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
    <button class="btn btn-block">Masuk</button>
  </form>
  <p class="muted" style="margin-top:1rem">Belum punya akun? <a href="<?= url('register.php') ?>">Daftar</a></p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
