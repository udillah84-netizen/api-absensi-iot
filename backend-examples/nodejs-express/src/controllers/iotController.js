const db = require('../db');

function formatLcdName(name) {
  if (!name) return '-';
  name = name.trim();
  if (name.length <= 16) return name;

  const parts = name.split(' ');
  if (parts.length > 1) {
    let shortened = parts[0];
    for (let i = 1; i < parts.length; i++) {
      if ((shortened + ' ' + parts[i]).length <= 16) {
        shortened += ' ' + parts[i];
      } else {
        shortened += ' ' + parts[i][0] + '.';
        break;
      }
    }
    if (shortened.length <= 16) return shortened;
  }

  return name.slice(0, 16);
}

exports.scan = (req, res) => {
  const code = (req.body.code || req.body.kode || '').trim();
  const deviceId = req.body.device_id || (req.device ? req.device.device_id : 'DEV-UNKNOWN');
  const jenis = (req.body.jenis || 'RFID').toUpperCase();
  const scannedAt = req.body.scanned_at ? new Date(req.body.scanned_at) : new Date();

  if (!code) {
    return res.status(400).json({ success: false, message: 'Parameter code wajib diisi' });
  }

  // Cari User di Database
  const user = db.users.find(
    (u) => u.rfid_uid === code || u.barcode_uid === code || String(u.id) === code
  );

  // 1. Kartu Tidak Dikenal
  if (!user) {
    db.scan_logs.push({ deviceId, code, jenis, status: 'failed', message: 'Kartu tidak terdaftar', scannedAt });
    return res.status(404).json({
      success: false,
      status: 'Gagal',
      nama: null,
      message: 'Kartu Tidak Terdaftar',
    });
  }

  // 2. Akun Nonaktif
  if (!user.is_active) {
    return res.status(403).json({
      success: false,
      status: 'Ditolak',
      nama: formatLcdName(user.name),
      message: 'Akun Pengguna Nonaktif',
    });
  }

  const tanggal = scannedAt.toISOString().split('T')[0];
  const jamSekarang = scannedAt.toTimeString().split(' ')[0].slice(0, 5); // HH:MM
  const namaLcd = formatLcdName(user.name);
  const department = user.department || '-';

  const schedule = db.settings[user.role] || db.settings.siswa;
  const batasTerlambat = schedule.batas_terlambat || '07:30';
  const jamPulang = schedule.jam_pulang || '14:00';

  let attendance = db.attendances.find((a) => a.userId === user.id && a.tanggal === tanggal);

  // Anti-Duplicate (30 detik)
  if (attendance && attendance.lastScannedAt) {
    const diffSec = Math.abs((scannedAt - new Date(attendance.lastScannedAt)) / 1000);
    if (diffSec < 30) {
      return res.json({
        success: true,
        status: attendance.status,
        nama: namaLcd,
        kelas: department,
        jam: jamSekarang,
        message: 'Sudah Absen Barusan',
      });
    }
  }

  // Absen Pulang
  if (attendance && !attendance.jamPulang && jamSekarang >= jamPulang) {
    attendance.jamPulang = jamSekarang;
    attendance.lastScannedAt = scannedAt;
    return res.json({
      success: true,
      status: 'Pulang',
      nama: namaLcd,
      kelas: department,
      jam: jamSekarang,
      message: 'Absensi Pulang Berhasil',
    });
  }

  // Sudah Absen Masuk
  if (attendance && attendance.jamMasuk) {
    return res.json({
      success: true,
      status: attendance.status,
      nama: namaLcd,
      kelas: department,
      jam: attendance.jamMasuk,
      message: 'Sudah Absen Hari Ini',
    });
  }

  // Absen Masuk Pertama Kali
  const statusMasuk = jamSekarang <= batasTerlambat ? 'Hadir' : 'Telat';

  attendance = {
    id: db.attendances.length + 1,
    userId: user.id,
    tanggal,
    jamMasuk: jamSekarang,
    jamPulang: null,
    status: statusMasuk,
    deviceIdMasuk: deviceId,
    metode: jenis,
    lastScannedAt: scannedAt,
  };
  db.attendances.push(attendance);

  return res.json({
    success: true,
    status: statusMasuk,
    nama: namaLcd,
    kelas: department,
    jam: jamSekarang,
    message: statusMasuk === 'Hadir' ? 'Absensi Masuk Berhasil' : 'Absen Masuk (Terlambat)',
  });
};

exports.heartbeat = (req, res) => {
  return res.json({
    success: true,
    server_time: new Date().toISOString(),
    heartbeat_interval: 60,
    message: 'Heartbeat OK',
  });
};

exports.config = (req, res) => {
  return res.json({
    success: true,
    server_time: new Date().toISOString(),
    schedules: db.settings,
  });
};
