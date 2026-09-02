<?php

require_once __DIR__ . '/config.php';

/**
 * Mendapatkan instance koneksi PDO MySQL
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status'  => 'Database Error',
                'message' => 'Gagal terhubung ke database: ' . $e->getMessage(),
            ]);
            exit;
        }
    }

    return $pdo;
}

/**
 * Validasi otentikasi token mesin dari request headers
 */
function authenticateDeviceToken(PDO $pdo): ?array
{
    $headers = getallheaders();
    $token = $headers['X-Device-Token'] 
        ?? ($headers['x-device-token'] 
        ?? ($headers['X-IoT-Token'] 
        ?? ($headers['x-iot-token'] ?? '')));

    if (empty($token) && isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(\S+)/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    if (empty($token)) {
        return null;
    }

    // 1. Cek Token Global
    if (hash_equals(IOT_SECRET_TOKEN, $token)) {
        return ['is_global' => true, 'token' => $token];
    }

    // 2. Cek Token Unik Perangkat di Database
    try {
        $stmt = $pdo->prepare('SELECT * FROM attendance_devices WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $device = $stmt->fetch();
        if ($device) {
            return ['is_global' => false, 'device' => $device];
        }
    } catch (Exception $e) {
        // Fallback jika tabel belum ada
    }

    return null;
}

/**
 * Format nama agar maksimal 16 karakter untuk display LCD 16x2
 */
function formatLcdName(string $name): string
{
    $name = trim($name);
    if (mb_strlen($name) <= 16) {
        return $name;
    }

    $parts = explode(' ', $name);
    if (count($parts) > 1) {
        $shortened = $parts[0];
        for ($i = 1; $i < count($parts); $i++) {
            if (mb_strlen($shortened . ' ' . $parts[$i]) <= 16) {
                $shortened .= ' ' . $parts[$i];
            } else {
                $shortened .= ' ' . mb_substr($parts[$i], 0, 1) . '.';
                break;
            }
        }
        if (mb_strlen($shortened) <= 16) {
            return $shortened;
        }
    }

    return mb_substr($name, 0, 16);
}
