# 🚀 Panduan Integrasi Laravel (Versi 10 / 11 / 12 / 13)

Folder ini berisi modul backend siap pakai untuk menghubungkan sistem Laravel Anda dengan **Mesin Absensi IoT ESP32 (RFID + Barcode/QR)**.

---

## 📁 Struktur Berkas

```
backend-examples/laravel/
├── app/
│   ├── Http/Controllers/IotAttendanceController.php   # Controller utama API scan, heartbeat & live monitor
│   ├── Http/Middleware/EnsureIotTokenIsValid.php       # Middleware autentikasi token mesin
│   └── Models/
│       ├── AttendanceDevice.php                        # Model data mesin IoT
│       ├── AttendanceScanLog.php                       # Model log riwayat scan mentah
│       ├── Attendance.php                              # Model data presensi harian
│       └── AttendanceSetting.php                       # Model jadwal jam masuk/pulang
├── config/iot.php                                      # Konfigurasi token & aturan sistem
├── database/migrations/                                # Berkas migrasi database lengkap
├── resources/views/iot/live_monitor.blade.php          # Tampilan Live Stream TV Lobi & Dashboard
├── routes/
│   ├── api_snippet.php                                 # Potongan rute API untuk routes/api.php
│   └── web_snippet.php                                 # Potongan rute Web untuk routes/web.php
└── tests/Feature/IotAttendanceTest.php                 # Uji otomatis endpoint IoT
```

---

## ⚡ Langkah Integrasi Cepat (5 Menit)

### 1. Salin Berkas ke Proyek Laravel Anda
- Salin isi folder `app/` ke direktori `app/` proyek Anda.
- Salin `config/iot.php` ke direktori `config/` proyek Anda.
- Salin berkas migrasi di `database/migrations/` ke `database/migrations/` proyek Anda.
- Salin `resources/views/iot/live_monitor.blade.php` ke `resources/views/iot/` proyek Anda.

### 2. Daftarkan Middleware
- **Laravel 11 / 12 / 13 (di `bootstrap/app.php`):**
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->validateCsrfTokens(except: [
          '/api/v1/iot/*',
      ]);
      $middleware->alias([
          'iot.token' => \App\Http\Middleware\EnsureIotTokenIsValid::class,
      ]);
  })
  ```
- **Laravel 10 (di `app/Http/Kernel.php`):**
  ```php
  protected $middlewareAliases = [
      // ...
      'iot.token' => \App\Http\Middleware\EnsureIotTokenIsValid::class,
  ];
  ```

### 3. Tambahkan Rute API & Web
- Salin isi berkas `routes/api_snippet.php` ke dalam `routes/api.php`.
- Salin isi berkas `routes/web_snippet.php` ke dalam `routes/web.php` (untuk halaman Live Monitor).

### 4. Tambahkan Konfigurasi di `.env`
```env
IOT_SECRET_TOKEN=tok_rahasia_mesin_anda
IOT_DUPLICATE_WINDOW_SECONDS=30
IOT_HEARTBEAT_TIMEOUT_MINUTES=5
```

### 5. Jalankan Migrasi Database
```bash
php artisan migrate
```

---

## 📺 Mengakses Live Monitor (TV Lobi & Ruang Guru)

Buka browser Anda dan akses URL:
```
http://localhost:8000/absensi/monitor
```
- Menampilkan data tap secara real-time (auto-poll 3 detik).
- Kartu yang baru discan akan menyala dengan animasi neon hijau.
- Dilengkapi tombol Fullscreen untuk Smart TV dan audio chime synthesizer.

---

## 🧪 Menguji Endpoint
Gunakan perintah `curl` berikut di terminal:

```bash
# Uji Scan Kartu RFID
curl -X POST http://localhost:8000/api/v1/iot/scan \
  -H "Content-Type: application/json" \
  -H "X-Device-Token: tok_rahasia_mesin_anda" \
  -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01", "jenis": "RFID"}'
```
