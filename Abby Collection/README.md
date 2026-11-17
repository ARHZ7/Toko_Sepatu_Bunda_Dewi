# Abby Collection

Website toko sepatu: katalog, keranjang, checkout (ambil di tempat / diantar), pembayaran (transfer bank manual / QRIS), dan panel admin.
Dibuat dengan HTML, CSS, JavaScript, PHP (PDO), dan MySQL. Palet warna: `#291C0E` `#6E473B` `#A78D78` `#BEB5A9` `#E1D4C2`.

## Kebutuhan
- PHP 8.1+ (ekstensi `pdo_mysql` dan `fileinfo` aktif)
- MySQL / MariaDB
- XAMPP / Laragon / server Apache apa pun

## Cara menjalankan
1. Salin folder ini ke `htdocs/abby-collection` (XAMPP) atau folder web server kamu.
2. Import `database/abby_collection.sql` lewat phpMyAdmin (database `abby_collection` dibuat otomatis).
3. Sesuaikan `config/database.php`: user/password DB, `BASE_URL`, rekening toko, dan alamat toko.
4. (Opsional) Taruh gambar QRIS toko di `assets/img/qris.png`.
5. Buka `http://localhost/abby-collection/`.

Tanpa Apache, bisa pakai server bawaan PHP: `php -S localhost:8000` dari folder ini, lalu ubah `BASE_URL` menjadi `''`.

## Akun demo
| Peran | Email | Password |
|---|---|---|
| Admin | admin@abbycollection.com | admin123 |
| Pelanggan | pelanggan@contoh.com | pelanggan123 |

Ganti password admin setelah login pertama (menu Profil).

## Struktur
```
config/database.php     koneksi DB + pengaturan toko + ongkir
database/               skema & data awal SQL
includes/               fungsi inti, header, footer
assets/css, js          tampilan & interaksi
assets/uploads/         foto produk & bukti pembayaran
admin/                  panel admin (produk, merek & kategori, stok, pesanan, pengguna)
index.php produk.php    katalog + pencarian/filter, detail produk
keranjang.php checkout.php pesanan.php pesanan_detail.php profil.php
```

## Perbedaan dari ERD awal (mengikuti use case)
- `sepatu.foto`: foto produk.
- `pesanan.metode_pengiriman` (`ambil_di_tempat` / `diantar`); `id_alamat` boleh NULL untuk ambil di tempat.
- Status pesanan ditambah `siap_diambil`.
- `pembayaran.metode_pembayaran` berupa ENUM `transfer_bank` / `qris`.
- Nama kolom di ERD gambar (`tgl_pesanan`, `harga_saat_ini`) mengikuti nama di SQL (`tanggal_pesan`, `harga_saat_beli`).

## Keamanan yang sudah diterapkan
Prepared statement (PDO), `password_hash`, token CSRF, escape output, validasi tipe file upload, nama file acak, transaksi + `FOR UPDATE` saat checkout agar stok tidak oversell.

## Upload ke GitHub
```bash
git init
git add .
git commit -m "Initial commit: Abby Collection"
git branch -M main
git remote add origin https://github.com/USERNAME/abby-collection.git
git push -u origin main
```
