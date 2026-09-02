<?php

/**
 * ====================================================================
 * KONFIGURASI SISTEM ABSENSI IOT (PHP NATIVE)
 * ====================================================================
 */

// 1. Kredensial Database MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_DATABASE') ?: 'db_absensi');
define('DB_USER', getenv('DB_USERNAME') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

// 2. Secret Token Mesin IoT (Harus sama dengan yang terprogram di mesin ESP32)
define('IOT_SECRET_TOKEN', getenv('IOT_SECRET_TOKEN') ?: 'tok_rahasia_mesin_anda');

// 3. Waktu Proteksi Anti-Duplicate Tap (dalam detik)
define('ANTI_DUPLICATE_WINDOW', 30);

// 4. Jadwal Default Absensi (Jika tidak menggunakan data dinamis dari tabel settings)
define('DEFAULT_JAM_MASUK', '07:00:00');
define('DEFAULT_BATAS_TERLAMBAT', '07:30:00');
define('DEFAULT_JAM_PULANG', '14:00:00');

// Zona Waktu (WIB / Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');
