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

if (!empty($deviceId)) {
    try {
        $stmt = $pdo->prepare('UPDATE attendance_devices SET status = "online", last_heartbeat = NOW(), firmware_version = ? WHERE device_id = ?');
        $stmt->execute([$fwVersion, $deviceId]);
    } catch (Exception $e) {}
}

echo json_encode([
    'success'            => true,
    'server_time'        => date('c'),
    'heartbeat_interval' => 60,
    'message'            => 'Heartbeat OK',
]);
