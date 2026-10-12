-- ====================================================================
-- DATABASE SCHEMA: MYSQL / MARIADB
-- SISTEM INTEGRASI MESIN ABSENSI IOT (RFID & BARCODE / QR 2D)
-- ====================================================================

-- 1. Tambah kolom kartu RFID dan Barcode pada tabel master user yang sudah ada
-- (Sesuaikan nama tabel pengguna Anda, misal: 'users', 'siswa', atau 'pegawai')
-- Uncomment jika Anda sudah memiliki tabel users:
-- ALTER TABLE users 
--     ADD COLUMN rfid_uid VARCHAR(64) UNIQUE NULL AFTER email,
--     ADD COLUMN barcode_uid VARCHAR(64) UNIQUE NULL AFTER rfid_uid;

-- Tabel Master User / Anggota (Jika membuat dari awal)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    rfid_uid VARCHAR(64) UNIQUE NULL,
    barcode_uid VARCHAR(64) UNIQUE NULL,
    identifier_number VARCHAR(64) UNIQUE NULL COMMENT 'NISN / NIP / NIK / ID Karyawan',
    role ENUM('siswa', 'pegawai', 'guru', 'karyawan', 'member') DEFAULT 'siswa',
    department VARCHAR(100) NULL COMMENT 'Kelas atau Divisi',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rfid (rfid_uid),
    INDEX idx_barcode (barcode_uid),
    INDEX idx_identifier (identifier_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Pendaftaran Perangkat IoT (Attendance Devices)
CREATE TABLE IF NOT EXISTS attendance_devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL UNIQUE COMMENT 'ID Unik Mesin, misal: DEV-GERBANG-01',
    nama VARCHAR(100) NOT NULL COMMENT 'Nama label mesin, misal: Gate Utama',
    lokasi VARCHAR(100) NULL COMMENT 'Lokasi fisik, misal: Pintu Masuk Depan',
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Secret Device Token untuk autentikasi API',
    tipe_sensor ENUM('COMBO', 'RFID', 'BARCODE') DEFAULT 'COMBO' COMMENT 'Varian hardware sensor mesin',
    status ENUM('online', 'offline') DEFAULT 'offline',
    wifi_rssi INT NULL COMMENT 'Kekuatan sinyal WiFi dalam dBm (-50 bagus, -85 lemah)',
    scans_today INT UNSIGNED DEFAULT 0 COMMENT 'Total scan berhasil hari ini',
    standby_text_override VARCHAR(50) NULL COMMENT 'Override teks standby LCD khusus mesin ini',
    last_heartbeat TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    firmware_version VARCHAR(20) DEFAULT 'v2.0.0',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Riwayat Scan Mentah (Attendance Scan Logs - untuk audit & debugging)
CREATE TABLE IF NOT EXISTS attendance_scan_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NULL,
    code VARCHAR(128) NOT NULL COMMENT 'Data mentah UID atau Barcode hasil scan',
    tipe_user VARCHAR(50) NULL COMMENT 'siswa/pegawai/dll',
    user_id BIGINT UNSIGNED NULL,
    jenis ENUM('RFID', 'BARCODE') DEFAULT 'RFID',
    status ENUM('success', 'failed') NOT NULL,
    message VARCHAR(255) NULL,
    scanned_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_scan (device_id, scanned_at),
    INDEX idx_user_scan_log (user_id, scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Transaksi Kehadiran Harian (Attendances)
CREATE TABLE IF NOT EXISTS attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME NULL,
    jam_pulang TIME NULL,
    status ENUM('Hadir', 'Telat', 'Izin', 'Sakit', 'Alpha') NOT NULL DEFAULT 'Hadir',
    device_id_masuk VARCHAR(50) NULL,
    device_id_pulang VARCHAR(50) NULL,
    metode_masuk ENUM('RFID', 'BARCODE') DEFAULT 'RFID',
    metode_pulang ENUM('RFID', 'BARCODE') NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_tanggal (user_id, tanggal),
    INDEX idx_tanggal_status (tanggal, status),
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Konfigurasi Jam Kerja / Sekolah (Attendance Settings)
CREATE TABLE IF NOT EXISTS attendance_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipe_user VARCHAR(50) NOT NULL UNIQUE COMMENT 'siswa / pegawai / default',
    jam_masuk TIME NOT NULL DEFAULT '07:00:00',
    batas_terlambat TIME NOT NULL DEFAULT '07:30:00',
    jam_pulang TIME NOT NULL DEFAULT '15:00:00',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Konfigurasi Lembaga & Mesin IoT (IoT Settings)
CREATE TABLE IF NOT EXISTS iot_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lembaga VARCHAR(100) NOT NULL DEFAULT 'SMK NEGERI 1 CONTOH' COMMENT 'Nama sekolah/kantor untuk baris 1 LCD 16x2',
    standby_text VARCHAR(50) NOT NULL DEFAULT 'SILAKAN ABSEN' COMMENT 'Teks standby baris 2 LCD 16x2',
    timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Jakarta',
    duplicate_window_seconds INT UNSIGNED NOT NULL DEFAULT 30,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel Antrean Kartu Belum Terdaftar (Unregistered Card Enrollment)
CREATE TABLE IF NOT EXISTS unregistered_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(128) NOT NULL UNIQUE COMMENT 'UID RFID atau kode Barcode',
    jenis ENUM('RFID', 'BARCODE') DEFAULT 'RFID',
    device_id VARCHAR(50) NULL,
    last_scanned_at DATETIME NOT NULL,
    tap_count INT UNSIGNED DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_scanned (last_scanned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
