-- ====================================================================
-- DATABASE SCHEMA: SQLITE
-- SISTEM INTEGRASI MESIN ABSENSI IOT (RFID & BARCODE / QR 2D)
-- ====================================================================

-- 1. Tabel Master User
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE,
    rfid_uid TEXT UNIQUE,
    barcode_uid TEXT UNIQUE,
    identifier_number TEXT UNIQUE,
    role TEXT DEFAULT 'siswa', -- siswa, pegawai, guru, karyawan, member
    department TEXT, -- Kelas atau Divisi
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_rfid ON users (rfid_uid);
CREATE INDEX IF NOT EXISTS idx_users_barcode ON users (barcode_uid);

-- 2. Tabel Pendaftaran Perangkat IoT
CREATE TABLE IF NOT EXISTS attendance_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT NOT NULL UNIQUE,
    nama TEXT NOT NULL,
    lokasi TEXT,
    token TEXT NOT NULL UNIQUE,
    tipe_sensor TEXT DEFAULT 'COMBO', -- COMBO, RFID, BARCODE
    status TEXT DEFAULT 'offline', -- online, offline
    last_heartbeat DATETIME,
    ip_address TEXT,
    firmware_version TEXT DEFAULT 'v2.0.0',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_devices_token ON attendance_devices (token);

-- 3. Tabel Riwayat Scan Mentah (Attendance Scan Logs)
CREATE TABLE IF NOT EXISTS attendance_scan_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT,
    code TEXT NOT NULL,
    tipe_user TEXT,
    user_id INTEGER,
    jenis TEXT DEFAULT 'RFID', -- RFID, BARCODE
    status TEXT NOT NULL, -- success, failed
    message TEXT,
    scanned_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_scan_logs_device ON attendance_scan_logs (device_id, scanned_at);

-- 4. Tabel Transaksi Kehadiran Harian
CREATE TABLE IF NOT EXISTS attendances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status TEXT NOT NULL DEFAULT 'Hadir', -- Hadir, Telat, Izin, Sakit, Alpha
    device_id_masuk TEXT,
    device_id_pulang TEXT,
    metode_masuk TEXT DEFAULT 'RFID',
    metode_pulang TEXT,
    keterangan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, tanggal),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_attendances_tanggal ON attendances (tanggal, status);

-- 5. Tabel Konfigurasi Jam Presensi
CREATE TABLE IF NOT EXISTS attendance_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tipe_user TEXT NOT NULL UNIQUE,
    jam_masuk TIME NOT NULL DEFAULT '07:00:00',
    batas_terlambat TIME NOT NULL DEFAULT '07:30:00',
    jam_pulang TIME NOT NULL DEFAULT '15:00:00',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
