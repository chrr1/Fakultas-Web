-- ============================================
-- DATABASE: db_fakultas
-- Admin Dashboard Fakultas - Berita
-- ============================================

CREATE DATABASE IF NOT EXISTS `db_fakultas` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `db_fakultas`;

-- Tabel Admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Berita
CREATE TABLE IF NOT EXISTS `berita` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `konten` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `kategori` enum('Akademik','Penelitian','Kemahasiswaan','Pengumuman','Event','Prestasi','Umum') NOT NULL DEFAULT 'Umum',
  `penulis` varchar(100) NOT NULL,
  `status` enum('Terbit','Draft','Arsip') NOT NULL DEFAULT 'Draft',
  `dilihat` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Admin Default (password: admin123)
INSERT INTO `admin` (`nama`, `username`, `password`) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert Data Contoh Berita
INSERT INTO `berita` (`judul`, `slug`, `konten`, `thumbnail`, `kategori`, `penulis`, `status`, `dilihat`, `created_at`) VALUES
('Fakultas Teknik Raih Akreditasi A dari BAN-PT', 'fakultas-teknik-raih-akreditasi-a', 'Dengan bangga kami umumkan bahwa Fakultas Teknik telah berhasil meraih akreditasi A dari Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT). Pencapaian luar biasa ini merupakan hasil kerja keras seluruh civitas akademika...', NULL, 'Akademik', 'Admin Fakultas', 'Terbit', 1247, '2025-05-15 09:00:00'),
('Mahasiswa FT Juara 1 Olimpiade Nasional Robotika 2025', 'mahasiswa-ft-juara-olimpiade-robotika', 'Tim mahasiswa Fakultas Teknik berhasil meraih Juara 1 dalam ajang Olimpiade Nasional Robotika 2025 yang diselenggarakan di Universitas Indonesia. Tim yang terdiri dari 4 mahasiswa ini berhasil mengalahkan 87 tim peserta dari seluruh Indonesia...', NULL, 'Prestasi', 'Humas Fakultas', 'Terbit', 856, '2025-05-20 10:30:00'),
('Pendaftaran KKN Semester Gasal 2025/2026 Dibuka', 'pendaftaran-kkn-semester-gasal-2025', 'Biro Akademik mengumumkan bahwa pendaftaran Kuliah Kerja Nyata (KKN) untuk Semester Gasal Tahun Akademik 2025/2026 resmi dibuka. Mahasiswa yang telah memenuhi syarat dapat mendaftar melalui portal akademik mulai tanggal 1 Juni 2025...', NULL, 'Pengumuman', 'Biro Akademik', 'Terbit', 523, '2025-05-25 08:00:00'),
('Seminar Internasional AI & Machine Learning di FT', 'seminar-internasional-ai-ml-ft', 'Fakultas Teknik akan menyelenggarakan Seminar Internasional bertema "Artificial Intelligence and Machine Learning in Engineering" pada tanggal 15 Juni 2025. Acara ini menghadirkan pembicara dari MIT, Oxford, dan Universitas Tokyo...', NULL, 'Event', 'Panitia Seminar', 'Terbit', 432, '2025-05-28 14:00:00'),
('Kerjasama Riset Bersama Universitas Tokyo Ditandatangani', 'kerjasama-riset-universitas-tokyo', 'Dekan Fakultas Teknik menandatangani Memorandum of Understanding (MoU) dengan Universitas Tokyo untuk kerjasama penelitian di bidang teknologi material dan energi terbarukan. Kerjasama ini akan berlangsung selama 5 tahun...', NULL, 'Penelitian', 'Humas Fakultas', 'Draft', 0, '2025-05-30 11:00:00'),
('Beasiswa Penelitian S2 dan S3 Tahun 2025', 'beasiswa-penelitian-s2-s3-2025', 'Bagi mahasiswa berprestasi yang ingin melanjutkan studi ke jenjang S2 dan S3, Fakultas Teknik membuka program beasiswa penelitian tahun 2025. Beasiswa mencakup biaya pendidikan penuh, biaya hidup, dan dana penelitian...', NULL, 'Kemahasiswaan', 'Kemahasiswaan', 'Draft', 0, '2025-06-01 07:00:00');
