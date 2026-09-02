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
        'status',
        'last_heartbeat',
        'ip_address',
        'firmware_version',
    ];

    protected $casts = [
        'last_heartbeat' => 'datetime',
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
        });
    }

    /**
     * Memeriksa apakah perangkat masih dalam status online (heartbeat < X menit yang lalu)
     */
    public function isOnline(): bool
    {
        $timeout = config('iot.heartbeat_timeout_minutes', 5);
        return $this->last_heartbeat && $this->last_heartbeat->diffInMinutes(now()) < $timeout;
    }

    /**
     * Memperbarui timestamp heartbeat dan status perangkat
     */
    public function updateHeartbeat(?string $ip = null, ?string $firmware = null): void
    {
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

        $this->update($data);
    }
}
