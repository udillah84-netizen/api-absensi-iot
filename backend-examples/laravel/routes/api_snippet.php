<?php

use App\Http\Controllers\IotAttendanceController;
use App\Http\Middleware\EnsureIotTokenIsValid;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IoT Attendance API Routes Snippet
|--------------------------------------------------------------------------
|
| Salin route berikut ke dalam file `routes/api.php` proyek Laravel Anda.
| (Atau sertakan file ini langsung di AppServiceProvider / bootstrap/app.php)
|
*/

Route::prefix('v1/iot')->group(function () {
    // 1. Endpoint Ping Sederhana (Untuk tes koneksi awal mesin)
    Route::match(['get', 'post'], '/ping', function () {
        return response()->json([
            'success'     => true,
            'message'     => 'IoT Attendance Server is running',
            'server_time' => now()->toIso8601String(),
        ]);
    });

    // 2. Endpoint Terproteksi Token IoT (Scan, Heartbeat, Config, OTA)
    Route::middleware([EnsureIotTokenIsValid::class])->group(function () {
        // Scan Kartu RFID & Barcode
        Route::post('/scan', [IotAttendanceController::class, 'scan'])
            ->name('iot.attendance.scan');

        // Sinyal Heartbeat Rutin
        Route::post('/heartbeat', [IotAttendanceController::class, 'heartbeat'])
            ->name('iot.attendance.heartbeat');

        // Pengambilan Konfigurasi Jam & Mesin
        Route::post('/config', [IotAttendanceController::class, 'config'])
            ->name('iot.attendance.config');

        // Cek Update Firmware OTA
        Route::get('/firmware-check', [IotAttendanceController::class, 'firmwareCheck'])
            ->name('iot.attendance.firmware');
    });

    // 3. Endpoint Live Monitoring Feed (Untuk polling dashboard/TV Lobi)
    Route::get('/monitor/data', [IotAttendanceController::class, 'liveMonitorData'])
        ->name('iot.attendance.monitor.data');
});
