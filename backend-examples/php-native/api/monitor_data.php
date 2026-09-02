<?php
/**
 * ============================================================================
 * STARTER KIT PHP NATIVE - DATA LIVE MONITORING FEED (JSON)
 * ============================================================================
 * Endpoint  : GET /api/monitor_data.php
 * Kegunaan  : Menyuplai data scan absensi hari ini ke halaman monitor.php
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

try {
    $pdo = getDbConnection();
    $today = date('Y-m-d');

    // Ambil 24 data absensi terbaru hari ini
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.status,
            a.check_in_time,
            a.check_out_time,
            a.device_id,
            a.updated_at,
            u.name,
            u.class_or_department,
            u.avatar_url
        FROM attendances a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.date = :today
        ORDER BY a.updated_at DESC
        LIMIT 24
    ");
    $stmt->execute([':today' => $today]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $records = [];
    foreach ($rows as $row) {
        $name = $row['name'] ?? 'Pengguna';
        $inTime = $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '--:--';
        $outTime = $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '--:--';

        // Format waktu relatif sederhana
        $updatedTs = strtotime($row['updated_at'] ?? 'now');
        $diffSec = time() - $updatedTs;
        if ($diffSec < 60) {
            $timeFormatted = 'Baru saja';
        } elseif ($diffSec < 3600) {
            $timeFormatted = floor($diffSec / 60) . ' mnt lalu';
        } else {
            $timeFormatted = floor($diffSec / 3600) . ' jam lalu';
        }

        $records[] = [
            'id'             => (int)$row['id'],
            'user_id'        => (int)$row['user_id'],
            'nama'           => $name,
            'inisial'        => mb_strtoupper(mb_substr($name, 0, 1)),
            'kelas'          => $row['class_or_department'] ?? 'Siswa/Staff',
            'foto'           => $row['avatar_url'] ?? null,
            'jam_masuk'      => $inTime,
            'jam_pulang'     => $outTime,
            'status'         => $row['status'] ?? 'Hadir',
            'device_id'      => $row['device_id'] ?? 'GERBANG-01',
            'time_formatted' => $timeFormatted,
            'updated_at'     => $row['updated_at'],
        ];
    }

    // Statistik Ringkasan
    $statStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_today,
            SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = 'Telat' THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN check_out_time IS NOT NULL THEN 1 ELSE 0 END) as pulang
        FROM attendances
        WHERE date = :today
    ");
    $statStmt->execute([':today' => $today]);
    $stats = $statStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_today' => 0,
        'hadir' => 0,
        'telat' => 0,
        'pulang' => 0
    ];

    $hariIndonesia = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulanIndonesia = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $waktuLengkap = $hariIndonesia[date('w')] . ', ' . date('j') . ' ' . $bulanIndonesia[(int)date('n')] . ' ' . date('Y');

    echo json_encode([
        'records' => $records,
        'stats'   => [
            'total_today' => (int)($stats['total_today'] ?? 0),
            'hadir'       => (int)($stats['hadir'] ?? 0),
            'telat'       => (int)($stats['telat'] ?? 0),
            'pulang'      => (int)($stats['pulang'] ?? 0),
            'server_time' => date('H:i:s'),
            'server_date' => $waktuLengkap,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
