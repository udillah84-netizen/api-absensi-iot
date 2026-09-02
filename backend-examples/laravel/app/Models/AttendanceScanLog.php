<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScanLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_scan_logs';

    protected $fillable = [
        'device_id',
        'code',
        'tipe_user',
        'user_id',
        'jenis',
        'status',
        'message',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
