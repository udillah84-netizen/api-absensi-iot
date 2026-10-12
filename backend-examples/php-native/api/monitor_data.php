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
            a.jam_masuk,
            a.jam_pulang,
            a.device_id_masuk,
            a.updated_at,
            u.name,
            u.department,
            NULL as avatar_url
        FROM attendances a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.tanggal = :today
        ORDER BY a.updated_at DESC
        LIMIT 24
    ");
    $stmt->execute([':today' => $today]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $records = [];
    foreach ($rows as $row) {
        $name = $row['name'] ?? 'Pengguna';
        $inTime = !empty($row['jam_masuk']) ? substr($row['jam_masuk'], 0, 5) : '--:--';
        $outTime = !empty($row['jam_pulang']) ? substr($row['jam_pulang'], 0, 5) : '--:--';

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
            'kelas'          => $row['department'] ?? 'Siswa/Staff',
            'foto'           => $row['avatar_url'] ?? null,
            'jam_masuk'      => $inTime,
            'jam_pulang'     => $outTime,
            'status'         => $row['status'] ?? 'Hadir',
            'device_id'      => $row['device_id_masuk'] ?? 'GERBANG-01',
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
            SUM(CASE WHEN jam_pulang IS NOT NULL THEN 1 ELSE 0 END) as pulang
        FROM attendances
        WHERE tanggal = :today
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
