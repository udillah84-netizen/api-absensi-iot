-- ====================================================================
-- DATABASE SCHEMA: POSTGRESQL
-- SISTEM INTEGRASI MESIN ABSENSI IOT (RFID & BARCODE / QR 2D)
-- ====================================================================

CREATE TYPE user_role_enum AS ENUM ('siswa', 'pegawai', 'guru', 'karyawan', 'member');
CREATE TYPE device_status_enum AS ENUM ('online', 'offline');
CREATE TYPE scan_status_enum AS ENUM ('success', 'failed');
CREATE TYPE attendance_status_enum AS ENUM ('Hadir', 'Telat', 'Izin', 'Sakit', 'Alpha');
CREATE TYPE scan_method_enum AS ENUM ('RFID', 'BARCODE');

-- 1. Tabel Master User
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    rfid_uid VARCHAR(64) UNIQUE,
    barcode_uid VARCHAR(64) UNIQUE,
    identifier_number VARCHAR(64) UNIQUE,
    role user_role_enum DEFAULT 'siswa',
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_rfid ON users (rfid_uid);
CREATE INDEX IF NOT EXISTS idx_users_barcode ON users (barcode_uid);
CREATE INDEX IF NOT EXISTS idx_users_identifier ON users (identifier_number);

-- 2. Tabel Pendaftaran Perangkat IoT
CREATE TABLE IF NOT EXISTS attendance_devices (
    id BIGSERIAL PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    lokasi VARCHAR(100),
    token VARCHAR(64) NOT NULL UNIQUE,
    tipe_sensor VARCHAR(20) DEFAULT 'COMBO', -- 'COMBO', 'RFID', 'BARCODE'
    status device_status_enum DEFAULT 'offline',
    last_heartbeat TIMESTAMP WITH TIME ZONE,
    ip_address VARCHAR(45),
    firmware_version VARCHAR(20) DEFAULT 'v2.0.0',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_devices_token ON attendance_devices (token);

-- 3. Tabel Riwayat Scan Mentah (Attendance Scan Logs)
CREATE TABLE IF NOT EXISTS attendance_scan_logs (
    id BIGSERIAL PRIMARY KEY,
    device_id VARCHAR(50),
    code VARCHAR(128) NOT NULL,
    tipe_user VARCHAR(50),
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    jenis scan_method_enum DEFAULT 'RFID',
    status scan_status_enum NOT NULL,
    message VARCHAR(255),
    scanned_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_scan_logs_device ON attendance_scan_logs (device_id, scanned_at);
CREATE INDEX IF NOT EXISTS idx_scan_logs_user ON attendance_scan_logs (user_id, scanned_at);

-- 4. Tabel Transaksi Kehadiran Harian
CREATE TABLE IF NOT EXISTS attendances (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status attendance_status_enum NOT NULL DEFAULT 'Hadir',
    device_id_masuk VARCHAR(50),
    device_id_pulang VARCHAR(50),
    metode_masuk scan_method_enum DEFAULT 'RFID',
    metode_pulang scan_method_enum,
    keterangan VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_attendance_tanggal UNIQUE (user_id, tanggal)
);

CREATE INDEX IF NOT EXISTS idx_attendances_tanggal_status ON attendances (tanggal, status);

-- 5. Tabel Konfigurasi Jam Presensi
CREATE TABLE IF NOT EXISTS attendance_settings (
    id BIGSERIAL PRIMARY KEY,
    tipe_user VARCHAR(50) NOT NULL UNIQUE,
    jam_masuk TIME NOT NULL DEFAULT '07:00:00',
    batas_terlambat TIME NOT NULL DEFAULT '07:30:00',
    jam_pulang TIME NOT NULL DEFAULT '15:00:00',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
