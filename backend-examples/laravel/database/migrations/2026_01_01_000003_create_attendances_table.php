<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('status', ['Hadir', 'Telat', 'Izin', 'Sakit', 'Alpha'])->default('Hadir');
            $table->string('device_id_masuk', 50)->nullable();
            $table->string('device_id_pulang', 50)->nullable();
            $table->enum('metode_masuk', ['RFID', 'BARCODE'])->default('RFID');
            $table->enum('metode_pulang', ['RFID', 'BARCODE'])->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
