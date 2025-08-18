# PEMISAHAN ROLE USER - FUNDRAISING SYSTEM

## 📋 **OVERVIEW**

Sistem fundraising telah dipisahkan dengan jelas antara interface **Admin/Monitor** dan **User (Fundraiser)**. Setiap role memiliki halaman dan fitur yang berbeda sesuai dengan kebutuhan dan hak akses mereka.

## 🔐 **ROLE-BASED ACCESS CONTROL**

### **1. Admin & Monitor**
- **Dashboard**: `dashboard.php` - Dashboard utama dengan semua data
- **Users**: `admin-users.php` - Management semua user
- **Donatur**: `donatur.php` - Management semua donatur
- **Kunjungan**: `kunjungan.php` - Management semua kunjungan
- **Analytics**: `analytics.php` - Laporan dan analisis
- **Settings**: `settings.php` - Konfigurasi sistem
- **Sidebar**: `sidebar-admin.php`

### **2. User (Fundraiser)**
- **Dashboard**: `user-dashboard.php` - Dashboard khusus fundraiser
- **Kunjungan**: `user-kunjungan.php` - Kunjungan pribadi fundraiser
- **Donatur**: `user-donatur.php` - Donatur yang dikunjungi fundraiser
- **Profile**: `user-profile.php` - Edit profil pribadi
- **Sidebar**: `sidebar-user.php`

## 📁 **STRUKTUR FILE BARU**

### **Halaman Khusus User**
```
user-dashboard.php      # Dashboard khusus fundraiser
user-kunjungan.php      # Kunjungan pribadi fundraiser
user-donatur.php        # Donatur yang dikunjungi fundraiser
user-profile.php        # Edit profil pribadi
user-redirect.php       # Redirect untuk user yang akses admin
```

### **Sidebar Terpisah**
```
sidebar-admin.php       # Menu untuk admin & monitor
sidebar-user.php        # Menu untuk user (fundraiser)
```

## 🎯 **FITUR KHUSUS USER (FUNDRAISER)**

### **1. User Dashboard (`user-dashboard.php`)**
- **Target Progress**: Progress target harian dengan visual progress bar
- **Stats Hari Ini**: Kunjungan, donasi berhasil, total donasi hari ini
- **Ringkasan Bulan**: Total kunjungan, donasi, rata-rata per kunjungan
- **Grafik Performa**: Chart 7 hari terakhir
- **Aktivitas Terbaru**: 10 kunjungan terbaru

### **2. User Kunjungan (`user-kunjungan.php`)**
- **Data Terbatas**: Hanya menampilkan kunjungan user sendiri
- **Filter**: Tanggal, status kunjungan
- **CRUD**: Tambah, edit, hapus kunjungan sendiri
- **Export**: Export data kunjungan pribadi
- **Stats**: Kunjungan hari ini, berhasil, total donasi

### **3. User Donatur (`user-donatur.php`)**
- **Data Terbatas**: Hanya donatur yang pernah dikunjungi user
- **Filter**: Search, kategori donatur
- **CRUD**: Tambah, edit donatur
- **Export**: Export data donatur pribadi
- **Stats**: Total donatur, kunjungan, donasi

### **4. User Profile (`user-profile.php`)**
- **Edit Profil**: Nama, email, HP
- **Ubah Password**: Password lama, baru, konfirmasi
- **Performa Stats**: Hari ini, bulan ini, keseluruhan
- **Account Info**: Tanggal bergabung, terakhir aktif

## 🔄 **FLOW SISTEM**

### **Login Flow**
```
1. User login dengan email/password
2. Sistem cek role user
3. Jika role = 'user' → redirect ke user-dashboard.php
4. Jika role = 'admin'/'monitor' → redirect ke dashboard.php
```

### **Access Control**
```
1. User mencoba akses halaman admin
2. Sistem cek role di setiap halaman
3. Jika role = 'user' → redirect ke user-redirect.php
4. user-redirect.php → redirect ke user-dashboard.php dengan pesan
```

## 🛡️ **SECURITY FEATURES**

### **1. Role Validation**
```php
// Setiap halaman user memvalidasi role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit;
}
```

### **2. Data Isolation**
```php
// Query hanya mengambil data user sendiri
$whereConditions = ["k.fundraiser_id = ?"]; // Only user's own data
$params = [$user_id];
```

### **3. CSRF Protection**
- Semua form menggunakan CSRF token
- API calls memerlukan X-CSRF-Token header

## 📊 **PERBEDAAN DATA ACCESS**

### **Admin/Monitor**
- **Users**: Semua user dalam sistem
- **Donatur**: Semua donatur dalam sistem
- **Kunjungan**: Semua kunjungan dalam sistem
- **Analytics**: Data keseluruhan sistem

### **User (Fundraiser)**
- **Users**: Hanya profil sendiri
- **Donatur**: Hanya donatur yang pernah dikunjungi
- **Kunjungan**: Hanya kunjungan sendiri
- **Analytics**: Hanya data pribadi

## 🎨 **UI/UX DIFFERENCES**

### **Admin/Monitor Interface**
- **Sidebar**: Menu lengkap dengan semua fitur
- **Dashboard**: Overview sistem keseluruhan
- **Tables**: Data semua user/donatur/kunjungan
- **Actions**: Full CRUD operations

### **User Interface**
- **Sidebar**: Menu terbatas (Dashboard, Kunjungan, Donatur, Profile)
- **Dashboard**: Focus pada performa pribadi
- **Tables**: Data terbatas pada user sendiri
- **Actions**: CRUD terbatas pada data sendiri

## 🔧 **IMPLEMENTATION DETAILS**

### **1. Database Queries**
```sql
-- Admin: Semua data
SELECT * FROM kunjungan ORDER BY created_at DESC

-- User: Hanya data sendiri
SELECT * FROM kunjungan WHERE fundraiser_id = ? ORDER BY created_at DESC
```

### **2. File Structure**
```
├── dashboard.php           # Admin dashboard
├── user-dashboard.php      # User dashboard
├── kunjungan.php          # Admin kunjungan
├── user-kunjungan.php     # User kunjungan
├── donatur.php            # Admin donatur
├── user-donatur.php       # User donatur
├── user-profile.php       # User profile
├── sidebar-admin.php      # Admin sidebar
├── sidebar-user.php       # User sidebar
└── user-redirect.php      # User redirect
```

### **3. Session Management**
```php
// Session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_name'] = $user['name'];
```

## 🚀 **BENEFITS**

### **1. Security**
- Data isolation per user
- Role-based access control
- CSRF protection

### **2. User Experience**
- Interface yang sesuai dengan kebutuhan
- Data yang relevan untuk setiap role
- Navigation yang jelas

### **3. Performance**
- Query yang lebih efisien
- Loading data yang lebih cepat
- Reduced server load

### **4. Maintainability**
- Code yang terpisah dan terorganisir
- Easy to maintain dan update
- Clear separation of concerns

## 📝 **TESTING SCENARIOS**

### **1. User Login**
- Login dengan role user → redirect ke user-dashboard.php
- Login dengan role admin → redirect ke dashboard.php

### **2. Access Control**
- User akses halaman admin → redirect dengan pesan
- Admin akses halaman user → bisa akses (read-only)

### **3. Data Isolation**
- User hanya lihat data sendiri
- Admin lihat semua data
- Monitor lihat semua data (read-only)

### **4. CRUD Operations**
- User bisa CRUD data sendiri
- Admin bisa CRUD semua data
- Monitor hanya read data

## 🔄 **MIGRATION NOTES**

### **Existing Users**
- User dengan role 'user' akan otomatis diarahkan ke interface baru
- Data existing tetap aman dan terisolasi
- Tidak ada perubahan pada database structure

### **New Features**
- Semua fitur baru tersedia untuk user
- Interface yang lebih user-friendly
- Performance yang lebih baik

## 📞 **SUPPORT**

Jika ada masalah atau pertanyaan tentang pemisahan role ini, silakan hubungi tim development.

---

**⚠️ Note**: Pemisahan role ini memastikan keamanan data dan pengalaman pengguna yang lebih baik sesuai dengan kebutuhan masing-masing role.