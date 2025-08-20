# 🚀 FUNDRAISING SYSTEM - DEPLOYMENT GUIDE

## 📋 **OVERVIEW**

Panduan lengkap untuk deploy Fundraising System ke hosting. Sistem ini sudah dilengkapi dengan GPS functionality, photo upload, dan data sample yang lengkap.

## 🎯 **SYSTEM FEATURES**

### **✅ Core Features**
- **User Management**: Admin, Monitor, User roles
- **Donatur Management**: CRUD donatur dengan validasi
- **Kunjungan Management**: GPS tracking, photo upload, status tracking
- **Analytics & Reports**: Excel export, performance tracking
- **Settings Management**: Dynamic app settings

### **✅ Advanced Features**
- **GPS Integration**: Real-time location tracking
- **Photo Upload**: Mandatory photo for visits
- **Data Isolation**: User can only see their own data
- **Responsive Design**: Mobile-friendly interface
- **Security**: CSRF protection, input validation

## 📁 **FILES STRUCTURE**

```
📁 Fundraising System/
├── 📄 index.php                    # Main entry point
├── 📄 login.php                    # Login page
├── 📄 setup_hosting.php            # Database setup script
├── 📄 config.php                   # Database configuration (auto-generated)
├── 📄 app_settings.php             # Application settings
├── 📄 database_complete.sql        # Complete database schema
├── 📄 migrate.php                  # CLI migration script
├── 📄 DEPLOYMENT_GUIDE.md          # This guide
├── 📄 GPS_LOCATION_CONSOLIDATION_COMPLETE.md
├── 📄 FINAL_VALIDATION_AND_MIGRATION_COMPLETE.md
│
├── 📁 Pages/
│   ├── 📄 dashboard.php            # Admin/Monitor dashboard
│   ├── 📄 dashboard-user.php       # User dashboard
│   ├── 📄 kunjungan.php            # Admin kunjungan management
│   ├── 📄 kunjungan-user.php       # User kunjungan management
│   ├── 📄 donatur.php              # Admin donatur management
│   ├── 📄 donatur-user.php         # User donatur management
│   ├── 📄 users.php                # User management
│   ├── 📄 profile.php              # User profile
│   ├── 📄 settings.php             # App settings
│   └── 📄 analytics-fixed.php      # Analytics & reports
│
├── 📁 Components/
│   ├── 📄 sidebar-admin.php        # Admin sidebar
│   ├── 📄 sidebar-user.php         # User sidebar
│   └── 📄 layout-header.php        # Header template
│
├── 📁 Assets/
│   ├── 📁 css/
│   │   ├── 📄 main.css             # Main stylesheet
│   │   └── 📄 icon-fixes.css       # Icon fixes
│   ├── 📁 js/
│   │   ├── 📄 app.js               # Main JavaScript
│   │   ├── 📄 ui.js                # UI utilities
│   │   ├── 📄 utils.js             # Utility functions
│   │   ├── 📄 users_api.js         # User API
│   │   ├── 📄 kunjungan_api.js     # Kunjungan API
│   │   ├── 📄 mobile-menu.js       # Mobile menu
│   │   └── 📄 icon-fixes.js        # Icon fixes
│   └── 📁 uploads/
│       └── 📁 kunjungan/           # Photo upload directory
│
└── 📁 Debug/                       # Debug files (can be deleted)
```

## 🚀 **DEPLOYMENT STEPS**

### **Step 1: Prepare Hosting**
1. **Domain & Hosting**: Siapkan domain dan hosting dengan PHP support
2. **Database**: Buat database MySQL/MariaDB
3. **PHP Requirements**: 
   - PHP 7.4+ (recommended 8.0+)
   - MySQL 5.7+ atau MariaDB 10.2+
   - PDO MySQL extension
   - File upload support
   - HTTPS support (untuk GPS)

### **Step 2: Upload Files**
1. **Download Repository**: Clone atau download dari GitHub
2. **Upload Files**: Upload semua file ke root directory hosting
3. **Set Permissions**: 
   ```bash
   chmod 755 uploads/kunjungan/
   chmod 644 *.php
   ```

### **Step 3: Database Setup**
1. **Access Setup Script**: Buka `yourdomain.com/setup_hosting.php`
2. **Fill Database Credentials**:
   - Database Host: `localhost` (atau sesuai hosting)
   - Database Name: `fundraising_db` (atau sesuai keinginan)
   - Database Username: (dari hosting provider)
   - Database Password: (dari hosting provider)
   - Admin Password: `admin123` (atau sesuai keinginan)
3. **Click Setup**: Klik "🚀 Setup Database"
4. **Wait for Completion**: Tunggu proses setup selesai

### **Step 4: Security**
1. **Delete Setup File**: Hapus `setup_hosting.php` setelah setup selesai
2. **Check Permissions**: Pastikan file permissions aman
3. **HTTPS Setup**: Aktifkan HTTPS untuk GPS functionality

### **Step 5: Test Application**
1. **Access Application**: Buka `yourdomain.com`
2. **Login Test**: Coba login dengan credentials default
3. **Feature Test**: Test semua fitur utama

## 🔐 **DEFAULT LOGIN CREDENTIALS**

### **After Setup**
- **Admin**: `admin@example.com` / `admin123`
- **User**: `ahmad.rizki@fundraising.com` / `admin123`
- **Monitor**: `monitor@fundraising.com` / `admin123`

### **Change Default Passwords**
1. Login sebagai admin
2. Go to User Management
3. Edit user dan ganti password
4. Logout dan login dengan password baru

## 📊 **SAMPLE DATA INCLUDED**

### **Users (8 accounts)**
- **1 Admin**: Administrator
- **6 Users**: Fundraisers dengan target 8
- **1 Monitor**: Monitor user

### **Donatur (8 records)**
- **4 Individuals**: Pak Joko, Ibu Siti, Bapak Ahmad, Ibu Kartika
- **4 Organizations**: PT. Maju Bersama, Yayasan Peduli Bangsa, PT. Bumi Sejahtera, Bapak Bambang

### **Kunjungan (12 records)**
- **8 Successful**: Dengan foto dan GPS coordinates
- **2 Follow-up**: Dengan GPS coordinates
- **2 Unsuccessful**: Dengan GPS coordinates
- **Total Value**: Rp 47,800,000

### **GPS Coordinates**
- **Jakarta Pusat**: Sudirman, Thamrin, Menteng
- **Jakarta Selatan**: Gatot Subroto, Rasuna Said, Kuningan, Senayan

## 🔧 **HOSTING REQUIREMENTS**

### **Minimum Requirements**
- **PHP**: 7.4+
- **MySQL**: 5.7+ atau MariaDB 10.2+
- **Storage**: 100MB+ (untuk foto upload)
- **Bandwidth**: 1GB+ per bulan
- **SSL/HTTPS**: Required untuk GPS

### **Recommended Requirements**
- **PHP**: 8.0+
- **MySQL**: 8.0+ atau MariaDB 10.5+
- **Storage**: 500MB+
- **Bandwidth**: 5GB+ per bulan
- **CDN**: Untuk foto upload

### **PHP Extensions Required**
- `pdo_mysql`
- `fileinfo`
- `gd` (untuk image processing)
- `openssl` (untuk security)
- `mbstring` (untuk UTF-8 support)

## 🛠️ **TROUBLESHOOTING**

### **Common Issues**

#### **1. Database Connection Error**
```
Error: Connection failed
```
**Solution:**
- Check database credentials
- Ensure database exists
- Check database user permissions
- Verify MySQL service is running

#### **2. GPS Not Working**
```
GPS functionality requires HTTPS
```
**Solution:**
- Enable SSL/HTTPS on hosting
- Check browser geolocation permissions
- Ensure modern browser is used

#### **3. Photo Upload Error**
```
Failed to upload photo
```
**Solution:**
- Check upload directory permissions (755)
- Verify PHP upload settings
- Check file size limits
- Ensure GD extension is enabled

#### **4. Permission Denied**
```
Permission denied for uploads
```
**Solution:**
```bash
chmod 755 uploads/kunjungan/
chown www-data:www-data uploads/kunjungan/
```

### **Error Logs**
- **PHP Errors**: Check hosting error logs
- **MySQL Errors**: Check database error logs
- **Upload Errors**: Check file permissions

## 📈 **PERFORMANCE OPTIMIZATION**

### **Database Optimization**
- **Indexes**: Already included for GPS and date queries
- **Query Optimization**: Use prepared statements
- **Connection Pooling**: Configure if available

### **File Optimization**
- **Image Compression**: Compress uploaded photos
- **CDN**: Use CDN for static assets
- **Caching**: Enable browser caching

### **Security Optimization**
- **HTTPS**: Always use HTTPS
- **Input Validation**: Already implemented
- **SQL Injection**: Protected with prepared statements
- **XSS Protection**: Output escaping implemented

## 🔄 **BACKUP & MAINTENANCE**

### **Database Backup**
```sql
-- Backup database
mysqldump -u username -p fundraising_db > backup.sql

-- Restore database
mysql -u username -p fundraising_db < backup.sql
```

### **File Backup**
```bash
# Backup uploads directory
tar -czf uploads_backup.tar.gz uploads/

# Backup entire application
tar -czf app_backup.tar.gz --exclude=uploads/ .
```

### **Regular Maintenance**
- **Weekly**: Check error logs
- **Monthly**: Database backup
- **Quarterly**: Update passwords
- **Annually**: Security audit

## 📞 **SUPPORT**

### **Documentation Files**
- `GPS_LOCATION_CONSOLIDATION_COMPLETE.md`: GPS feature documentation
- `FINAL_VALIDATION_AND_MIGRATION_COMPLETE.md`: Complete system documentation
- `DEPLOYMENT_GUIDE.md`: This deployment guide

### **Technical Support**
- **GitHub Issues**: Report bugs and feature requests
- **Documentation**: Check included documentation files
- **Community**: PHP and MySQL communities

## 🎉 **DEPLOYMENT COMPLETE**

### **✅ Success Indicators**
- [ ] Setup script runs without errors
- [ ] Can login with default credentials
- [ ] GPS functionality works (with HTTPS)
- [ ] Photo upload works
- [ ] All pages load correctly
- [ ] Database contains sample data

### **🔒 Security Checklist**
- [ ] `setup_hosting.php` deleted
- [ ] Default passwords changed
- [ ] HTTPS enabled
- [ ] File permissions set correctly
- [ ] Error reporting disabled in production

### **📊 Performance Checklist**
- [ ] Database indexes created
- [ ] Upload directory exists
- [ ] SSL certificate installed
- [ ] CDN configured (optional)
- [ ] Backup strategy implemented

**Status: READY FOR PRODUCTION! 🚀**

Sistem Fundraising siap untuk digunakan di production dengan semua fitur GPS, photo upload, dan data sample yang lengkap!