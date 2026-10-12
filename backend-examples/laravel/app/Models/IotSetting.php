<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IotSetting extends Model
{
    use HasFactory;

    protected $table = 'iot_settings';

    protected $fillable = [
        'nama_lembaga',
        'standby_text',
        'timezone',
        'duplicate_window_seconds',
    ];

    /**
     * Dapatkan setting aktif atau default jika belum ada di database
     */
    public static function getActive(): self
    {
        $setting = static::first();
        if (! $setting) {
            $setting = static::create([
                'nama_lembaga'             => config('iot.institution_name', 'SMK NEGERI 1 BOGOR'),
                'standby_text'             => config('iot.standby_text', 'SILAKAN ABSEN'),
                'timezone'                 => config('app.timezone', 'Asia/Jakarta'),
                'duplicate_window_seconds' => config('iot.duplicate_window_seconds', 30),
            ]);
        }

        return $setting;
    }
}
