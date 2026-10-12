<?php
/**
 * ============================================================================
 * STARTER KIT PHP NATIVE - DEVICE TELEMETRY & MANAGEMENT API (JSON)
 * ============================================================================
 * Endpoint: /api/device_data.php
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST actions: Save Settings / Enroll Card / Delete Card
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $action = $data['action'] ?? '';

    // Action 1: Save Settings
    if ($action === 'save_settings') {
        $namaLembaga = trim($data['nama_lembaga'] ?? 'SMK NEGERI 1 BOGOR');
        $standbyText = trim($data['standby_text'] ?? 'SILAKAN ABSEN');
        $dupWindow = (int)($data['duplicate_window_seconds'] ?? 30);

        try {
            // Update / insert iot_settings
            $stmt = $pdo->prepare('
                INSERT INTO iot_settings (id, nama_lembaga, standby_text, timezone, duplicate_window_seconds, created_at, updated_at)
                VALUES (1, ?, ?, "Asia/Jakarta", ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    nama_lembaga = VALUES(nama_lembaga),
                    standby_text = VALUES(standby_text),
                    duplicate_window_seconds = VALUES(duplicate_window_seconds),
                    updated_at = NOW()
            ');
            $stmt->execute([$namaLembaga, $standbyText, $dupWindow]);

            // Update jadwal siswa
            $stmtSiswa = $pdo->prepare('
                INSERT INTO attendance_settings (tipe_user, jam_masuk, batas_terlambat, jam_pulang, created_at, updated_at)
                VALUES ("siswa", ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    jam_masuk = VALUES(jam_masuk),
                    batas_terlambat = VALUES(batas_terlambat),
                    jam_pulang = VALUES(jam_pulang),
                    updated_at = NOW()
            ');
            $stmtSiswa->execute([
                $data['siswa_masuk'] ?? '07:00:00',
                $data['siswa_terlambat'] ?? '07:30:00',
                $data['siswa_pulang'] ?? '14:00:00'
            ]);

            // Update jadwal pegawai
            $stmtPeg = $pdo->prepare('
                INSERT INTO attendance_settings (tipe_user, jam_masuk, batas_terlambat, jam_pulang, created_at, updated_at)
                VALUES ("pegawai", ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    jam_masuk = VALUES(jam_masuk),
                    batas_terlambat = VALUES(batas_terlambat),
                    jam_pulang = VALUES(jam_pulang),
                    updated_at = NOW()
            ');
            $stmtPeg->execute([
                $data['pegawai_masuk'] ?? '07:30:00',
                $data['pegawai_terlambat'] ?? '08:00:00',
                $data['pegawai_pulang'] ?? '16:00:00'
            ]);

            echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Action 2: Enroll Card to User
    if ($action === 'enroll_card') {
        $userId = (int)($data['user_id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $jenis = strtoupper(trim($data['jenis'] ?? 'RFID'));

        if (!$userId || empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID dan Code kartu wajib diisi']);
            exit;
        }

        try {
            if ($jenis === 'RFID') {
                $stmt = $pdo->prepare('UPDATE users SET rfid_uid = ?, updated_at = NOW() WHERE id = ?');
            } else {
                $stmt = $pdo->prepare('UPDATE users SET barcode_uid = ?, updated_at = NOW() WHERE id = ?');
            }
            $stmt->execute([$code, $userId]);

            // Hapus dari antrean unregistered
            $stmtDel = $pdo->prepare('DELETE FROM unregistered_cards WHERE code = ?');
            $stmtDel->execute([$code]);

            echo json_encode(['success' => true, 'message' => "Kartu {$code} berhasil dipasangkan"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Action 3: Delete Unregistered Card
    if ($action === 'delete_card') {
        $cardId = (int)($data['id'] ?? 0);
        try {
            $stmt = $pdo->prepare('DELETE FROM unregistered_cards WHERE id = ?');
            $stmt->execute([$cardId]);
            echo json_encode(['success' => true, 'message' => 'Kartu dihapus']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// GET: Return Telemetry, Devices, Settings, and Cards
try {
    $devices = [];
    $stmtDev = $pdo->query('SELECT * FROM attendance_devices ORDER BY nama ASC');
    $nowTs = time();

    while ($row = $stmtDev->fetch(PDO::FETCH_ASSOC)) {
        $lastHeartbeat = $row['last_heartbeat'] ? strtotime($row['last_heartbeat']) : null;
        // Online jika heartbeat dalam 3 menit terakhir (180 detik)
        $isOnline = $lastHeartbeat && ($nowTs - $lastHeartbeat < 180);

        $rssi = $row['wifi_rssi'] !== null ? (int)$row['wifi_rssi'] : null;
        $signalLabel = 'Tidak Ada';
        $signalColor = 'slate';
        $signalPct = 0;

        if ($rssi !== null) {
            $signalPct = max(0, min(100, 2 * ($rssi + 100)));
            if ($rssi >= -60) {
                $signalLabel = 'Sangat Bagus';
                $signalColor = 'emerald';
            } elseif ($rssi >= -75) {
                $signalLabel = 'Bagus';
                $signalColor = 'teal';
            } elseif ($rssi >= -85) {
                $signalLabel = 'Cukup';
                $signalColor = 'amber';
            } else {
                $signalLabel = 'Lemah';
                $signalColor = 'rose';
            }
        }

        $lastSeen = 'Belum pernah';
        if ($lastHeartbeat) {
            $diff = $nowTs - $lastHeartbeat;
            if ($diff < 60) $lastSeen = 'Baru saja';
            elseif ($diff < 3600) $lastSeen = floor($diff / 60) . ' mnt lalu';
            else $lastSeen = floor($diff / 3600) . ' jam lalu';
        }

        $devices[] = [
            'id'                    => (int)$row['id'],
            'device_id'             => $row['device_id'],
            'nama'                  => $row['nama'],
            'lokasi'                => $row['lokasi'],
            'token'                 => $row['token'],
            'tipe_sensor'           => $row['tipe_sensor'] ?? 'COMBO',
            'is_online'             => $isOnline,
            'status'                => $isOnline ? 'online' : 'offline',
            'wifi_rssi'             => $rssi,
            'signal_quality'        => [
                'percentage' => $signalPct,
                'label'      => $signalLabel,
                'color'      => $signalColor,
                'dbm'        => $rssi
            ],
            'scans_today'           => (int)($row['scans_today'] ?? 0),
            'standby_text_override' => $row['standby_text_override'],
            'last_heartbeat'        => $row['last_heartbeat'],
            'last_seen'             => $lastSeen,
            'ip_address'            => $row['ip_address'] ?? '-',
            'firmware_version'      => $row['firmware_version'] ?? 'v2.0.0',
        ];
    }

    $onlineCount = count(array_filter($devices, fn($d) => $d['is_online']));
    $offlineCount = count($devices) - $onlineCount;
    $totalScans = array_sum(array_column($devices, 'scans_today'));

    $iotSettings = getIotSettings($pdo);

    // Ambil schedules
    $schedules = [
        'siswa' => ['jam_masuk' => '07:00', 'batas_terlambat' => '07:30', 'jam_pulang' => '14:00'],
        'pegawai' => ['jam_masuk' => '07:30', 'batas_terlambat' => '08:00', 'jam_pulang' => '16:00'],
    ];
    try {
        $stmtSched = $pdo->query('SELECT * FROM attendance_settings');
        while ($r = $stmtSched->fetch(PDO::FETCH_ASSOC)) {
            if (isset($schedules[$r['tipe_user']])) {
                $schedules[$r['tipe_user']] = [
                    'jam_masuk'       => substr($r['jam_masuk'], 0, 5),
                    'batas_terlambat' => substr($r['batas_terlambat'], 0, 5),
                    'jam_pulang'      => substr($r['jam_pulang'], 0, 5),
                ];
            }
        }
    } catch (Exception $e) {}

    // Unregistered cards
    $unregisteredCards = [];
    try {
        $stmtCards = $pdo->query('SELECT * FROM unregistered_cards ORDER BY last_scanned_at DESC LIMIT 15');
        $unregisteredCards = $stmtCards->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Users lookup
    $users = [];
    try {
        $stmtUsers = $pdo->query('SELECT id, name, role, department, identifier_number, rfid_uid, barcode_uid FROM users ORDER BY name ASC LIMIT 500');
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    echo json_encode([
        'summary' => [
            'total_devices' => count($devices),
            'online'        => $onlineCount,
            'offline'       => $offlineCount,
            'total_scans'   => $totalScans,
        ],
        'devices'            => $devices,
        'unregistered_cards' => $unregisteredCards,
        'users'              => $users,
        'settings'           => [
            'nama_lembaga'             => $iotSettings['nama_lembaga'],
            'standby_text'             => $iotSettings['standby_text'],
            'timezone'                 => $iotSettings['timezone'],
            'duplicate_window_seconds' => (int)$iotSettings['duplicate_window_seconds'],
            'schedules'                => $schedules,
        ],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
