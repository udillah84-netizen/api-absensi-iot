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

echo json_encode([
    'success'     => true,
    'server_time' => date('c'),
    'device'      => [
        'device_id' => $deviceId ?: 'DEV-MESIN',
    ],
    'schedules'   => [
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
