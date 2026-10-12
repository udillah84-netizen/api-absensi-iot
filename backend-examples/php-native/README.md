# 🐘 Panduan Integrasi PHP Native / CodeIgniter / WordPress / cPanel

Folder ini ditujukan bagi Anda yang menggunakan:

- PHP Native / Vanilla PHP
- Framework PHP Klasik (CodeIgniter 3/4, Yii, CakePHP)
- WordPress / Custom CMS
- Shared Hosting / cPanel

---

## 📁 Struktur Berkas

```text
backend-examples/php-native/
├── api/
│   ├── scan.php          # Endpoint scan presensi mandiri PDO
│   ├── heartbeat.php     # Endpoint heartbeat & update sinyal WiFi RSSI
│   ├── config.php        # Endpoint konfigurasi nama sekolah & standby text
│   ├── monitor_data.php  # Endpoint live feed untuk layar TV
│   └── device_data.php   # Endpoint telemetri monitoring mesin IoT
├── monitor.php           # Halaman Live Monitor TV Lobi (Plug & Play)
├── devices.php           # Dashboard Monitoring Mesin & Pendaftaran Kartu Siswa/Pegawai
├── config.php            # Kredensial DB, Token Rahasia & Teks Lembaga Default
└── db.php                # Koneksi PDO singleton aman & helper database
```

---

## ⚡ Cara Pemasangan di Shared Hosting / cPanel

### 1. Upload Berkas

Unggah seluruh berkas di folder ini ke folder website Anda (misal di `public_html/iot/` atau `public_html/api/`).

### 2. Atur Database di `config.php`

Buka file `config.php` dan sesuaikan kredensial database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nama_database_anda');
define('DB_USER', 'user_database');
define('DB_PASS', 'password_database');

define('IOT_SECRET_TOKEN', 'tok_rahasia_mesin_anda');
define('NAMA_LEMBAGA', 'SMK NEGERI 1 BOGOR');
define('STANDBY_TEXT', 'SILAKAN ABSEN');
```

### 3. Import Skema Database

Import berkas SQL yang ada di `../../sql/schema_mysql.sql` melalui phpMyAdmin di cPanel.

---

## 🌐 Daftar URL Endpoint & Halaman Web

- **Endpoint Scan:** `https://domain-anda.com/api/scan.php`
- **Endpoint Heartbeat:** `https://domain-anda.com/api/heartbeat.php`
- **Endpoint Config:** `https://domain-anda.com/api/config.php`
- **Dashboard Kontrol & Pendaftaran Kartu:** `https://domain-anda.com/devices.php`
- **Live Monitor TV Lobi:** `https://domain-anda.com/monitor.php`

---

## 💳 Fitur Pendaftaran Kartu Siswa / Pegawai Cepat

1. Tempelkan kartu RFID baru yang belum terdaftar ke mesin ESP32.
2. Mesin akan merespons LCD: `KARTU DITOLAK / Tidak Terdaftar`.
3. Buka halaman `https://domain-anda.com/devices.php` di browser.
4. Klik tab **"Pendaftaran Kartu"**. UID kartu otomatis muncul di antrean.
5. Pilih Siswa atau Karyawan dari daftar, lalu klik **"Simpan"**. Selesai!
