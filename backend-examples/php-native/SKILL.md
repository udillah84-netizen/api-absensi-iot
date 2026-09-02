---
name: php-native-iot-attendance-integration
description: Panduan langkah-demi-langkah bagi AI Agent untuk memasang modul integrasi Mesin Absensi IoT ESP32 pada server PHP Native, CodeIgniter, WordPress, atau cPanel hosting.
---

# PHP Native IoT Attendance Integration Skill

Gunakan skill ini ketika diminta memasang endpoint REST API absensi IoT pada lingkungan PHP murni tanpa framework besar.

---

## 📋 Langkah Kerja (Execution Workflow)

### 1. Unggah Berkas ke Hosting Klien
Salin struktur berkas berikut ke direktori publik website (misal `public_html/api/` atau `public_html/iot/`):
```
api/
├── scan.php        # Endpoint POST /api/scan.php
├── heartbeat.php   # Endpoint POST /api/heartbeat.php
└── config.php      # Endpoint POST /api/config.php
db.php              # Koneksi PDO & helper fungsi
config.php          # Konfigurasi database & secret token
```

---

### 2. Konfigurasi `config.php`
Sesuaikan kredensial database:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_sekolah_anda');
define('DB_USER', 'user_db');
define('DB_PASS', 'pass_db');
define('IOT_SECRET_TOKEN', 'tok_rahasia_mesin_anda');
```

---

### 3. Impor Database melalui phpMyAdmin
Buka phpMyAdmin di cPanel dan impor:
- `sql/schema_mysql.sql`
- `sql/sample_data.sql` (opsional untuk data tester)

---

### 4. Uji Coba Endpoint
Jalankan pengujian via cURL:
```bash
curl -X POST https://domain-anda.com/api/scan.php \
  -H "Content-Type: application/json" \
  -H "X-Device-Token: tok_rahasia_mesin_anda" \
  -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
```
