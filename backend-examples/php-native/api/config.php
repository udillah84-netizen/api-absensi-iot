<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDbConnection();
$auth = authenticateDeviceToken($pdo);

if (!$auth) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized Device Token']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$deviceId = trim($data['device_id'] ?? '');

$siswaSetting = ['jam_masuk' => DEFAULT_JAM_MASUK, 'batas_terlambat' => DEFAULT_BATAS_TERLAMBAT, 'jam_pulang' => DEFAULT_JAM_PULANG];
$pegawaiSetting = ['jam_masuk' => '07:30:00', 'batas_terlambat' => '08:00:00', 'jam_pulang' => '16:00:00'];

try {
    $stmt = $pdo->query('SELECT * FROM attendance_settings');
    while ($row = $stmt->fetch()) {
        if ($row['tipe_user'] === 'siswa') {
            $siswaSetting = $row;
        } elseif ($row['tipe_user'] === 'pegawai') {
            $pegawaiSetting = $row;
        }
    }
} catch (Exception $e) {}

$iotSettings = getIotSettings($pdo);

$device = null;
if (!empty($deviceId)) {
    try {
        $stmtDev = $pdo->prepare('SELECT * FROM attendance_devices WHERE device_id = ? LIMIT 1');
        $stmtDev->execute([$deviceId]);
        $device = $stmtDev->fetch();
    } catch (Exception $e) {}
}

$namaLembaga = $iotSettings['nama_lembaga'] ?? (defined('NAMA_LEMBAGA') ? NAMA_LEMBAGA : 'SMK NEGERI 1 BOGOR');
$standbyText = !empty($device['standby_text_override']) ? $device['standby_text_override'] : ($iotSettings['standby_text'] ?? (defined('STANDBY_TEXT') ? STANDBY_TEXT : 'SILAKAN ABSEN'));
$tipeSensor = $device['tipe_sensor'] ?? 'COMBO';

echo json_encode([
    'success'      => true,
    'nama_lembaga' => formatLcdName($namaLembaga),
    'tipe_sensor'  => $tipeSensor,
    'standby_text' => $standbyText,
    'server_time'  => date('c'),
    'device'       => [
        'device_id' => $device['device_id'] ?? ($deviceId ?: 'DEV-MESIN'),
        'nama'      => $device['nama'] ?? 'Mesin Absensi',
        'lokasi'    => $device['lokasi'] ?? 'Pintu Utama',
    ],
    'schedules'    => [
        'siswa' => [
            'jam_masuk'       => substr($siswaSetting['jam_masuk'], 0, 5),
            'batas_terlambat' => substr($siswaSetting['batas_terlambat'], 0, 5),
            'jam_pulang'      => substr($siswaSetting['jam_pulang'], 0, 5),
        ],
        'pegawai' => [
            'jam_masuk'       => substr($pegawaiSetting['jam_masuk'], 0, 5),
            'batas_terlambat' => substr($pegawaiSetting['batas_terlambat'], 0, 5),
            'jam_pulang'      => substr($pegawaiSetting['jam_pulang'], 0, 5),
        ],
    ],
]);
