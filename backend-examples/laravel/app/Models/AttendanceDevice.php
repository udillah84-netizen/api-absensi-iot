<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDevice extends Model
{
    use HasFactory;

    protected $table = 'attendance_devices';

    protected $fillable = [
        'device_id',
        'nama',
        'lokasi',
        'token',
        'tipe_sensor',
        'status',
        'wifi_rssi',
        'scans_today',
        'standby_text_override',
        'last_heartbeat',
        'ip_address',
        'firmware_version',
    ];

    protected $casts = [
        'last_heartbeat' => 'datetime',
        'wifi_rssi'      => 'integer',
        'scans_today'    => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->token)) {
                $device->token = 'tok_' . bin2hex(random_bytes(16));
            }
            if (empty($device->device_id)) {
                $device->device_id = 'DEV-' . strtoupper(substr(uniqid(), -6));
            }
            if (empty($device->tipe_sensor)) {
                $device->tipe_sensor = 'COMBO';
            }
        });
    }

    /**
     * Memeriksa apakah perangkat masih dalam status online (heartbeat < X menit yang lalu)
     */
    public function isOnline(): bool
    {
        $timeout = config('iot.heartbeat_timeout_minutes', 3);
        return $this->last_heartbeat && $this->last_heartbeat->diffInMinutes(now()) < $timeout;
    }

    /**
     * Mendapatkan kualitas sinyal WiFi dalam teks dan persentase
     */
    public function getSignalQualityAttribute(): array
    {
        $rssi = $this->wifi_rssi;
        if ($rssi === null) {
            return ['percentage' => 0, 'label' => 'Tidak Ada Sinyal', 'color' => 'slate'];
        }

        // Formula persentase kualitas sinyal WiFi dBm (-100 dBm = 0%, -50 dBm = 100%)
        $quality = 2 * ($rssi + 100);
        $quality = max(0, min(100, $quality));

        if ($rssi >= -60) {
            $label = 'Sangat Bagus';
            $color = 'emerald';
        } elseif ($rssi >= -75) {
            $label = 'Bagus';
            $color = 'teal';
        } elseif ($rssi >= -85) {
            $label = 'Cukup';
            $color = 'amber';
        } else {
            $label = 'Lemah';
            $color = 'rose';
        }

        return [
            'percentage' => $quality,
            'label'      => $label,
            'color'      => $color,
            'dbm'        => $rssi,
        ];
    }

    /**
     * Memperbarui timestamp heartbeat, sinyal, dan status perangkat
     */
    public function updateHeartbeat(
        ?string $ip = null,
        ?string $firmware = null,
        ?int $wifiRssi = null,
        ?int $scansToday = null
    ): void {
        $data = [
            'status'         => 'online',
            'last_heartbeat' => now(),
        ];

        if ($ip) {
            $data['ip_address'] = $ip;
        }
        if ($firmware) {
            $data['firmware_version'] = $firmware;
        }
        if ($wifiRssi !== null) {
            $data['wifi_rssi'] = $wifiRssi;
        }
        if ($scansToday !== null) {
            $data['scans_today'] = $scansToday;
        }

        $this->update($data);
    }
}
