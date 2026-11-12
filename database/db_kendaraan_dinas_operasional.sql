-- ============================================================================
-- Sistem Informasi Pengelolaan Kendaraan Dinas Operasional (SI-KDO BMD)
-- Database Dump: db_kendaraan_dinas_operasional
-- RDBMS: MySQL / MariaDB (InnoDB, utf8mb4)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `db_kendaraan_dinas_operasional`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `db_kendaraan_dinas_operasional`;

-- ----------------------------------------------------------------------------
-- 1. Tabel: perangkat_daerah (PD)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pemakaian_kdo`;
DROP TABLE IF EXISTS `kdo`;
DROP TABLE IF EXISTS `pns`;
DROP TABLE IF EXISTS `perangkat_daerah`;

CREATE TABLE `perangkat_daerah` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_perangkat` varchar(30) DEFAULT NULL,
  `nama_perangkat` varchar(150) NOT NULL,
  `singkatan` varchar(30) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nama_perangkat` (`nama_perangkat`),
  UNIQUE KEY `uk_kode_perangkat` (`kode_perangkat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data Riil Perangkat Daerah (PD)
INSERT INTO `perangkat_daerah` (`id`, `kode_perangkat`, `nama_perangkat`, `singkatan`, `alamat`, `telepon`) VALUES
(1, '1.01.01', 'Dinas Pendidikan', 'DISDIK', 'Jl. Gatot Subroto Kav. 40-41, Gedung Dinas Teknis Lt. 4', '(021) 5254831'),
(2, '1.02.01', 'Dinas Kesehatan', 'DINKES', 'Jl. Kesehatan No. 10, Kompleks Perkantoran Pemda', '(021) 3845825'),
(3, '1.03.01', 'Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)', 'DPUPR', 'Jl. Taman Jatibaru No. 1, Gedung Dinas Teknis', '(021) 3853271'),
(4, '5.02.01', 'Badan Pengelola Keuangan dan Aset Daerah', 'BPKAD', 'Jl. Medan Merdeka Selatan No. 8-9, Blok D Lt. 3', '(021) 3822254'),
(5, '2.15.01', 'Dinas Perhubungan', 'DISHUB', 'Jl. Taman Jatibaru No. 1, Kompleks Dinas Teknis', '(021) 3501349'),
(6, '2.11.01', 'Dinas Lingkungan Hidup', 'DLH', 'Jl. Mandala V No. 67, Cililitan', '(021) 8092744'),
(7, '6.01.01', 'Inspektorat Daerah', 'INSPEKTORAT', 'Jl. Medan Merdeka Selatan No. 8-9, Blok G Lt. 16', '(021) 3822147');

-- ----------------------------------------------------------------------------
-- 2. Tabel: pns (Pegawai Negeri Sipil)
-- ----------------------------------------------------------------------------
CREATE TABLE `pns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perangkat_id` int(11) DEFAULT NULL,
  `nama_pegawai` varchar(250) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `jabatan` varchar(150) NOT NULL,
  `golongan` varchar(20) NOT NULL,
  `no_hp` varchar(25) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nip` (`nip`),
  KEY `idx_pns_perangkat` (`perangkat_id`),
  CONSTRAINT `fk_pns_perangkat` FOREIGN KEY (`perangkat_id`) REFERENCES `perangkat_daerah` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data Riil Pegawai Negeri Sipil (PNS) dengan Format NIP 18 Digit Standar BKN
INSERT INTO `pns` (`id`, `perangkat_id`, `nama_pegawai`, `nip`, `jabatan`, `golongan`, `no_hp`) VALUES
(1, 1, 'Dr. H. Bambang Sutrisno, M.Pd.', '197205141998031004', 'Kepala Dinas Pendidikan', 'IV/c', '081280114401'),
(2, 1, 'Dra. Ratna Dewi Kartika, M.Si.', '197811202003122002', 'Kepala Bidang Sarana dan Prasarana Pendidikan', 'IV/a', '081384229012'),
(3, 1, 'Hendra Kurniawan, S.AP.', '198604182010011009', 'Pengurus Barang Pengguna Dinas Pendidikan', 'III/c', '081290441123'),
(4, 2, 'dr. Hj. Siti Nurhaliza Wahyuni, M.Kes.', '197408092000122001', 'Kepala Dinas Kesehatan', 'IV/c', '081192340011'),
(5, 2, 'apt. R. Agung Prasetyo, S.Farm., M.K.M.', '198302112008011005', 'Kepala Seksi Logistik dan Alat Kesehatan', 'III/d', '081288761209'),
(6, 3, 'Ir. Mochamad Ridwan Hakim, S.T., M.T.', '197109251997031002', 'Kepala Dinas PUPR', 'IV/c', '081110928374'),
(7, 3, 'Dedi Suryadi, S.T., M.Eng.', '198107152006041007', 'Kepala Bidang Bina Marga', 'IV/a', '081319283746'),
(8, 3, 'Yusuf Hidayatullah, A.Md.', '198912032014031003', 'Koordinator Peralatan dan Kendaraan Operasional', 'III/b', '085678912340'),
(9, 4, 'Drs. H. Ahmad Fauzi Nasution, Ak., M.M.', '197303291999031001', 'Kepala Bidang Pengelolaan Aset Daerah (BMD)', 'IV/b', '081211335577'),
(10, 5, 'Adi Nugroho, S.Si.T., M.T.', '198206192005021004', 'Kepala Bidang Pengendalian Operasional Lalu Lintas', 'IV/a', '081399887766'),
(11, 6, 'Lestari Wulandari, S.T., M.Ling.', '198501272009022006', 'Kepala Seksi Pengelolaan Armada Kebersihan', 'III/d', '081277665544'),
(12, 7, 'Muhammad Iqbal Pratama, S.E., M.Ak.', '198409102008121002', 'Auditor Muda Inspektorat Daerah', 'III/d', '081233445566');

-- ----------------------------------------------------------------------------
-- 3. Tabel: kdo (Kendaraan Dinas Operasional)
-- ----------------------------------------------------------------------------
CREATE TABLE `kdo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL,
  `perangkat_id` int(11) NOT NULL,
  `kode_barang` varchar(40) DEFAULT '1.3.2.02.01.01.001',
  `nomor_polisi_kendaraan` varchar(20) NOT NULL,
  `merk_kendaraan` varchar(100) NOT NULL,
  `tipe_kendaraan` varchar(100) NOT NULL,
  `jenis_kendaraan` enum('Roda 4','Roda 2','Roda 6 / Khusus') NOT NULL DEFAULT 'Roda 4',
  `tahun_produksi` year(4) NOT NULL,
  `warna_kendaraan` varchar(50) NOT NULL,
  `nomor_rangka` varchar(50) DEFAULT NULL,
  `nomor_mesin` varchar(50) DEFAULT NULL,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomor_polisi` (`nomor_polisi_kendaraan`),
  KEY `idx_kdo_pegawai` (`pegawai_id`),
  KEY `idx_kdo_perangkat` (`perangkat_id`),
  CONSTRAINT `kdo_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pns` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `kdo_ibfk_2` FOREIGN KEY (`perangkat_id`) REFERENCES `perangkat_daerah` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data Riil Kendaraan Dinas Operasional (KDO)
INSERT INTO `kdo` (`id`, `pegawai_id`, `perangkat_id`, `kode_barang`, `nomor_polisi_kendaraan`, `merk_kendaraan`, `tipe_kendaraan`, `jenis_kendaraan`, `tahun_produksi`, `warna_kendaraan`, `nomor_rangka`, `nomor_mesin`, `kondisi`, `keterangan`) VALUES
(1, 1, 1, '1.3.2.02.01.01.002', 'B 1024 PQN', 'Toyota', 'Kijang Innova Zenix 2.0 G CVT', 'Roda 4', 2023, 'Hitam Metalik', 'MHFAB1BY4P0012841', 'M20A-FKS0892341', 'Baik', 'Kendaraan Dinas Jabatan Kepala Dinas Pendidikan'),
(2, 2, 1, '1.3.2.02.01.01.003', 'B 1482 PQN', 'Toyota', 'Avanza 1.5 G CVT', 'Roda 4', 2022, 'Silver Metalik', 'MHKM5EA3JNK045120', '2NR-VE9834120', 'Baik', 'Kendaraan Operasional Monitoring Sekolah'),
(3, 3, 1, '1.3.2.02.01.04.001', 'B 6315 PQN', 'Honda', 'Vario 160 CBS', 'Roda 2', 2023, 'Hitam Doff', 'MH1KF0119PK210944', 'KF01E-1210944', 'Baik', 'Kendaraan Operasional Kurir & Inventarisasi Aset Sekolah'),
(4, 4, 2, '1.3.2.02.01.01.002', 'B 1108 PQH', 'Toyota', 'Kijang Innova Reborn 2.4 V AT Diesel', 'Roda 4', 2021, 'Hitam Metalik', 'MHFJB8EM5M0093812', '2GD-FTV4819201', 'Baik', 'Kendaraan Dinas Operasional Kepala Dinas Kesehatan'),
(5, 5, 2, '1.3.2.02.01.03.005', 'B 9204 PQH', 'Daihatsu', 'Gran Max Blind Van 1.5 AC', 'Roda 4', 2020, 'Putih', 'MHKV3BA1JLK081239', '3SZ-VE1928374', 'Rusak Ringan', 'Kendaraan Distribusi Vaksin & Obat Puskesmas (AC perlu servis kompresor)'),
(6, 6, 3, '1.3.2.02.01.01.001', 'B 1055 PQP', 'Mitsubishi', 'Pajero Sport Dakar 4x2 2.4L AT', 'Roda 4', 2022, 'Hitam', 'MMBJNKR10NH018492', '4N15-UAT9281', 'Baik', 'Kendaraan Dinas Operasional Lapangan Kepala Dinas PUPR'),
(7, 7, 3, '1.3.2.02.01.03.002', 'B 8419 PQP', 'Toyota', 'Hilux Double Cabin 2.4 V 4x4 AT', 'Roda 4', 2021, 'Abu-abu Metalik', 'MR0KB8CD7M0192834', '2GD-FTV7712390', 'Baik', 'Kendaraan Operasional Tim Teknis Bina Marga & Jembatan'),
(8, 8, 3, '1.3.2.02.01.03.008', 'B 9571 PQP', 'Isuzu', 'Elf NMR 71 T SD Dump Truck', 'Roda 6 / Khusus', 2018, 'Kuning Kombinasi Biru', 'MHCNMR71HJK009218', '4HG1-T882910', 'Rusak Berat', 'Armada Angkut Material Jalan (Menunggu penggantian transmisi & pompa hidrolik di bengkel rekanan)'),
(9, 8, 3, '1.3.2.02.01.04.002', 'B 6890 PQP', 'Honda', 'CRF150L', 'Roda 2', 2022, 'Merah Putih', 'MH1KD1115NK049182', 'KD11E-1049182', 'Baik', 'Motor Trail Inspeksi Jaringan Irigasi & Drainase'),
(10, 9, 4, '1.3.2.02.01.01.003', 'B 1730 PQB', 'Toyota', 'Veloz 1.5 Q CVT', 'Roda 4', 2023, 'Putih Mutiara', 'MHKM5FB4JPK011294', '2NR-VE1129485', 'Baik', 'Kendaraan Operasional Sensus & Rekonsiliasi BMD BPKAD'),
(11, 10, 5, '1.3.2.02.01.02.001', 'B 1922 PQT', 'Suzuki', 'APV Arena GX MT', 'Roda 4', 2019, 'Biru Dishub', 'MHYGDN42VJK401923', 'G15A-ID839201', 'Rusak Ringan', 'Kendaraan Patroli Pengendalian Operasional Lalu Lintas (Jadwal ganti ban & kampas kopling)'),
(12, 12, 7, '1.3.2.02.01.01.003', 'B 1345 PQG', 'Honda', 'BR-V Prestige 1.5 CVT', 'Roda 4', 2023, 'Abu-abu Metalik', 'MHRDG3850PJ102938', 'L15ZF-1029384', 'Baik', 'Kendaraan Operasional Pemeriksaan Reguler Inspektorat Daerah');

-- ----------------------------------------------------------------------------
-- 4. Tabel: pemakaian_kdo (Riwayat & Pencatatan Pemakaian Operasional KDO)
-- ----------------------------------------------------------------------------
CREATE TABLE `pemakaian_kdo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kdo_id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `perangkat_id` int(11) NOT NULL,
  `nomor_surat_tugas` varchar(80) NOT NULL,
  `keperluan` varchar(255) NOT NULL,
  `tujuan` varchar(150) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `km_awal` int(11) DEFAULT 0,
  `km_akhir` int(11) DEFAULT NULL,
  `status_pemakaian` enum('Digunakan','Selesai','Terjadwal') NOT NULL DEFAULT 'Digunakan',
  `kondisi_setelah` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT 'Baik',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pakai_kdo` (`kdo_id`),
  KEY `idx_pakai_pegawai` (`pegawai_id`),
  KEY `idx_pakai_perangkat` (`perangkat_id`),
  CONSTRAINT `fk_pakai_kdo` FOREIGN KEY (`kdo_id`) REFERENCES `kdo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pakai_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pns` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pakai_perangkat` FOREIGN KEY (`perangkat_id`) REFERENCES `perangkat_daerah` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data Riil Pemakaian Kendaraan Dinas Operasional (KDO)
INSERT INTO `pemakaian_kdo` (`id`, `kdo_id`, `pegawai_id`, `perangkat_id`, `nomor_surat_tugas`, `keperluan`, `tujuan`, `tanggal_mulai`, `tanggal_selesai`, `km_awal`, `km_akhir`, `status_pemakaian`, `kondisi_setelah`, `catatan`) VALUES
(1, 2, 2, 1, '800/ST-114/DISDIK/XI/2026', 'Monitoring Rehabilitasi Ruang Kelas SMP Negeri Wilayah Timur', 'SMPN 42, SMPN 118, dan SMPN 195', '2026-11-02', '2026-11-04', 28410, 28565, 'Selesai', 'Baik', 'Perjalanan dinas berjalan lancar, BBM dan tol sesuai SPJ.'),
(2, 7, 7, 3, '094/SPPD-209/PUPR-BM/XI/2026', 'Inspeksi Teknis Pengaspalan Jalan Arteri & Perbaikan Jembatan', 'Ruas Jalan Lingkar Luar & Jembatan Kali Pesanggrahan', '2026-11-10', NULL, 44120, NULL, 'Digunakan', 'Baik', 'Membawa peralatan uji kepadatan aspal (Core Drill).'),
(3, 5, 5, 2, '440/ST-088/DINKES-SDK/XI/2026', 'Distribusi Rantai Dingin Vaksin Imunisasi Rutin Puskesmas Kecamatan', 'Gudang Farmasi Dinkes ke 6 Puskesmas Kecamatan', '2026-11-05', '2026-11-06', 61200, 61340, 'Selesai', 'Rusak Ringan', 'Saat perjalanan pulang hembusan AC kabin melemah, telah dilaporkan ke pengurus barang untuk servis kompresor.'),
(4, 10, 9, 4, '028/ST-BMD/BPKAD/XI/2026', 'Sensus Fisik & Rekonsiliasi Barang Milik Daerah (KIB B) Semester II', 'Kantor Dinas Pendidikan dan Dinas Kesehatan', '2026-11-11', NULL, 15890, NULL, 'Digunakan', 'Baik', 'Pemeriksaan fisik dokumen BPKB, STNK, dan pelabelan barcode BMD.'),
(5, 3, 3, 1, '800/ST-129/DISDIK/XI/2026', 'Verifikasi Berita Acara Serah Terima (BAST) Meubelair Sekolah', 'SDN 01 Pagi dan SDN 07 Petang', '2026-11-12', NULL, 9420, NULL, 'Digunakan', 'Baik', 'Penugasan harian pengurus barang.'),
(6, 12, 12, 7, '700/ST-301/INSP/XI/2026', 'Audit Kepatuhan Pengelolaan Aset dan Kas Bendahara Pengeluaran', 'Kantor Dinas Perhubungan dan Dinas Lingkungan Hidup', '2026-11-14', '2026-11-18', 11200, NULL, 'Terjadwal', 'Baik', 'Penugasan Tim Pemeriksa Reguler Inspektorat.');

SET FOREIGN_KEY_CHECKS = 1;
