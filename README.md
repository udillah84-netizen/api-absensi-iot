# 📡 Mesin Absensi IoT ESP32 (Smart Box) - Starter Kit & Integration Guide

> **Pustaka Integrasi Backend & Starter Kit Resmi untuk Mesin Absensi Fisik ESP32 (RFID MIFARE 13.56 MHz + 2D Barcode/QR Code Scanner)**

[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-red.svg)](backend-examples/laravel/)
[![PHP Native](https://img.shields.io/badge/PHP-7.4%20%7C%208.x%20Native-blue.svg)](backend-examples/php-native/)
[![Node.js](https://img.shields.io/badge/Node.js-Express%20v4-green.svg)](backend-examples/nodejs-express/)
[![Python](https://img.shields.io/badge/Python-FastAPI-teal.svg)](backend-examples/python-fastapi/)
[![Golang](https://img.shields.io/badge/Golang-Gin%20Gonic-cyan.svg)](backend-examples/golang-gin/)
[![Hardware Simulator](https://img.shields.io/badge/Hardware%20Simulator-Web%20Audio%20%2B%20LCD-orange.svg)](simulator/index.html)

Selamat datang di repositori resmi **IoT Attendance Integration Kit**. Repositori ini dirancang khusus bagi pengembang software (*software house*, freelancer, IT internal sekolah/kampus/perusahaan) yang membeli unit fisik **Mesin Absensi IoT Smart Box ESP32** dan ingin menghubungkannya langsung ke dalam website atau aplikasi web mereka tanpa perlu membongkar atau merombak arsitektur sistem yang sudah ada.

---

## 🎯 Mengapa Starter Kit Ini Dibuat?

Ketika Anda membeli mesin absensi fisik komersial di pasaran, kendala terbesar pengembang biasanya adalah:
- ❌ SDK sulit dipahami, dokumentasi bahasa asing minim contoh, atau mengharuskan instalasi aplikasi desktop *middleware* Windows yang berat.
- ❌ Format data terkunci (*proprietary protocol*) dan sulit dikirim langsung ke REST API website berbasis cloud / cPanel.

**Dengan Smart Box ESP32 ini, semuanya berubah:**
- ✅ **100% Berbasis REST API JSON Standar**: Menggunakan method `HTTP POST` dan payload `application/json` yang kompatibel dengan seluruh bahasa pemrograman di dunia.
- ✅ **Plug & Play Multi-Framework**: Tersedia modul siap pakai untuk Laravel, PHP Native, Node.js, Python FastAPI, dan Golang.
- ✅ **Web Hardware Simulator Bawaan**: Anda dapat mengembangkan dan menguji backend Anda **sebelum unit fisik mesin tiba di lokasi**.
- ✅ **Layar TV Lobi Real-time (Live Monitor)**: Template monitor presensi langsung yang dapat dibuka di Smart TV gerbang sekolah/lobi kantor.

---

## 🏗️ Arsitektur Alur Komunikasi Sistem

```text
+-----------------------------------------------------------------------------------+
|                           UNIT MESIN SMART BOX ESP32                              |
|  [RFID MIFARE 13.56 MHz]   [Kamera Barcode / QR 2D]   [RTC DS1302 Presisi Tinggi] |
|  [Layar LCD 16x2 I2C]      [Active Buzzer 5V]         [SD Card Offline Buffer]    |
+-----------------------------------------------------------------------------------+
                                         │
                   HTTP POST /api/v1/iot/scan (WiFi / Internet)
                   Header: X-Device-Token: tok_secret_123
                                         │
                                         ▼
+-----------------------------------------------------------------------------------+
|                           SERVER WEBSITE ANDA                                     |
|           (Laravel / PHP Native / Node.js Express / Python / Golang)              |
|                                                                                   |
|  1. Validasi Token Otorisasi Mesin (Middleware)                                   |
|  2. Cari Data Pengguna Berdasarkan UID Kartu / Teks Barcode                       |
|  3. Validasi Anti-Duplicate Tap (Jeda 30 Detik)                                   |
|  4. Tentukan Status Kehadiran (Hadir / Telat / Pulang)                            |
|  5. Simpan ke Database (MySQL / PostgreSQL / SQLite)                              |
|  6. Kembalikan Response JSON Ramah LCD 16x2 (Max 16 Karakter Baris 1)             |
+-----------------------------------------------------------------------------------+
                   │                                             │
                   │ JSON Response                               │ Polling / SSE
                   ▼                                             ▼
+------------------------------------+         +------------------------------------+
|         LAYAR LCD MESIN            |         |       LIVE MONITOR TV LOBI         |
|  Baris 1: Ahmad Fauzi              |         |  (Tampilan Real-time Layar TV      |
|  Baris 2: HADIR - 07:15            |         |   dengan efek animasi glow hijau)  |
+------------------------------------+         +------------------------------------+
```

---

## 📁 Peta Struktur Direktori Repositori

```text
api-absensi-iot/
├── README.md                                 # Panduan induk & overview lengkap
├── skillscan.md                              # Spesifikasi teknis firmware ESP32 v2.0.0+
├── AGENTS.md                                 # Master guide untuk AI Agent (Antigravity/Cursor/Claude)
├── SKILL.md                                  # Skill cheatsheet otomasi integrasi
│
├── sql/                                      # Skema Database Siap Impor
│   ├── schema_mysql.sql                      # Skema MySQL / MariaDB (InnoDB)
│   ├── schema_postgresql.sql                 # Skema PostgreSQL
│   ├── schema_sqlite.sql                     # Skema SQLite
│   └── sample_data.sql                       # Data sampel pengguna, kartu RFID & QR tester
│
├── backend-examples/                         # Template Backend Siap Salin & Pakai
│   ├── laravel/                              # Template Laravel 10 / 11 / 12 / 13
│   │   ├── app/Http/Controllers/             # IotAttendanceController.php (Scan, Heartbeat, Live Monitor)
│   │   ├── app/Http/Middleware/              # EnsureIotTokenIsValid.php
│   │   ├── app/Models/                       # Model Attendance, Device, Log, Setting
│   │   ├── database/migrations/              # Migrasi database lengkap
│   │   ├── resources/views/iot/              # live_monitor.blade.php (TV Kiosk Dashboard)
│   │   └── routes/                           # api_snippet.php & web_snippet.php
│   │
│   ├── php-native/                           # Solusi PHP Native / cPanel / Shared Hosting
│   │   ├── api/scan.php                      # Endpoint scan presensi mandiri PDO
│   │   ├── api/heartbeat.php                 # Endpoint heartbeat status mesin
│   │   ├── api/config.php                    # Endpoint konfigurasi jam kerja
│   │   ├── api/monitor_data.php              # Endpoint data real-time live feed
│   │   ├── monitor.php                       # Halaman Live Monitor TV drop-in
│   │   ├── config.php                        # Kredensial DB & Token Rahasia
│   │   └── db.php                            # Koneksi PDO singleton aman
│   │
│   ├── nodejs-express/                       # Starter Node.js (Express + CORS + Joi)
│   ├── python-fastapi/                       # Starter Python (FastAPI + Pydantic + Uvicorn)
│   └── golang-gin/                           # Starter Golang (Gin Framework + GORM)
│
├── simulator/                                # Web Hardware Simulator Interaktif
│   ├── index.html                            # Visual Chassis Smart Box + LCD 16x2 + Switcher Varian
│   ├── style.css                             # Matrix font & LCD backlight styling
│   └── app.js                                # Synthesizer audio buzzer Web Audio API & request engine
│
├── postman/                                  # Koleksi Postman Siap Impor
│   ├── IoT_Attendance_API.postman_collection.json
│   └── IoT_Attendance_Environment.postman_environment.json
│
└── docs/                                     # Dokumentasi Spesifikasi Mendalam
    ├── API_REFERENCE.md                      # Kontrak lengkap seluruh endpoint REST API
    ├── LIVE_MONITORING.md                    # Panduan Live Stream TV Kiosk di Lobi / Gerbang
    ├── HARDWARE_AND_TROUBLESHOOTING.md       # Arti kode display LCD, LED, & nada buzzer
    └── CLIENT_ORDER_CHECKLIST.md             # Formulir konfigurasi data saat pemesanan mesin
```

---

## ⚡ Langkah Cepat Integrasi (5 Menit)

### Langkah 1: Siapkan Database
Pilih file skema database yang sesuai di folder [`sql/`](sql/):
- **MySQL / MariaDB:** Impor file [`sql/schema_mysql.sql`](sql/schema_mysql.sql)
- **Data Pengujian:** Impor file [`sql/sample_data.sql`](sql/sample_data.sql) untuk mengisi data dummy siswa/pegawai tester.

### Langkah 2: Pilih & Pasang Modul Backend Anda
Buka folder starter kit sesuai framework yang Anda gunakan:

| Framework | Panduan | Waktu Integrasi |
|---|---|:---:|
| **Laravel (10-13)** | Baca [`backend-examples/laravel/README.md`](backend-examples/laravel/README.md) | ~3 Menit |
| **PHP Native / cPanel** | Baca [`backend-examples/php-native/README.md`](backend-examples/php-native/README.md) | ~2 Menit |
| **Node.js (Express)** | Baca [`backend-examples/nodejs-express/README.md`](backend-examples/nodejs-express/README.md) | ~3 Menit |
| **Python (FastAPI)** | Baca [`backend-examples/python-fastapi/README.md`](backend-examples/python-fastapi/README.md) | ~3 Menit |
| **Golang (Gin)** | Baca [`backend-examples/golang-gin/README.md`](backend-examples/golang-gin/README.md) | ~3 Menit |

### Langkah 3: Uji Coba dengan Web Hardware Simulator
1. Buka berkas [`simulator/index.html`](simulator/index.html) langsung di browser Anda.
2. Masukkan **API Endpoint URL** server Anda (contoh: `http://localhost:8000/api/v1/iot/scan`) dan **Device Secret Token** (contoh: `tok_rahasia_mesin_anda`).
3. Pilih varian perangkat (**Combo Dual**, **Hanya RFID**, atau **Hanya Barcode/QR**).
4. Klik salah satu tombol preset tester (misal: *Ahmad Fauzi* `A1B2C3D4`).
5. Perhatikan tampilan layar LCD 16x2 virtual dan dengarkan nada buzzer Web Audio yang dihasilkan secara langsung!

---

## 🔌 Rangkuman Kontrak REST API

### 1. Header Autentikasi Wajib
Setiap request dari unit mesin menyertakan header keamanan berikut:
```http
Content-Type: application/json
X-Device-Token: tok_rahasia_mesin_anda
```

---

### 2. Endpoint Scan Presensi (`POST /api/v1/iot/scan`)

#### Payload Request dari Mesin (HTTP POST):
```json
{
  "code": "A1B2C3D4",
  "device_id": "DEV-GERBANG-01",
  "scanned_at": "2026-08-30T07:15:22+07:00",
  "jenis": "RFID"
}
```

| Parameter | Tipe Data | Keterangan |
|---|---|---|
| `code` | `string` | UID Kartu RFID (Hex) atau data string isi Barcode/QR Code. |
| `device_id` | `string` | Nomor seri / identifier fisik unit mesin. |
| `scanned_at` | `string` | Waktu scan dari RTC mesin (ISO-8601). Digunakan untuk validasi sinkronisasi offline. |
| `jenis` | `string` | `"RFID"` atau `"BARCODE"`. |

#### Format Response Server (Ramah LCD 16x2):

##### 🟢 Skenario 1: Hadir Tepat Waktu (HTTP 200)
```json
{
  "success": true,
  "status": "Hadir",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "07:15",
  "message": "Absensi Masuk Berhasil"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi` (Maks 16 karakter)
* **Layar LCD Baris 2**: `HADIR - 07:15`
* **Audio Buzzer**: 2x Beep Pendek (Sukses)

##### 🟡 Skenario 2: Hadir Terlambat (HTTP 200)
```json
{
  "success": true,
  "status": "Telat",
  "nama": "Budi Santoso",
  "kelas": "XII RPL 1",
  "jam": "07:45",
  "message": "Absen Masuk (Terlambat)"
}
```
* **Layar LCD Baris 1**: `Budi Santoso`
* **Layar LCD Baris 2**: `TELAT - 07:45`

##### 🔵 Skenario 3: Absen Pulang (HTTP 200)
```json
{
  "success": true,
  "status": "Pulang",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "14:15",
  "message": "Absensi Pulang Berhasil"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi`
* **Layar LCD Baris 2**: `PULANG - 14:15`

##### ⚪ Skenario 4: Double Tap Barusan (HTTP 200)
```json
{
  "success": true,
  "status": "Hadir",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "07:15",
  "message": "Sudah Absen Barusan"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi`
* **Layar LCD Baris 2**: `SUDAH ABSEN HARI`

##### 🔴 Skenario 5: Kartu Belum Terdaftar (HTTP 404)
```json
{
  "success": false,
  "status": "Gagal",
  "nama": null,
  "message": "Kartu Tidak Terdaftar"
}
```
* **Layar LCD Baris 1**: `KARTU DITOLAK`
* **Layar LCD Baris 2**: `Tidak Terdaftar`
* **Audio Buzzer**: 3x Beep Cepat (Error)

---

### 3. Endpoint Pendukung Lainnya

| Endpoint | Method | Fungsi |
|---|:---:|---|
| `/api/v1/iot/heartbeat` | `POST` | Mengirim telemetri sinyal WiFi RSSI, versi firmware, total scan, dan sinkronisasi waktu server. |
| `/api/v1/iot/config` | `POST` | Menyuplai nama sekolah lembaga, varian sensor, dan jam operasional saat mesin booting. |
| `/api/v1/iot/monitor/data` | `GET` | Menyuplai data scan absensi hari ini ke dashboard Live Monitor / Layar TV Lobi. |

> 📖 **Pelajari seluruh detail payload & parameter di [Dokumentasi API Reference Lengkap (`docs/API_REFERENCE.md`)](docs/API_REFERENCE.md).**

---

## 📺 Tampilan Layar TV Lobi Real-time (Live Monitor)

Starter kit ini dilengkapi dengan halaman **Live Monitor Real-time** yang dirancang khusus untuk dipasang pada **Smart TV di Lobi Gedung / Gerbang Sekolah**:

1. **Auto-Streaming (3 Detik Polling)**: Browser otomatis menarik data tanpa perlu refresh (`F5`).
2. **Animasi Flash Neon Hijau**: Setiap kali ada yang tap kartu di gerbang, kartu profil mereka akan langsung muncul di urutan teratas dengan efek visual menyala.
3. **Audio Ding-Dong (Chime Synthesizer)**: Opsional bunyi lonceng nada lembut saat data presensi baru masuk.
4. **Mode Layar Penuh (Fullscreen 1-Click)**: Bersih tanpa address bar browser.

- **Akses Laravel**: `http://localhost:8000/absensi/monitor`
- **Akses PHP Native**: `http://localhost/monitor.php`
- 📖 **Panduan lengkap & tips setup TV Lobi**: Baca [`docs/LIVE_MONITORING.md`](docs/LIVE_MONITORING.md).

---

## 🚨 5 Aturan Emas Integrasi IoT (Wajib Dibaca)

1. **Bypass CSRF Token**:
   - Mesin ESP32 adalah perangkat keras *standalone* dan **tidak memiliki cookie sesi web / token CSRF**.
   - Pastikan route `/api/v1/iot/*` dimasukkan ke daftar pengecualian CSRF (misal pada `bootstrap/app.php` Laravel).
2. **Batasan Karakter LCD 16x2**:
   - Field `nama` pada balasan JSON sebaiknya **maksimal 16 karakter**. Potong atau singkat nama belakang (contoh: `"Ahmad Fauzi R."`) agar tampilan di LCD mesin tidak terpotong jelek.
3. **Anti-Duplicate Window (Jeda 30-60 Detik)**:
   - Pengguna sering menempelkan kartu 2 kali tanpa sengaja. Backend wajib memeriksa apakah pengguna yang sama baru saja absen dalam jeda < 30 detik.
4. **Offline Timestamp Sync (`scanned_at`)**:
   - Jika koneksi internet mesin sempat terputus, data scan disimpan di buffer internal dan dikirimkan saat internet pulih. Selalu prioritaskan parameter `scanned_at` dari request daripada `now()`.
5. **Format Response Wajib JSON Murni**:
   - Jangan pernah mengembalikan respon HTML / Debug error page ke mesin! Mesin ESP32 hanya dapat memproses JSON valid dengan header `Content-Type: application/json`.

---

## 📚 Panduan Lengkap & Navigasi Cepat

- 📖 [**Dokumentasi Kontrak REST API Lengkap (`docs/API_REFERENCE.md`)**](docs/API_REFERENCE.md)
- 📺 [**Panduan Live Monitor & TV Kiosk Lobi (`docs/LIVE_MONITORING.md`)**](docs/LIVE_MONITORING.md)
- 🔧 [**Arti Tampilan LCD, LED Indikator & Nada Buzzer (`docs/HARDWARE_AND_TROUBLESHOOTING.md`)**](docs/HARDWARE_AND_TROUBLESHOOTING.md)
- 📝 [**Formulir Pra-Pemesanan Unit Mesin (`docs/CLIENT_ORDER_CHECKLIST.md`)**](docs/CLIENT_ORDER_CHECKLIST.md)
- ⚡ [**Spesifikasi Firmware ESP32 v2.0.0+ (`skillscan.md`)**](skillscan.md)

---

## 💬 Bantuan & Layanan Integrasi
Jika Anda membutuhkan bantuan kustomisasi logika presensi, integrasi dengan database khusus (Oracle, SQL Server), atau pengadaan unit mesin Smart Box ESP32 tambahan, silakan hubungi tim pengembang kami.
