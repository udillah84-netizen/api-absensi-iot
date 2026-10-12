<?php

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

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
$fwVersion = trim($data['fw_version'] ?? 'v2.0.0');
$wifiRssi = isset($data['wifi_rssi']) ? (int)$data['wifi_rssi'] : null;
$scansToday = isset($data['scans_today']) ? (int)$data['scans_today'] : null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

if (!empty($deviceId)) {
    try {
        $stmt = $pdo->prepare('
            UPDATE attendance_devices 
            SET status = "online", 
                last_heartbeat = NOW(), 
                firmware_version = COALESCE(?, firmware_version),
                wifi_rssi = COALESCE(?, wifi_rssi),
                scans_today = COALESCE(?, scans_today),
                ip_address = COALESCE(?, ip_address)
            WHERE device_id = ?
        ');
        $stmt->execute([$fwVersion, $wifiRssi, $scansToday, $ipAddress, $deviceId]);
    } catch (Exception $e) {}
}

echo json_encode([
    'success'            => true,
    'server_time'        => date('c'),
    'heartbeat_interval' => 60,
    'message'            => 'Heartbeat OK',
]);
