-- PRAKTIKUM PENGEMBANGAN APLIKASI WEB - MODUL 6
-- Jalankan melalui phpMyAdmin > Import atau MySQL CLI.
CREATE DATABASE IF NOT EXISTS penjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE penjualan;

-- Agar impor dapat diulang untuk proyek demo ini.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS pembayaran;
DROP TABLE IF EXISTS transaksi_detail;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS pelanggan;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS `user`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE supplier (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nama VARCHAR(100) NOT NULL,
 telp VARCHAR(12),
 alamat TEXT
) ENGINE=InnoDB;

CREATE TABLE pelanggan (
 id VARCHAR(20) PRIMARY KEY,
 nama VARCHAR(100) NOT NULL,
 jenis_kelamin ENUM('L','P') NOT NULL,
 telp VARCHAR(12),
 alamat TEXT
) ENGINE=InnoDB;

CREATE TABLE `user` (
 id_user TINYINT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(30) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 nama VARCHAR(50) NOT NULL,
 alamat VARCHAR(150),
 hp VARCHAR(20),
 level TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE barang (
 id INT AUTO_INCREMENT PRIMARY KEY,
 kode_barang VARCHAR(10) NOT NULL UNIQUE,
 nama_barang VARCHAR(100) NOT NULL,
 harga INT NOT NULL,
 stok INT NOT NULL,
 supplier_id INT NOT NULL,
 CONSTRAINT fk_barang_supplier FOREIGN KEY (supplier_id)
   REFERENCES supplier(id) ON DELETE RESTRICT ON UPDATE CASCADE,
 CONSTRAINT chk_harga CHECK (harga >= 0),
 CONSTRAINT chk_stok CHECK (stok >= 0)
) ENGINE=InnoDB;

CREATE TABLE transaksi (
 id INT AUTO_INCREMENT PRIMARY KEY,
 waktu_transaksi DATE NOT NULL,
 keterangan TEXT,
 total INT NOT NULL DEFAULT 0,
 pelanggan_id VARCHAR(20) NOT NULL,
 CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (pelanggan_id)
   REFERENCES pelanggan(id) ON DELETE RESTRICT ON UPDATE CASCADE,
 CONSTRAINT chk_total CHECK (total >= 0)
) ENGINE=InnoDB;

-- PK gabungan transaksi_id + barang_id juga mencegah barang duplikat di transaksi sama.
CREATE TABLE transaksi_detail (
 transaksi_id INT NOT NULL,
 barang_id INT NOT NULL,
 harga INT NOT NULL,
 qty INT NOT NULL,
 PRIMARY KEY(transaksi_id, barang_id),
 CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id)
   REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_detail_barang FOREIGN KEY (barang_id)
   REFERENCES barang(id) ON DELETE RESTRICT ON UPDATE CASCADE,
 CONSTRAINT chk_detail_harga CHECK (harga >= 0),
 CONSTRAINT chk_detail_qty CHECK (qty > 0)
) ENGINE=InnoDB;

CREATE TABLE pembayaran (
 id INT AUTO_INCREMENT PRIMARY KEY,
 waktu_bayar DATETIME NOT NULL,
 total INT NOT NULL,
 metode ENUM('TUNAI','TRANSFER','EDC') NOT NULL,
 transaksi_id INT NOT NULL,
 CONSTRAINT fk_pembayaran_transaksi FOREIGN KEY (transaksi_id)
   REFERENCES transaksi(id) ON DELETE RESTRICT ON UPDATE CASCADE,
 CONSTRAINT chk_pembayaran_total CHECK (total >= 0)
) ENGINE=InnoDB;

-- Data master: minimal 5 setiap tabel master yang dipakai.
INSERT INTO supplier (id,nama,telp,alamat) VALUES
 (1,'PT Sumber Makmur','081234567101','Malang'),
 (2,'CV Niaga Jaya','081234567102','Surabaya'),
 (3,'UD Berkah Abadi','081234567103','Batu'),
 (4,'PT Sentosa Distribusi','081234567104','Kediri'),
 (5,'CV Maju Bersama','081234567105','Pasuruan');

INSERT INTO pelanggan (id,nama,jenis_kelamin,telp,alamat) VALUES
 ('P001','Andi Saputra','L','081111111101','Malang'),
 ('P002','Siti Rahma','P','081111111102','Batu'),
 ('P003','Budi Santoso','L','081111111103','Surabaya'),
 ('P004','Dina Putri','P','081111111104','Kediri'),
 ('P005','Rizky Pratama','L','081111111105','Pasuruan');

-- Data user contoh hanya untuk kelengkapan skema; aplikasi demo tidak memakai autentikasi.
-- Password di bawah merupakan contoh hash untuk akun demo, bukan untuk sistem produksi.
INSERT INTO `user` (id_user,username,password,nama,alamat,hp,level) VALUES
 (1,'admin1','demo-nonlogin','Admin Satu','Malang','081300000001',1),
 (2,'admin2','demo-nonlogin','Admin Dua','Batu','081300000002',1),
 (3,'kasir1','demo-nonlogin','Kasir Satu','Surabaya','081300000003',0),
 (4,'kasir2','demo-nonlogin','Kasir Dua','Kediri','081300000004',0),
 (5,'kasir3','demo-nonlogin','Kasir Tiga','Pasuruan','081300000005',0);

INSERT INTO barang (id,kode_barang,nama_barang,harga,stok,supplier_id) VALUES
 (1,'BRG001','Buku Tulis',5000,100,1),
 (2,'BRG002','Pulpen',3000,200,2),
 (3,'BRG003','Pensil',2500,150,3),
 (4,'BRG004','Penghapus',2000,120,4),
 (5,'BRG005','Penggaris',4000,80,5),
 (6,'BRG006','Spidol',7500,70,1),
 (7,'BRG007','Map Plastik',3500,90,2),
 (8,'BRG008','Stabilo',6500,60,3);

-- Data transaksi: 10 baris (persyaratan minimal).
INSERT INTO transaksi (id,waktu_transaksi,keterangan,total,pelanggan_id) VALUES
 (1,'2026-11-01','Belanja perlengkapan sekolah',0,'P001'),
 (2,'2026-11-01','Pembelian alat tulis',0,'P002'),
 (3,'2026-11-02','Pesanan kantor',0,'P003'),
 (4,'2026-11-02','Belanja rutin',0,'P004'),
 (5,'2026-11-03','Pembelian sekolah',0,'P005'),
 (6,'2026-11-03','Kebutuhan pribadi',0,'P001'),
 (7,'2026-11-04','Belanja administrasi',0,'P002'),
 (8,'2026-11-04','Pesanan tambahan',0,'P003'),
 (9,'2026-11-05','Pembelian alat kantor',0,'P004'),
 (10,'2026-11-05','Belanja perlengkapan belajar',0,'P005');

-- Detail contoh: lebih dari 10 baris, dan tidak ada barang berulang pada transaksi sama.
INSERT INTO transaksi_detail (transaksi_id,barang_id,harga,qty) VALUES
 (1,1,5000,3),(1,2,3000,2),
 (2,3,2500,4),(2,4,2000,2),
 (3,5,4000,5),(3,6,7500,2),
 (4,1,5000,2),(4,7,3500,3),
 (5,2,3000,5),(5,8,6500,1),
 (6,3,2500,2),(6,5,4000,1),
 (7,4,2000,4),(7,6,7500,2),
 (8,7,3500,2),(8,8,6500,3),
 (9,1,5000,4),(9,3,2500,2),
 (10,2,3000,3),(10,5,4000,2);

-- Isi kolom total dari seluruh detail, bukan input manual.
UPDATE transaksi t SET t.total = (
 SELECT COALESCE(SUM(d.harga * d.qty),0)
 FROM transaksi_detail d WHERE d.transaksi_id = t.id
);

-- Contoh pembayaran opsional, sesuai tabel pada diagram.
INSERT INTO pembayaran (waktu_bayar,total,metode,transaksi_id) VALUES
 ('2026-11-01 10:00:00',21000,'TUNAI',1),
 ('2026-11-01 11:30:00',14000,'TRANSFER',2),
 ('2026-11-02 09:20:00',35000,'EDC',3),
 ('2026-11-02 13:00:00',20500,'TUNAI',4),
 ('2026-11-03 08:15:00',21500,'TRANSFER',5),
 ('2026-11-03 14:40:00',9000,'TUNAI',6),
 ('2026-11-04 10:10:00',23000,'EDC',7),
 ('2026-11-04 16:30:00',26500,'TRANSFER',8),
 ('2026-11-05 09:45:00',25000,'TUNAI',9),
 ('2026-11-05 12:05:00',17000,'EDC',10);
