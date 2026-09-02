# 📺 Panduan Live Monitoring & TV Kiosk Display

Panduan implementasi dan penyiapan **Live Monitor Real-time** untuk menampilkan data tap presensi mesin IoT ESP32 secara instan di layar monitor komputer, ruang guru, ataupun **Smart TV di Lobi Gedung / Gerbang Sekolah**.

---

## 🌟 Fitur Unggulan Live Monitor

1. **Radar Streaming Real-time**:
   - Polling otomatis setiap 3 detik tanpa perlu refresh browser secara manual (`F5`).
2. **Animasi Flash Neon Hijau (New Card Arrival)**:
   - Setiap kali siswa atau pegawai menempelkan kartu RFID / QR di mesin, kartu profil mereka akan langsung muncul di baris paling atas dengan animasi glowing hijau neon dan efek pembesaran (*scale up*).
3. **Audio Chime Synthesizer (Web Audio API)**:
   - Fitur suara lonceng ding-dong (*chime tone*) opsional saat ada tap presensi baru masuk.
4. **Mode Fullscreen / Layar TV**:
   - Tombol 1-klik untuk mode layar penuh (*Fullscreen Kiosk Mode*) yang cocok dipasang pada Smart TV / Android TV di lobi.
5. **Indikator Status & Waktu Akurat**:
   - Jam digital presisi tinggi (WIB), tanggal lengkap, serta kalkulasi waktu relatif (*"Baru saja"*, *"2 mnt lalu"*).
6. **Ringkasan Statistik Cepat**:
   - Counter otomatis menghitung total scan, hadir tepat waktu, terlambat, dan pulang hari ini.

---

## 🚀 Cara Menjalankan Berdasarkan Framework

### 1. 🔴 Laravel (10 / 11 / 12 / 13)

1. **Salin File Blade**:
   - Salin file [`backend-examples/laravel/resources/views/iot/live_monitor.blade.php`](file:///d:/ubay/00.projek-website/api-absensi-iot/backend-examples/laravel/resources/views/iot/live_monitor.blade.php) ke dalam folder `resources/views/iot/live_monitor.blade.php` di proyek Anda.
2. **Tambahkan Route Web & API**:
   - Di `routes/web.php`:
     ```php
     use App\Http\Controllers\IotAttendanceController;

     Route::get('/absensi/monitor', [IotAttendanceController::class, 'liveMonitor'])
         ->name('absensi.monitor');
     ```
   - Di `routes/api.php`:
     ```php
     Route::get('/v1/iot/monitor/data', [IotAttendanceController::class, 'liveMonitorData'])
         ->name('iot.monitor.data');
     ```
3. **Buka di Browser / Smart TV**:
   - Kunjungi URL: `http://website-anda.test/absensi/monitor`

---

### 2. 🐘 PHP Native / cPanel Hosting

1. **Upload File**:
   - Upload file [`backend-examples/php-native/monitor.php`](file:///d:/ubay/00.projek-website/api-absensi-iot/backend-examples/php-native/monitor.php) ke root public folder web Anda (misal `public_html/monitor.php`).
   - Upload file [`backend-examples/php-native/api/monitor_data.php`](file:///d:/ubay/00.projek-website/api-absensi-iot/backend-examples/php-native/api/monitor_data.php) ke folder `public_html/api/monitor_data.php`.
2. **Buka di Browser**:
   - Akses: `https://website-anda.com/monitor.php`

---

## 📺 Tips Menjadikan Layar TV Lobi / Smart TV (Kiosk Mode)

1. **Gunakan Browser Bawaan Smart TV atau Chrome Android Box**:
   - Buka URL `/absensi/monitor`.
   - Klik tombol **Fullscreen** di pojok kanan atas untuk menyembunyikan address bar.
2. **Aktifkan Audio Chime**:
   - Klik tombol **Suara ON** jika ingin Smart TV mengeluarkan bunyi lonceng nada lembut setiap kali ada yang tap kartu di gerbang.
3. **Kombinasi dengan Hardware Simulator**:
   - Anda dapat membuka halaman Live Monitor di Tab 1, lalu membuka [`simulator/index.html`](file:///d:/ubay/00.projek-website/api-absensi-iot/simulator/index.html) di Tab 2.
   - Saat Anda mengklik tombol **"TAP KARTU"** di simulator, data langsung terpampang di Tab Live Monitor dalam hitungan detik!
