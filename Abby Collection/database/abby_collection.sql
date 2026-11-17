-- =====================================================
-- Abby Collection - Database
-- Import lewat phpMyAdmin atau: mysql -u root -p < database/abby_collection.sql
-- =====================================================
CREATE DATABASE IF NOT EXISTS abby_collection CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE abby_collection;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS pembayaran, detail_pesanan, pesanan, keranjang, stok_sepatu, sepatu, kategori, merek, alamat, pengguna;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE pengguna (
  id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  no_hp       VARCHAR(20)  NULL,
  peran       ENUM('admin','pelanggan') NOT NULL DEFAULT 'pelanggan',
  dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE alamat (
  id_alamat      INT AUTO_INCREMENT PRIMARY KEY,
  id_pengguna    INT NOT NULL,
  label_alamat   VARCHAR(50)  NOT NULL,
  alamat_lengkap TEXT         NOT NULL,
  kota_kabupaten VARCHAR(100) NOT NULL,
  provinsi       VARCHAR(100) NOT NULL,
  kode_pos       VARCHAR(10)  NOT NULL,
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE merek (
  id_merek   INT AUTO_INCREMENT PRIMARY KEY,
  nama_merek VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE kategori (
  id_kategori   INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE sepatu (
  id_sepatu   INT AUTO_INCREMENT PRIMARY KEY,
  nama_sepatu VARCHAR(150) NOT NULL,
  deskripsi   TEXT NULL,
  harga       DECIMAL(12,2) NOT NULL,
  foto        VARCHAR(255) NULL,
  id_kategori INT NOT NULL,
  id_merek    INT NOT NULL,
  dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori),
  FOREIGN KEY (id_merek)    REFERENCES merek(id_merek)
) ENGINE=InnoDB;

CREATE TABLE stok_sepatu (
  id_stok     INT AUTO_INCREMENT PRIMARY KEY,
  id_sepatu   INT NOT NULL,
  ukuran      VARCHAR(10) NOT NULL,
  warna       VARCHAR(50) NOT NULL,
  jumlah_stok INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_varian (id_sepatu, ukuran, warna),
  FOREIGN KEY (id_sepatu) REFERENCES sepatu(id_sepatu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE keranjang (
  id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT NOT NULL,
  id_stok      INT NOT NULL,
  kuantitas    INT NOT NULL DEFAULT 1,
  UNIQUE KEY uq_keranjang (id_pengguna, id_stok),
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE,
  FOREIGN KEY (id_stok)     REFERENCES stok_sepatu(id_stok)  ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pesanan (
  id_pesanan         INT AUTO_INCREMENT PRIMARY KEY,
  id_pengguna        INT NOT NULL,
  id_alamat          INT NULL,
  metode_pengiriman  ENUM('ambil_di_tempat','diantar') NOT NULL DEFAULT 'diantar',
  tanggal_pesan      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_harga_produk DECIMAL(12,2) NOT NULL,
  ongkos_kirim       DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_bayar        DECIMAL(12,2) NOT NULL,
  status_pesanan     ENUM('belum_bayar','dikemas','dikirim','siap_diambil','selesai','dibatalkan') NOT NULL DEFAULT 'belum_bayar',
  kurir              VARCHAR(50) NULL,
  no_resi            VARCHAR(80) NULL,
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna),
  FOREIGN KEY (id_alamat)   REFERENCES alamat(id_alamat) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE detail_pesanan (
  id_detail       INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan      INT NOT NULL,
  id_stok         INT NOT NULL,
  jumlah          INT NOT NULL,
  harga_saat_beli DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
  FOREIGN KEY (id_stok)    REFERENCES stok_sepatu(id_stok)
) ENGINE=InnoDB;

CREATE TABLE pembayaran (
  id_pembayaran     INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan        INT NOT NULL,
  metode_pembayaran ENUM('transfer_bank','qris') NOT NULL,
  jumlah_transfer   DECIMAL(12,2) NULL,
  tanggal_bayar     DATETIME NULL,
  bukti_transfer    VARCHAR(255) NULL,
  status_pembayaran ENUM('pending','berhasil','gagal') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================== DATA AWAL =====================
-- Akun demo:  admin@abbycollection.com / admin123   |   pelanggan@contoh.com / pelanggan123
-- (GANTI password admin setelah login pertama kali!)
INSERT INTO pengguna (nama,email,password,no_hp,peran) VALUES
('Admin Abby','admin@abbycollection.com','$2b$10$FkTqif.IfWGYrpUtwNTuZuXhhLvcF/I9FskYg9V9DtCjtDxkk1nFy','081200000000','admin'),
('Pelanggan Contoh','pelanggan@contoh.com','$2b$10$alG40jWhGBU.jn9Wc08zveNhH00CzeFF8kC4Wpmz/hSO4fwnsuKyW','081211112222','pelanggan');

INSERT INTO merek (nama_merek) VALUES ('Nike'),('Adidas'),('Converse'),('Vans'),('Puma');
INSERT INTO kategori (nama_kategori) VALUES ('Sneakers'),('Formal'),('Olahraga'),('Sandal');

INSERT INTO sepatu (nama_sepatu,deskripsi,harga,id_kategori,id_merek) VALUES
('Air Force Low Classic','Sneakers kulit sintetis dengan sol empuk untuk pemakaian harian.',1299000,1,1),
('Ultraboost Run','Sepatu lari dengan bantalan responsif dan upper rajut.',2199000,3,2),
('Chuck 70 Hi','Sneakers kanvas siluet tinggi dengan sol karet tebal.',1199000,1,3),
('Old Skool Black','Sepatu skate klasik dengan garis samping khas.',999000,1,4),
('Derby Pantofel Pria','Sepatu formal kulit untuk kantor dan acara resmi.',849000,2,5);

INSERT INTO stok_sepatu (id_sepatu,ukuran,warna,jumlah_stok) VALUES
(1,'39','Putih',8),(1,'40','Putih',6),(1,'41','Putih',5),(1,'42','Hitam',4),
(2,'40','Abu-abu',5),(2,'41','Abu-abu',7),(2,'42','Biru',3),
(3,'38','Hitam',6),(3,'39','Hitam',6),(3,'40','Krem',2),
(4,'39','Hitam',9),(4,'40','Hitam',9),(4,'41','Hitam',4),
(5,'41','Cokelat',5),(5,'42','Cokelat',5),(5,'43','Hitam',3);
