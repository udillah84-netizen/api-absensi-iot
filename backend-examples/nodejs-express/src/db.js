// Simple In-Memory / Extensible Database Store untuk Node.js Express
// Bisa diganti dengan Prisma, Sequelize, Mongoose, atau knex.

const db = {
  users: [
    { id: 1, name: 'Ahmad Fauzi', rfid_uid: 'A1B2C3D4', barcode_uid: 'SISWA-2026-001', role: 'siswa', department: 'XII RPL 1', is_active: true },
    { id: 2, name: 'Budi Santoso', rfid_uid: 'E5F6G7H8', barcode_uid: 'SISWA-2026-002', role: 'siswa', department: 'XII RPL 1', is_active: true },
    { id: 3, name: 'Siti Nurhaliza', rfid_uid: '99AA88BB', barcode_uid: 'SISWA-2026-003', role: 'siswa', department: 'XII TKJ 2', is_active: true },
    { id: 4, name: 'Drs. Hendra Wijaya', rfid_uid: 'CCDD1122', barcode_uid: 'GURU-001', role: 'guru', department: 'Kurikulum', is_active: true },
  ],
  devices: [
    { device_id: 'DEV-GERBANG-01', nama: 'Smart Box Gerbang', token: 'tok_rahasia_mesin_anda', status: 'online', last_heartbeat: new Date() },
  ],
  settings: {
    siswa: { jam_masuk: '07:00', batas_terlambat: '07:30', jam_pulang: '14:00' },
    guru: { jam_masuk: '07:30', batas_terlambat: '08:00', jam_pulang: '16:00' },
  },
  attendances: [],
  scan_logs: [],
};

module.exports = db;
