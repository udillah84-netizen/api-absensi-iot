<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnregisteredCard extends Model
{
    use HasFactory;

    protected $table = 'unregistered_cards';

    protected $fillable = [
        'code',
        'jenis',
        'device_id',
        'last_scanned_at',
        'tap_count',
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime',
    ];

    /**
     * Catat atau update tap kartu yang belum terdaftar (Auto-Enrollment Buffer)
     */
    public static function recordCard(string $code, string $jenis = 'RFID', ?string $deviceId = null): self
    {
        $card = static::where('code', $code)->first();

        if ($card) {
            $card->increment('tap_count');
            $card->update([
                'jenis'           => $jenis,
                'device_id'       => $deviceId,
                'last_scanned_at' => now(),
            ]);
        } else {
            $card = static::create([
                'code'            => $code,
                'jenis'           => $jenis,
                'device_id'       => $deviceId,
                'last_scanned_at' => now(),
                'tap_count'       => 1,
            ]);
        }

        return $card;
    }
}
