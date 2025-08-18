# Fundraising System

Sistem fundraising lengkap dengan role-based access control, API backend, dan frontend yang responsif.

## 🏗️ Arsitektur

Aplikasi ini menggunakan arsitektur **full-stack** dengan:
- **Backend**: PHP 8+ dengan MySQL database
- **Frontend**: JavaScript vanilla dengan Tailwind CSS
- **API**: RESTful API endpoints untuk semua operasi CRUD
- **Security**: CSRF protection, session management, role-based access control

## 🔐 Role-Based Access Control

### Admin
- Akses penuh ke semua fitur
- CRUD users, donatur, kunjungan, settings
- Management data dummy
- Export dan laporan lengkap

### Monitor (Pipin)
- Read-only access ke semua data
- Export laporan dan data
- Tidak bisa create, edit, atau delete

### User
- Tambah kunjungan baru
- Tambah donatur baru
- Edit profil sendiri
- Lihat data terbatas

## 🚀 Fitur Utama

### 1. Dashboard Real-time
- Statistik kunjungan hari ini
- Progress target fundraising
- Aktivitas terbaru
- Grafik performa

### 2. Management Data Dummy
- **Insert Data Dummy**: Masukkan data sample ke database
- **Delete Data Dummy**: Hapus semua data dummy
- Rate limiting (max 3x per menit)
- Logging untuk audit trail

### 3. CRUD Operations
- **Users**: Management user dengan role
- **Donatur**: Data donatur lengkap
- **Kunjungan**: Tracking kunjungan fundraiser
- **Settings**: Konfigurasi aplikasi

### 4. Security Features
- CSRF token protection
- Session management
- Password hashing (BCRYPT)
- Input validation & sanitization
- Rate limiting untuk operasi sensitif

## 📁 Struktur File

```
fundraising_php/
├── api/                    # API Endpoints
│   ├── users.php          # CRUD Users
│   ├── donatur.php        # CRUD Donatur
│   ├── kunjungan.php      # CRUD Kunjungan
│   ├── settings.php       # CRUD Settings
│   └── dummy.php          # Management Data Dummy
├── js/                    # Frontend JavaScript
│   ├── app.js            # Main application logic
│   ├── data.js           # Dummy data & utilities
│   ├── ui.js             # UI rendering functions
│   ├── utils.js          # Utility functions
│   └── charts.js         # Chart.js integration
├── styles/                # CSS Styles
├── config.php             # Database & security config
├── login.php              # Login page
├── dashboard.php          # Main dashboard
├── database.sql           # Database schema
└── README.md              # Documentation
```

## 🔌 API Endpoints

### Users API (`/api/users.php`)
- `GET /api/users.php` - List semua users
- `GET /api/users.php?id={id}` - Get user by ID
- `POST /api/users.php` - Create user baru (admin only)
- `PUT /api/users.php?id={id}` - Update user
- `DELETE /api/users.php?id={id}` - Delete user (admin only)

### Donatur API (`/api/donatur.php`)
- `GET /api/donatur.php` - List semua donatur
- `GET /api/donatur.php?id={id}` - Get donatur by ID
- `POST /api/donatur.php` - Create donatur baru
- `PUT /api/donatur.php?id={id}` - Update donatur
- `DELETE /api/donatur.php?id={id}` - Delete donatur (admin only)

### Kunjungan API (`/api/kunjungan.php`)
- `GET /api/kunjungan.php` - List semua kunjungan
- `GET /api/kunjungan.php?id={id}` - Get kunjungan by ID
- `POST /api/kunjungan.php` - Create kunjungan baru
- `PUT /api/kunjungan.php?id={id}` - Update kunjungan
- `DELETE /api/kunjungan.php?id={id}` - Delete kunjungan (admin only)

### Settings API (`/api/settings.php`)
- `GET /api/settings.php` - List semua settings
- `GET /api/settings.php?key={key}` - Get setting by key
- `POST /api/settings.php` - Create setting baru (admin only)
- `PUT /api/settings.php?key={key}` - Update setting (admin only)
- `DELETE /api/settings.php?key={key}` - Delete setting (admin only)

### Dummy Data API (`/api/dummy.php`)
- `POST /api/dummy.php` dengan `action: 'insert_all_dummy'` - Insert semua data dummy
- `POST /api/dummy.php` dengan `action: 'delete_all_dummy'` - Delete semua data dummy

## 🛠️ Installation

### 1. Database Setup
```sql
-- Buat database
CREATE DATABASE fundraising_db;
USE fundraising_db;

-- Import schema
SOURCE database.sql;
```

### 2. Configuration
Update `config.php` dengan kredensial database Anda:
```php
$host = 'localhost';
$database = 'fundraising_db';
$username = 'root';
$password = '';
```

### 3. Web Server
Pastikan web server (Apache/Nginx) sudah running dan folder project ada di web root.

### 4. Access
Buka browser dan akses `http://localhost/fundraising_php/`

## 🔑 Default Accounts

### Admin
- **Email**: ahmad.rizki@fundraising.com
- **Password**: password

### Monitor
- **Email**: pipin@fundraising.com
- **Password**: password

### User
- **Email**: siti.nurhaliza@fundraising.com
- **Password**: password

## 🔒 Security Features

### CSRF Protection
- Setiap request POST/PUT/DELETE memerlukan CSRF token
- Token disimpan di session dan dikirim via header `X-CSRF-Token`

### Session Management
- Session regeneration pada login
- Session timeout handling
- Secure session storage

### Input Validation
- Email validation
- Password strength requirements
- SQL injection prevention
- XSS protection

### Rate Limiting
- Login attempts: max 5x per 5 menit
- Dummy operations: max 3x per menit

## 📊 Data Dummy Management

### Insert Data Dummy
Tombol "Insert Data Dummy ke Database" akan memasukkan:
- 7 users dengan role berbeda
- 5 donatur sample
- 5 kunjungan sample
- 9 settings aplikasi

### Delete Data Dummy
Tombol "Hapus Data Dummy dari Database" akan menghapus semua data dummy berdasarkan pattern tertentu.

### Logging
Semua operasi dummy data di-log ke file `dummy_log.txt` untuk audit trail.

## 🚨 Important Notes

1. **Data Dummy**: Hanya untuk keperluan demo/testing
2. **Production**: Ganti password default dan hapus data dummy
3. **Security**: Update kredensial database dan konfigurasi security
4. **Backup**: Backup database secara regular

## 🔧 Troubleshooting

### Common Issues
1. **Database Connection Error**: Cek kredensial di `config.php`
2. **CSRF Token Error**: Pastikan session berjalan dengan baik
3. **Permission Error**: Cek file permissions untuk folder `api/`

### Debug Mode
Enable error reporting di PHP untuk debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Performance

- **Database**: Optimized queries dengan prepared statements
- **Frontend**: Lazy loading dan efficient DOM manipulation
- **API**: JSON responses dengan proper HTTP status codes
- **Caching**: Session-based caching untuk data yang sering diakses

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

This project is licensed under the MIT License.

---

**⚠️ Disclaimer**: Aplikasi ini dibuat untuk keperluan demo dan pembelajaran. Pastikan untuk melakukan security audit sebelum digunakan di production environment.
