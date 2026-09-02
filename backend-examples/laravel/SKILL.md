---
name: laravel-iot-attendance-integration
description: Panduan langkah-demi-langkah bagi AI Agent untuk memasang modul integrasi Mesin Absensi IoT ESP32 (RFID + Barcode/QR) ke dalam aplikasi Laravel 10/11/12/13.
---

# Laravel IoT Attendance Integration Skill

Gunakan skill ini ketika diminta memasang, mengonfigurasi, atau memodifikasi endpoint Mesin Absensi IoT pada aplikasi berbasis Laravel.

---

## 📋 Langkah Kerja (Execution Workflow)

### 1. Salin Berkas Modul ke Proyek Laravel Klien
1. Salin Controller:
   `app/Http/Controllers/IotAttendanceController.php` &rarr; `<project_root>/app/Http/Controllers/`
2. Salin Middleware:
   `app/Http/Middleware/EnsureIotTokenIsValid.php` &rarr; `<project_root>/app/Http/Middleware/`
3. Salin Models:
   `app/Models/AttendanceDevice.php`
   `app/Models/AttendanceScanLog.php`
   `app/Models/Attendance.php`
   `app/Models/AttendanceSetting.php` &rarr; `<project_root>/app/Models/`
4. Salin Konfigurasi:
   `config/iot.php` &rarr; `<project_root>/config/iot.php`
5. Salin Migrasi:
   Salin 5 berkas di `database/migrations/` ke `<project_root>/database/migrations/`.

---

### 2. Daftarkan Rute di `routes/api.php`
Tambahkan blok rute berikut:

```php
use App\Http\Controllers\IotAttendanceController;
use App\Http\Middleware\EnsureIotTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/iot')->group(function () {
    Route::match(['get', 'post'], '/ping', function () {
        return response()->json(['success' => true, 'message' => 'IoT Server Ready', 'server_time' => now()->toIso8601String()]);
    });

    Route::middleware([EnsureIotTokenIsValid::class])->group(function () {
        Route::post('/scan', [IotAttendanceController::class, 'scan'])->name('iot.scan');
        Route::post('/heartbeat', [IotAttendanceController::class, 'heartbeat'])->name('iot.heartbeat');
        Route::post('/config', [IotAttendanceController::class, 'config'])->name('iot.config');
        Route::get('/firmware-check', [IotAttendanceController::class, 'firmwareCheck'])->name('iot.firmware');
    });
});
```

---

### 3. Konfigurasi CSRF & Environment
1. Tambahkan ke `.env`:
   ```env
   IOT_SECRET_TOKEN=tok_rahasia_mesin_anda
   IOT_DUPLICATE_WINDOW_SECONDS=30
   ```
2. Pastikan rute IoT dikecualikan dari CSRF di `bootstrap/app.php` (Laravel 11+) atau `VerifyCsrfToken.php` (Laravel 10).

---

### 4. Jalankan Migrasi & Test
```bash
php artisan migrate
php artisan test --filter=IotAttendanceTest
```
