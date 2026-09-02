<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $table = 'attendance_settings';

    protected $fillable = [
        'tipe_user',
        'jam_masuk',
        'batas_terlambat',
        'jam_pulang',
    ];

    /**
     * Mendapatkan setting jam berdasarkan role/tipe user (fallback ke 'siswa' atau default)
     */
    public static function getForRole(?string $role = 'siswa'): self
    {
        return self::where('tipe_user', $role)->first() 
            ?? self::where('tipe_user', 'siswa')->first()
            ?? new self([
                'tipe_user'       => 'default',
                'jam_masuk'       => '07:00:00',
                'batas_terlambat' => '07:30:00',
                'jam_pulang'      => '14:00:00',
            ]);
    }
}
