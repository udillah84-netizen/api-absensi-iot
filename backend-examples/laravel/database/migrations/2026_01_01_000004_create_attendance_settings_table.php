<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tipe_user', 50)->unique(); // siswa, pegawai, default
            $table->time('jam_masuk')->default('07:00:00');
            $table->time('batas_terlambat')->default('07:30:00');
            $table->time('jam_pulang')->default('14:00:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
