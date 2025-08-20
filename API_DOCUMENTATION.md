# 📚 API Documentation - Fundraising System

## 🚀 Overview

Fundraising System menyediakan RESTful API untuk mengelola data fundraising, kunjungan, donatur, dan integrasi WhatsApp. API ini menggunakan session-based authentication dan mendukung CORS.

## 🔐 Authentication

Semua endpoint memerlukan autentikasi melalui session. Login melalui endpoint `/api/auth.php?action=login` untuk mendapatkan session.

### Login
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
    "username": "admin",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin User",
            "username": "admin",
            "email": "admin@example.com",
            "role": "admin",
            "status": "active"
        },
        "session_id": "abc123..."
    }
}
```

## 📊 API Endpoints

### 🔐 Authentication API

#### Login
- **URL:** `/api/auth.php?action=login`
- **Method:** `POST`
- **Description:** Login user dan mendapatkan session
- **Required Fields:** `username`, `password`

#### Logout
- **URL:** `/api/auth.php?action=logout`
- **Method:** `POST`
- **Description:** Logout user dan clear session

#### Verify User
- **URL:** `/api/auth.php?action=verify`
- **Method:** `GET` atau `POST`
- **Description:** Verifikasi session user

#### Get Profile
- **URL:** `/api/auth.php?action=profile`
- **Method:** `GET`
- **Description:** Ambil data profile user beserta statistik

### 📋 Kunjungan API

#### List Kunjungan
- **URL:** `/api/kunjungan.php`
- **Method:** `GET`
- **Description:** Ambil daftar kunjungan dengan filtering dan pagination
- **Query Parameters:**
  - `page` (optional): Halaman (default: 1)
  - `limit` (optional): Jumlah data per halaman (default: 20, max: 100)
  - `search` (optional): Pencarian berdasarkan nama donatur/fundraiser
  - `status` (optional): Filter berdasarkan status
  - `date_from` (optional): Filter tanggal dari
  - `date_to` (optional): Filter tanggal sampai
  - `fundraiser_id` (optional): Filter berdasarkan fundraiser

**Response:**
```json
{
    "success": true,
    "message": "Kunjungan list retrieved successfully",
    "data": {
        "data": [
            {
                "id": 1,
                "fundraiser_id": 2,
                "donatur_id": 1,
                "status": "berhasil",
                "nominal": 5000000,
                "catatan": "Kunjungan berhasil",
                "foto": "uploads/kunjungan/abc123.jpg",
                "latitude": -6.2088,
                "longitude": 106.8456,
                "location_address": "Jl. Sudirman No. 123",
                "created_at": "2024-01-15 10:30:00",
                "fundraiser_name": "John Doe",
                "donatur_name": "Jane Smith",
                "donatur_hp": "081234567890",
                "donatur_alamat": "Jakarta"
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 20,
            "total": 50,
            "total_pages": 3,
            "has_next": true,
            "has_prev": false
        },
        "filters": {
            "search": "",
            "status": "",
            "date_from": "",
            "date_to": "",
            "fundraiser_id": ""
        }
    }
}
```

#### Get Single Kunjungan
- **URL:** `/api/kunjungan.php?id={id}`
- **Method:** `GET`
- **Description:** Ambil detail kunjungan berdasarkan ID

#### Create Kunjungan
- **URL:** `/api/kunjungan.php`
- **Method:** `POST`
- **Description:** Buat kunjungan baru
- **Required Fields:** `donatur_id`, `status`, `nominal`
- **Optional Fields:** `catatan`, `latitude`, `longitude`, `location_address`, `foto`

**Request:**
```json
{
    "donatur_id": 1,
    "status": "berhasil",
    "nominal": 5000000,
    "catatan": "Kunjungan berhasil",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "location_address": "Jl. Sudirman No. 123"
}
```

#### Update Kunjungan
- **URL:** `/api/kunjungan.php?id={id}`
- **Method:** `PUT`
- **Description:** Update kunjungan
- **Optional Fields:** `status`, `nominal`, `catatan`, `latitude`, `longitude`, `location_address`

#### Delete Kunjungan
- **URL:** `/api/kunjungan.php?id={id}`
- **Method:** `DELETE`
- **Description:** Hapus kunjungan (Admin/Monitor only)

### 📱 WhatsApp API

#### Send Text Message
- **URL:** `/api/whatsapp.php?action=send`
- **Method:** `POST`
- **Description:** Kirim pesan WhatsApp teks
- **Required Fields:** `to`, `message`
- **Optional Fields:** `file`

**Request:**
```json
{
    "to": "6281234567890",
    "message": "Halo, terima kasih atas donasinya!",
    "file": "https://example.com/file.pdf"
}
```

#### Send Template Message
- **URL:** `/api/whatsapp.php?action=send_template`
- **Method:** `POST`
- **Description:** Kirim pesan template WhatsApp
- **Required Fields:** `to`, `template_id`
- **Optional Fields:** `variables`

**Request:**
```json
{
    "to": "6281234567890",
    "template_id": "welcome_donor",
    "variables": {
        "{nama}": "John Doe"
    }
}
```

#### Send Bulk Messages
- **URL:** `/api/whatsapp.php?action=send_bulk`
- **Method:** `POST`
- **Description:** Kirim pesan bulk ke multiple donatur (Admin/Monitor only)
- **Required Fields:** `donor_ids`, `message`

**Request:**
```json
{
    "donor_ids": [1, 2, 3],
    "message": "Halo, terima kasih atas donasinya!"
}
```

#### Get Message Templates
- **URL:** `/api/whatsapp.php?action=templates`
- **Method:** `GET`
- **Description:** Ambil daftar template pesan

#### Get Message History
- **URL:** `/api/whatsapp.php?action=history`
- **Method:** `GET`
- **Description:** Ambil riwayat pesan WhatsApp

## 🔧 WhatsApp Integration

### Konfigurasi API
API WhatsApp menggunakan layanan Saungwa/Wapanels dengan konfigurasi:
- **Base URL:** `https://app.saungwa.com/api`
- **App Key:** `e98095ab-363d-47a4-b3b6-af99d68ef2b8`
- **Auth Key:** `jH7UfjEsjiw86eF7fTjZuQs62ZIwEqtHL4qjCR6mY6sE36fmyT`

### Template Messages
Sistem menyediakan template pesan siap pakai:

1. **Welcome Donor**
   - Template ID: `welcome_donor`
   - Variables: `{nama}`
   - Message: "Halo {nama}, terima kasih telah mendukung program fundraising kami."

2. **Kunjungan Success**
   - Template ID: `kunjungan_success`
   - Variables: `{nama}`, `{nominal}`
   - Message: "Halo {nama}, kunjungan fundraising kami telah berhasil. Terima kasih atas donasi sebesar Rp {nominal}."

3. **Kunjungan Follow Up**
   - Template ID: `kunjungan_followup`
   - Variables: `{nama}`, `{tanggal}`
   - Message: "Halo {nama}, kami akan melakukan follow up kunjungan fundraising pada {tanggal}."

4. **Reminder Target**
   - Template ID: `reminder_target`
   - Variables: `{nama}`, `{target}`
   - Message: "Halo {nama}, target kunjungan hari ini adalah {target}."

## 🛡️ Security Features

### Rate Limiting
- **Auth API:** 10 requests per 5 minutes
- **Kunjungan API:** 100 requests per hour
- **WhatsApp API:** 50 requests per hour

### CORS
API mendukung CORS untuk cross-origin requests:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token
```

### Role-Based Access
- **User:** Hanya bisa akses data sendiri
- **Monitor:** Bisa akses semua data, tidak bisa delete
- **Admin:** Akses penuh ke semua fitur

## 📊 Database Schema

### WhatsApp Messages Table
```sql
CREATE TABLE whatsapp_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    donor_id INT UNSIGNED,
    to_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id VARCHAR(100),
    variables JSON,
    file_url VARCHAR(500),
    success TINYINT(1) DEFAULT 0,
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (donor_id) REFERENCES donatur(id)
);
```

### API Logs Table
```sql
CREATE TABLE api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    user_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 🚀 Usage Examples

### JavaScript/Fetch
```javascript
// Login
const loginResponse = await fetch('/api/auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'admin',
        password: 'password123'
    })
});

// Get kunjungan list
const kunjunganResponse = await fetch('/api/kunjungan.php?page=1&limit=20', {
    method: 'GET',
    credentials: 'same-origin'
});

// Send WhatsApp message
const whatsappResponse = await fetch('/api/whatsapp.php?action=send', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify({
        to: '6281234567890',
        message: 'Test message'
    })
});
```

### cURL
```bash
# Login
curl -X POST http://localhost/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Get kunjungan
curl -X GET "http://localhost/api/kunjungan.php?page=1&limit=20" \
  -H "Cookie: PHPSESSID=your_session_id"

# Send WhatsApp
curl -X POST http://localhost/api/whatsapp.php?action=send \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{"to":"6281234567890","message":"Test message"}'
```

## 📝 Error Handling

API mengembalikan response dengan format konsisten:

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {...},
    "timestamp": "2024-01-15 10:30:00"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "data": null,
    "timestamp": "2024-01-15 10:30:00"
}
```

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `429` - Rate Limit Exceeded
- `500` - Internal Server Error

## 🔄 Web Interface

Sistem juga menyediakan web interface untuk mengelola WhatsApp messages:
- **URL:** `/whatsapp-manager.php`
- **Features:**
  - Send individual messages
  - Send bulk messages
  - View message templates
  - View message history
  - Real-time status updates

## 📞 Support

Untuk bantuan teknis atau pertanyaan tentang API, silakan hubungi tim development.