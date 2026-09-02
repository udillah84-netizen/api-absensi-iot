# 🤖 AGENTS.md - Laravel Integration Guide

Panduan kerja khusus AI Agent saat mengintegrasikan modul **Mesin Absensi IoT ESP32** ke dalam proyek **Laravel (Versi 10, 11, 12, atau 13)**.

---

## 📌 Konteks & Arsitektur Modul

Modul ini menghubungkan hardware fisik ESP32 dengan sistem database Laravel. Komponen yang disediakan:
- **Middleware:** `App\Http\Middleware\EnsureIotTokenIsValid`
- **Controller:** `App\Http\Controllers\IotAttendanceController`
- **Models:** `AttendanceDevice`, `AttendanceScanLog`, `Attendance`, `AttendanceSetting`
- **Config:** `config/iot.php`

---

## 🚨 Aturan Kritis Laravel yang Harus Dipatuhi

1. **CSRF Exemption Wajib Dikonfigurasi**:
   - Pada **Laravel 11 / 12 / 13** (di `bootstrap/app.php`):
     ```php
     ->withMiddleware(function (Middleware $middleware) {
         $middleware->validateCsrfTokens(except: [
             '/api/v1/iot/*',
             '/iot/*',
         ]);
     })
     ```
   - Pada **Laravel 10** (di `app/Http/Middleware/VerifyCsrfToken.php`):
     ```php
     protected $except = [
         'api/v1/iot/*',
         'iot/*',
     ];
     ```
2. **Kesesuaian Model Pengguna (User / Siswa / Pegawai)**:
   - Default controller mencari user melalui model `App\Models\User`.
   - Jika proyek klien memisahkan tabel `Siswa` dan `Pegawai` (misal pada sistem sekolah dengan tabel terpisah), sesuaikan controller untuk mencari ke tabel `Siswa` terlebih dahulu, lalu fallback ke `Pegawai`.
3. **Penyimpanan Log Mentah (Audit Trail)**:
   - Seluruh request scan (baik berhasil maupun gagal) harus dicatat ke tabel `attendance_scan_logs` untuk audit troubleshooting jika ada komplain kartu tidak terbaca.
4. **Respon JSON Ramah LCD 16x2**:
   - Jangan pernah melempar Exception tak tertangkap yang menghasilkan HTML error page (HTTP 500 HTML). Selalu tangkap Exception dalam blok `try...catch` dan kembalikan JSON.

---

## 🛠️ Perintah CLI Utama

```bash
# Menjalankan migrasi tabel IoT
php artisan migrate

# Menjalankan unit & feature tests IoT
php artisan test --filter=IotAttendanceTest

# Memeriksa rute yang terdaftar
php artisan route:list --path=iot

# Membersihkan cache konfigurasi
php artisan optimize:clear
```

---

## 🔍 Checklist Verifikasi AI Agent

- [ ] Kolom `rfid_uid` dan `barcode_uid` sudah ada di tabel user/siswa/pegawai.
- [ ] Middleware `EnsureIotTokenIsValid` sudah terdaftar.
- [ ] Rute `/api/v1/iot/*` sudah bebas dari proteksi CSRF.
- [ ] `.env` memiliki variabel `IOT_SECRET_TOKEN=tok_rahasia_mesin_anda`.
- [ ] Uji coba berhasil dilakukan dengan `php artisan test` atau via `simulator/index.html`.
