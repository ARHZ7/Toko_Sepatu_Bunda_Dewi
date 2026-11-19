<?php
// Konfigurasi aplikasi & database. Sesuaikan dengan server kamu.
define('DB_HOST', 'localhost');
define('DB_NAME', 'abby_collection');
define('DB_USER', 'root');
define('DB_PASS', '');

// Alamat folder proyek dari root server, tanpa slash di akhir.
// Contoh XAMPP: htdocs/abby-collection -> '/abby-collection'. Jika di root domain, isi ''.
define('BASE_URL', 'http://localhost/abby-collection');

define('NAMA_TOKO', 'Abby Collection');
define('REKENING_TOKO', 'BRI 1234-5678-9012 a.n. Abby Collection'); // ganti dengan rekening asli
define('LOKASI_AMBIL', 'Toko Abby Collection, Jl. Contoh No. 1, Makassar'); // ganti dengan alamat toko

// Ongkos kirim flat per kurir (Rp)
const ONGKIR = ['JNE' => 20000, 'J&T' => 22000, 'SiCepat' => 18000];

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa config/database.php dan pastikan database sudah di-import.');
        }
    }
    return $pdo;
}
