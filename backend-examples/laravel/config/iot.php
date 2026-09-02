<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global IoT Secret Token
    |--------------------------------------------------------------------------
    |
    | Token rahasia default yang digunakan untuk otorisasi mesin IoT ESP32.
    | Jika tidak menggunakan token per-perangkat di database, token global ini
    | yang akan divalidasi pada header 'X-Device-Token' atau 'X-IoT-Token'.
    |
    */
    'secret_token' => env('IOT_SECRET_TOKEN', 'tok_rahasia_mesin_anda'),

    /*
    |--------------------------------------------------------------------------
    | Anti-Duplicate Tap Window (Detik)
    |--------------------------------------------------------------------------
    |
    | Durasi proteksi (dalam detik) untuk mencegah double-tap / tap beruntun 
    | kartu yang sama secara tidak sengaja. Default: 30 detik.
    |
    */
    'duplicate_window_seconds' => (int) env('IOT_DUPLICATE_WINDOW_SECONDS', 30),

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Timeout Offline (Menit)
    |--------------------------------------------------------------------------
    |
    | Ambang batas waktu tidak ada sinyal heartbeat sebelum perangkat dianggap offline.
    |
    */
    'heartbeat_timeout_minutes' => (int) env('IOT_HEARTBEAT_TIMEOUT_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Firmware OTA Update Setting
    |--------------------------------------------------------------------------
    */
    'firmware' => [
        'latest_version' => env('IOT_FIRMWARE_LATEST_VERSION', 'v2.0.0'),
        'download_url'   => env('IOT_FIRMWARE_DOWNLOAD_URL', ''),
        'checksum_sha256'=> env('IOT_FIRMWARE_CHECKSUM_SHA256', ''),
    ],
];
