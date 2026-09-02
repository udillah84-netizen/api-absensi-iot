<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->nullable();
            $table->string('code', 128);
            $table->string('tipe_user', 50)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('jenis', ['RFID', 'BARCODE'])->default('RFID');
            $table->enum('status', ['success', 'failed']);
            $table->string('message')->nullable();
            $table->dateTime('scanned_at');
            $table->timestamps();

            $table->index(['device_id', 'scanned_at']);
            $table->index(['user_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scan_logs');
    }
};
