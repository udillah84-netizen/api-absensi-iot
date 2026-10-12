<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya menerima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$pdo = getDbConnection();

// 1. Validasi Token Mesin
$auth = authenticateDeviceToken($pdo);
if (!$auth) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'status'  => 'Unauthorized',
        'nama'    => null,
        'message' => 'Token Perangkat Tidak Valid',
    ]);
    exit;
}

// 2. Baca Input JSON dari Mesin ESP32
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$code = trim($data['code'] ?? ($data['kode'] ?? ''));
$deviceId = trim($data['device_id'] ?? ($auth['device']['device_id'] ?? 'DEV-UNKNOWN'));
$jenis = strtoupper(trim($data['jenis'] ?? 'RFID'));
$scannedAtStr = trim($data['scanned_at'] ?? '');

if (empty($code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'status'  => 'Bad Request',
        'message' => 'Parameter code / UID wajib diisi',
    ]);
    exit;
}

$scannedAt = !empty($scannedAtStr) ? new DateTime($scannedAtStr) : new DateTime();
$tanggal = $scannedAt->format('Y-m-d');
$jamSekarang = $scannedAt->format('H:i:s');
$jamDisplay = $scannedAt->format('H:i');

// 3. Update status online mesin
try {
    $stmtDev = $pdo->prepare('UPDATE attendance_devices SET status = "online", last_heartbeat = NOW() WHERE device_id = ?');
    $stmtDev->execute([$deviceId]);
} catch (Exception $e) {}

// 4. Cari Data User di Tabel users (by RFID, Barcode, atau ID)
$stmtUser = $pdo->prepare('SELECT * FROM users WHERE rfid_uid = ? OR barcode_uid = ? OR identifier_number = ? OR id = ? LIMIT 1');
$stmtUser->execute([$code, $code, $code, $code]);
$user = $stmtUser->fetch();

// Helper untuk menyimpan riwayat scan mentah
function logScan(PDO $pdo, $deviceId, $code, $tipeUser, $userId, $jenis, $status, $message, $scannedAt) {
    try {
        $stmt = $pdo->prepare('INSERT INTO attendance_scan_logs (device_id, code, tipe_user, user_id, jenis, status, message, scanned_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$deviceId, $code, $tipeUser, $userId, $jenis, $status, $message, $scannedAt->format('Y-m-d H:i:s')]);
    } catch (Exception $e) {}
}

// Skenario 1: Kartu Tidak Dikenal
if (!$user) {
    logScan($pdo, $deviceId, $code, null, null, $jenis, 'failed', 'Kartu tidak terdaftar', $scannedAt);
    recordUnregisteredCard($pdo, $code, $jenis, $deviceId);

    http_response_code(404);
    echo json_encode([
        'success' => false,
        'status'  => 'Gagal',
        'nama'    => null,
        'message' => 'Kartu Tidak Terdaftar',
    ]);
    exit;
}

// Skenario 2: Akun Nonaktif
if (isset($user['is_active']) && !$user['is_active']) {
    logScan($pdo, $deviceId, $code, $user['role'] ?? 'user', $user['id'], $jenis, 'failed', 'Akun nonaktif', $scannedAt);
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'status'  => 'Ditolak',
        'nama'    => formatLcdName($user['name']),
        'message' => 'Akun Pengguna Nonaktif',
    ]);
    exit;
}

$namaLcd = formatLcdName($user['name']);
$departemen = $user['department'] ?? '-';
$userRole = $user['role'] ?? 'siswa';

// 5. Ambil Setting Jam Kehadiran
$jamMasukSetting = DEFAULT_JAM_MASUK;
$batasTerlambat  = DEFAULT_BATAS_TERLAMBAT;
$jamPulangSetting = DEFAULT_JAM_PULANG;

try {
    $stmtSet = $pdo->prepare('SELECT * FROM attendance_settings WHERE tipe_user = ? LIMIT 1');
    $stmtSet->execute([$userRole]);
    $setting = $stmtSet->fetch();
    if ($setting) {
        $jamMasukSetting = $setting['jam_masuk'];
        $batasTerlambat  = $setting['batas_terlambat'];
        $jamPulangSetting = $setting['jam_pulang'];
    }
} catch (Exception $e) {}

// 6. Cek Data Absensi Hari Ini
$stmtAtt = $pdo->prepare('SELECT * FROM attendances WHERE user_id = ? AND tanggal = ? LIMIT 1');
$stmtAtt->execute([$user['id'], $tanggal]);
$attendance = $stmtAtt->fetch();

// Anti-Duplicate Tap (Jeda < 30 detik)
if ($attendance && !empty($attendance['updated_at'])) {
    $updatedAt = new DateTime($attendance['updated_at']);
    $diffSec = abs($scannedAt->getTimestamp() - $updatedAt->getTimestamp());
    if ($diffSec < ANTI_DUPLICATE_WINDOW) {
        logScan($pdo, $deviceId, $code, $userRole, $user['id'], $jenis, 'success', 'Duplicate tap ignored', $scannedAt);
        echo json_encode([
            'success' => true,
            'status'  => $attendance['status'],
            'nama'    => $namaLcd,
            'kelas'   => $departemen,
            'jam'     => $jamDisplay,
            'message' => 'Sudah Absen Barusan',
        ]);
        exit;
    }
}

// Skenario 3: Absen Pulang
if ($attendance && empty($attendance['jam_pulang']) && $jamSekarang >= $jamPulangSetting) {
    $stmtUp = $pdo->prepare('UPDATE attendances SET jam_pulang = ?, device_id_pulang = ?, metode_pulang = ?, updated_at = NOW() WHERE id = ?');
    $stmtUp->execute([$jamSekarang, $deviceId, $jenis, $attendance['id']]);

    logScan($pdo, $deviceId, $code, $userRole, $user['id'], $jenis, 'success', 'Absen Pulang Berhasil', $scannedAt);

    echo json_encode([
        'success' => true,
        'status'  => 'Pulang',
        'nama'    => $namaLcd,
        'kelas'   => $departemen,
        'jam'     => $jamDisplay,
        'message' => 'Absensi Pulang Berhasil',
    ]);
    exit;
}

// Skenario 4: Sudah Pernah Absen Masuk & Belum Waktunya Pulang
if ($attendance && !empty($attendance['jam_masuk'])) {
    logScan($pdo, $deviceId, $code, $userRole, $user['id'], $jenis, 'success', 'Sudah absen masuk', $scannedAt);
    echo json_encode([
        'success' => true,
        'status'  => $attendance['status'],
        'nama'    => $namaLcd,
        'kelas'   => $departemen,
        'jam'     => substr($attendance['jam_masuk'], 0, 5),
        'message' => 'Sudah Absen Hari Ini',
    ]);
    exit;
}

// Skenario 5: Absen Masuk Pertama Kali Hari Ini
$statusMasuk = ($jamSekarang <= $batasTerlambat) ? 'Hadir' : 'Telat';

if ($attendance) {
    $stmtUp = $pdo->prepare('UPDATE attendances SET jam_masuk = ?, status = ?, device_id_masuk = ?, metode_masuk = ?, updated_at = NOW() WHERE id = ?');
    $stmtUp->execute([$jamSekarang, $statusMasuk, $deviceId, $jenis, $attendance['id']]);
} else {
    $stmtIns = $pdo->prepare('INSERT INTO attendances (user_id, tanggal, jam_masuk, status, device_id_masuk, metode_masuk, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmtIns->execute([$user['id'], $tanggal, $jamSekarang, $statusMasuk, $deviceId, $jenis]);
}

logScan($pdo, $deviceId, $code, $userRole, $user['id'], $jenis, 'success', 'Absen Masuk: ' . $statusMasuk, $scannedAt);

echo json_encode([
    'success' => true,
    'status'  => $statusMasuk,
    'nama'    => $namaLcd,
    'kelas'   => $departemen,
    'jam'     => $jamDisplay,
    'message' => ($statusMasuk === 'Hadir') ? 'Absensi Masuk Berhasil' : 'Absen Masuk (Terlambat)',
]);
