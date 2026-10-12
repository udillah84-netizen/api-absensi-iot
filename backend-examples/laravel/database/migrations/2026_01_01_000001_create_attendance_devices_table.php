<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->unique();
            $table->string('nama', 100);
            $table->string('lokasi', 100)->nullable();
            $table->string('token', 64)->unique();
            $table->string('tipe_sensor', 20)->default('COMBO'); // COMBO, RFID, BARCODE
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->integer('wifi_rssi')->nullable();
            $table->unsignedInteger('scans_today')->default(0);
            $table->string('standby_text_override', 50)->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('firmware_version', 20)->default('v2.0.0');
            $table->timestamps();

            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
