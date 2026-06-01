# 🏛️ Admin Dashboard Fakultas

Dashboard Admin lengkap untuk manajemen Berita Fakultas berbasis PHP & MySQL.

---

## 📁 Struktur File

```
admin-fakultas/
├── config.php              → Konfigurasi database
├── dashboard.php           → Sidebar + layout utama (header)
├── dashboard_footer.php    → Penutup layout (footer)
├── index.php               → Halaman dashboard (statistik & ringkasan)
├── database.sql            → Script SQL database
└── berita/
    ├── index.php           → Daftar berita (dengan filter & pagination)
    ├── tambah.php          → Form tambah berita
    ├── edit.php            → Form edit berita
    ├── detail.php          → Detail/preview berita
    └── uploads/            → Folder upload thumbnail
```

---

## ⚙️ Cara Install

### 1. Persyaratan
- PHP 7.4 atau lebih baru
- MySQL 5.7 / MariaDB 10.3 atau lebih baru
- Web server: Apache / Nginx / XAMPP / WAMP / Laragon

### 2. Setup Database
1. Buka phpMyAdmin atau MySQL client
2. Import file `database.sql`:
   ```sql
   SOURCE /path/to/database.sql;
   ```
   Atau upload melalui phpMyAdmin → Import → pilih `database.sql`

### 3. Konfigurasi
Edit file `config.php` sesuai konfigurasi server Anda:
```php
define('DB_HOST', 'localhost');  // Host database
define('DB_USER', 'root');       // Username database
define('DB_PASS', '');           // Password database
define('DB_NAME', 'db_fakultas');// Nama database
```

### 4. Akses
- Tempatkan folder `admin-fakultas/` di dalam `htdocs/` (XAMPP) atau `www/` (WAMP)
- Akses: `http://localhost/admin-fakultas/`

---

## 🔐 Login Default
| Username | Password |
|----------|----------|
| admin    | admin123 |

> **Segera ganti password** setelah pertama kali login melalui database.

---

## ✨ Fitur

### Dashboard
- Statistik berita (total, terbit, draft, total tayangan)
- Berita terbaru
- Berita terpopuler
- Distribusi per kategori
- Aksi cepat

### Manajemen Berita (CRUD Lengkap)
- ✅ **Create** — Tambah berita baru dengan upload thumbnail
- ✅ **Read** — Daftar berita dengan tabel lengkap + filter + pencarian + pagination
- ✅ **Update** — Edit semua field berita termasuk ganti/hapus thumbnail
- ✅ **Delete** — Hapus berita beserta thumbnail (dengan konfirmasi)
- ✅ **Toggle Status** — Ubah Terbit ↔ Draft langsung dari tabel

### Kolom Tabel Berita
| Kolom | Keterangan |
|-------|-----------|
| Thumbnail | Preview gambar berita |
| Judul Berita | Judul + cuplikan konten |
| Kategori | Badge berwarna per kategori |
| Penulis | Nama penulis/redaksi |
| Tanggal | Tanggal & jam publikasi |
| Status | Terbit / Draft / Arsip (dapat diklik untuk toggle) |
| Dilihat | Jumlah tayangan + bar visual |
| Aksi | Edit, Lihat Detail, Hapus |

### Kategori Tersedia
Akademik · Penelitian · Kemahasiswaan · Pengumuman · Event · Prestasi · Umum

---

## 🎨 Desain
- Font: **Poppins** (Google Fonts)
- Tema: **Putih Modern** dengan sidebar gelap (#0F172A)
- Responsif untuk mobile & tablet
- Komponen UI konsisten di semua halaman

---

## 📌 Catatan Teknis
- Thumbnail disimpan di folder `berita/uploads/`
- Format yang diterima: JPG, PNG, WebP (maks. 3MB)
- Slug otomatis dibuat dari judul berita
- Pagination 10 berita per halaman
- Filter berdasarkan status, kategori, dan kata kunci
