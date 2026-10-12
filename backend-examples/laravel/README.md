# 🚀 Panduan Integrasi Laravel (Versi 10 / 11 / 12 / 13)

Folder ini berisi modul backend siap pakai untuk menghubungkan sistem Laravel Anda dengan **Mesin Absensi IoT ESP32 (RFID + Barcode/QR)**.

---

## 📁 Struktur Berkas

```text
backend-examples/laravel/
├── app/
│   ├── Http/Controllers/IotAttendanceController.php   # Controller API scan, heartbeat, config & device control
│   ├── Http/Middleware/EnsureIotTokenIsValid.php       # Middleware autentikasi token mesin
│   └── Models/
│       ├── AttendanceDevice.php                        # Model data mesin IoT & sinyal WiFi
│       ├── AttendanceScanLog.php                       # Model log riwayat scan mentah
│       ├── Attendance.php                              # Model data presensi harian
│       ├── AttendanceSetting.php                       # Model jadwal jam masuk/pulang
│       ├── IotSetting.php                              # Model nama sekolah & standby text LCD
│       └── UnregisteredCard.php                        # Model antrean pendaftaran kartu baru
├── config/iot.php                                      # Konfigurasi token & aturan sistem
├── database/migrations/                                # Berkas migrasi database lengkap
├── resources/views/iot/
│   ├── live_monitor.blade.php                          # Tampilan Live Stream TV Lobi Kiosk
│   └── device_monitoring.blade.php                     # Dashboard kontrol mesin & pendaftaran kartu
├── routes/
│   ├── api_snippet.php                                 # Potongan rute API untuk routes/api.php
│   └── web_snippet.php                                 # Potongan rute Web untuk routes/web.php
└── tests/Feature/IotAttendanceTest.php                 # Uji otomatis endpoint IoT
```

---

## ⚡ Langkah Integrasi Cepat (5 Menit)

### 1. Salin Berkas ke Proyek Laravel Anda

- Salin seluruh isi folder `app/` ke direktori `app/` proyek Anda.
- Salin `config/iot.php` ke direktori `config/` proyek Anda.
- Salin berkas migrasi di `database/migrations/` ke `database/migrations/` proyek Anda.
- Salin file-file view di `resources/views/iot/` ke `resources/views/iot/` proyek Anda.

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
- Salin isi berkas `routes/web_snippet.php` ke dalam `routes/web.php`.

### 4. Tambahkan Konfigurasi di `.env`

```env
IOT_SECRET_TOKEN=tok_rahasia_mesin_anda
IOT_INSTITUTION_NAME="SMK NEGERI 1 BOGOR"
IOT_STANDBY_TEXT="SILAKAN ABSEN"
IOT_DUPLICATE_WINDOW_SECONDS=30
IOT_HEARTBEAT_TIMEOUT_MINUTES=5
```

### 5. Jalankan Migrasi Database

```bash
php artisan migrate
```

---

## 🖥️ Mengakses Dashboard Kontrol & Pendaftaran Kartu

Buka browser Anda dan akses URL:

```text
http://localhost:8000/absensi/devices
```

Fitur yang tersedia:

- **Monitoring Status Perangkat**: Menampilkan status online/offline, kekuatan sinyal WiFi RSSI (-50 s/d -90 dBm), dan total scan hari ini.
- **Pendaftaran Kartu Cepat**: Kartu RFID/QR yang belum terdaftar otomatis muncul di antrean. Cukup pilih nama Siswa atau Karyawan lalu klik Simpan.
- **Setting Nama Lembaga & Standby Text**: Ubah nama sekolah pada LCD Baris 1 dan teks ajakan pada Baris 2 tanpa menyentuh kode program.

---

## 📺 Mengakses Live Monitor (TV Lobi & Ruang Guru)

Buka browser Anda dan akses URL:

```text
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
