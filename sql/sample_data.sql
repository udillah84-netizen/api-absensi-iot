-- ====================================================================
-- SAMPLE DATA UNTUK PENGUJIAN INSTAN
-- SISTEM INTEGRASI MESIN ABSENSI IOT (RFID & BARCODE / QR 2D)
-- ====================================================================

-- 1. Setting Jam Default
INSERT INTO attendance_settings (tipe_user, jam_masuk, batas_terlambat, jam_pulang)
VALUES 
('siswa', '07:00:00', '07:30:00', '14:00:00'),
('pegawai', '07:30:00', '08:00:00', '16:00:00')
ON DUPLICATE KEY UPDATE 
    jam_masuk = VALUES(jam_masuk),
    batas_terlambat = VALUES(batas_terlambat),
    jam_pulang = VALUES(jam_pulang);

-- 2. Setting Profil Lembaga & Standby LCD Mesin
INSERT INTO iot_settings (id, nama_lembaga, standby_text, timezone, duplicate_window_seconds)
VALUES 
(1, 'SMK NEGERI 1 BOGOR', 'SILAKAN ABSEN', 'Asia/Jakarta', 30)
ON DUPLICATE KEY UPDATE 
    nama_lembaga = VALUES(nama_lembaga),
    standby_text = VALUES(standby_text);

-- 3. Pendaftaran Perangkat IoT Percobaan
INSERT INTO attendance_devices (device_id, nama, lokasi, token, status, wifi_rssi, scans_today, firmware_version)
VALUES 
('DEV-GERBANG-01', 'Smart Box Gerbang Depan', 'Pintu Masuk Utama', 'tok_rahasia_mesin_anda', 'online', -62, 142, 'v2.0.0'),
('DEV-KANTOR-01', 'Mesin Absensi Guru & Staf', 'Lobi Kantor Guru', 'tok_kantor_guru_secret', 'offline', -78, 0, 'v2.0.0')
ON DUPLICATE KEY UPDATE 
    nama = VALUES(nama),
    token = VALUES(token),
    wifi_rssi = VALUES(wifi_rssi),
    scans_today = VALUES(scans_today);

-- 3. Data Pengguna Percobaan (Siswa & Karyawan dengan UID RFID & Barcode)
INSERT INTO users (name, email, rfid_uid, barcode_uid, identifier_number, role, department, is_active)
VALUES 
-- Siswa dengan kartu RFID & Barcode
('Ahmad Fauzi', 'ahmad.fauzi@example.com', 'A1B2C3D4', 'SISWA-2026-001', 'NISN-00192831', 'siswa', 'XII RPL 1', 1),
('Budi Santoso', 'budi.santoso@example.com', 'E5F6G7H8', 'SISWA-2026-002', 'NISN-00192832', 'siswa', 'XII RPL 1', 1),
('Siti Nurhaliza', 'siti.nur@example.com', '99AA88BB', 'SISWA-2026-003', 'NISN-00192833', 'siswa', 'XII TKJ 2', 1),
('Rizky Ramadhan', 'rizky.r@example.com', '12345678', 'SISWA-2026-004', 'NISN-00192834', 'siswa', 'X MM 1', 1),

-- Pegawai / Guru
('Drs. Hendra Wijaya', 'hendra.guru@example.com', 'CCDD1122', 'GURU-NIP-19800101', '198001012005011002', 'guru', 'Kurikulum', 1),
('Dewi Lestari, S.Pd', 'dewi.guru@example.com', 'FFEEDD33', 'GURU-NIP-19850202', '198502022010012005', 'guru', 'Kesiswaan', 1)
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    rfid_uid = VALUES(rfid_uid),
    barcode_uid = VALUES(barcode_uid);
