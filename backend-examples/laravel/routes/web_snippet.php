<?php

use App\Http\Controllers\IotAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IoT Attendance Web Monitor Routes Snippet
|--------------------------------------------------------------------------
|
| Salin route berikut ke dalam file `routes/web.php` proyek Laravel Anda
| untuk menampilkan dashboard Live Monitoring pada layar TV Lobi / Browser.
|
*/

Route::get('/absensi/monitor', [IotAttendanceController::class, 'liveMonitor'])
    ->name('iot.attendance.monitor.view');
