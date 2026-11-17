<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function show_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) echo '<div class="alert alert-' . e($t) . '" role="status">' . e($m) . '</div>';
    unset($_SESSION['flash']);
}

// ---- CSRF ----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// ---- Auth ----
function user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id_pengguna,nama,email,no_hp,peran FROM pengguna WHERE id_pengguna=?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function require_login(): array {
    $u = user();
    if (!$u) { flash('info', 'Silakan masuk terlebih dahulu.'); redirect('login.php'); }
    return $u;
}
function require_admin(): array {
    $u = user();
    if (!$u || $u['peran'] !== 'admin') { flash('error', 'Halaman khusus admin.'); redirect('login.php'); }
    return $u;
}
function cart_count(): int {
    $u = user();
    if (!$u || $u['peran'] !== 'pelanggan') return 0;
    $s = db()->prepare('SELECT COALESCE(SUM(kuantitas),0) FROM keranjang WHERE id_pengguna=?');
    $s->execute([$u['id_pengguna']]);
    return (int)$s->fetchColumn();
}

// ---- Upload gambar (JPG/PNG/WEBP, maks 2 MB) ----
function upload_gambar(array $file, string $folder): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Ukuran file maksimal 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Format harus JPG, PNG, atau WEBP.');
    $dir = __DIR__ . '/../assets/uploads/' . $folder;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nama = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$nama")) throw new RuntimeException('Gagal menyimpan file.');
    return $nama;
}
function hapus_gambar(?string $nama, string $folder): void {
    if ($nama && preg_match('/^[a-f0-9]+\.(jpg|png|webp)$/', $nama)) @unlink(__DIR__ . "/../assets/uploads/$folder/$nama");
}
function foto_url(?string $foto): ?string { return $foto ? url('assets/uploads/produk/' . $foto) : null; }

// Gambar placeholder jika produk belum punya foto
function shoe_svg(): string {
    return '<svg viewBox="0 0 120 70" aria-hidden="true"><path d="M8 52c0-6 3-9 8-10l18-5 10-14c2-3 6-3 8-1l6 7 18 8c10 3 22 6 26 12 2 3 1 8-3 8H12c-3 0-4-2-4-5z" fill="currentColor"/></svg>';
}

function badge_status(string $s): string {
    $map = ['belum_bayar'=>'Belum bayar','dikemas'=>'Dikemas','dikirim'=>'Dikirim','siap_diambil'=>'Siap diambil','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan',
            'pending'=>'Menunggu','berhasil'=>'Berhasil','gagal'=>'Gagal'];
    return '<span class="badge badge-' . e($s) . '">' . e($map[$s] ?? $s) . '</span>';
}

// Kembalikan stok saat pesanan dibatalkan (panggil di dalam transaksi / setelah status diubah)
function kembalikan_stok(int $idPesanan): void {
    db()->prepare('UPDATE stok_sepatu s JOIN detail_pesanan d ON d.id_stok=s.id_stok
                   SET s.jumlah_stok = s.jumlah_stok + d.jumlah WHERE d.id_pesanan=?')->execute([$idPesanan]);
}
