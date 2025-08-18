# 🔧 Manual Fix untuk Error Donatur

## ❌ Masalah
Error: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'd.catatan' in 'group statement'`

## 🎯 Penyebab
Tabel `donatur` tidak memiliki kolom `catatan` yang direferensikan dalam kode PHP.

## ✅ Solusi

### Opsi 1: Menggunakan Migration Script (Recommended)
1. Buka browser dan akses: `http://localhost/fundraising_php/database_migration.php`
2. Klik tombol "🚀 Run Migration - Add Catatan Column"
3. Tunggu hingga selesai
4. Coba akses halaman donatur lagi

### Opsi 2: Manual SQL Command
Jalankan perintah SQL berikut di database MySQL:

```sql
-- Tambahkan kolom catatan ke tabel donatur
ALTER TABLE donatur ADD COLUMN catatan TEXT NULL AFTER kategori;

-- Update existing records (opsional)
UPDATE donatur SET catatan = '' WHERE catatan IS NULL;
```

### Opsi 3: Menggunakan phpMyAdmin
1. Buka phpMyAdmin
2. Pilih database `fundraising_db`
3. Pilih tabel `donatur`
4. Klik tab "Structure"
5. Klik "Add" untuk menambah kolom baru
6. Isi:
   - Name: `catatan`
   - Type: `TEXT`
   - Null: ✅ (Allow NULL)
   - After: `kategori`
7. Klik "Save"

## 🔍 Verifikasi
Setelah menjalankan salah satu solusi di atas:

1. Coba akses halaman donatur sebagai admin
2. Error seharusnya sudah hilang
3. Form tambah/edit donatur seharusnya bisa digunakan dengan field catatan

## 📝 Struktur Tabel Donatur yang Benar

```sql
CREATE TABLE donatur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    hp VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255),
    alamat TEXT NOT NULL,
    kategori ENUM('individu', 'korporasi', 'yayasan', 'organisasi') NOT NULL,
    catatan TEXT NULL,  -- ← Kolom yang ditambahkan
    total_donasi DECIMAL(15,2) DEFAULT 0.00,
    terakhir_donasi TIMESTAMP NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    jumlah_kunjungan INT DEFAULT 0,
    rata_rata_donasi DECIMAL(15,2) DEFAULT 0.00,
    first_donation TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## ⚠️ Catatan Penting
- Backup database sebelum menjalankan migration
- Pastikan tidak ada aplikasi lain yang sedang mengakses database
- Jika masih error, periksa log error di browser console atau server log