<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lembaga', 100)->default('SMK NEGERI 1 BOGOR');
            $table->string('standby_text', 50)->default('SILAKAN ABSEN');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->unsignedInteger('duplicate_window_seconds')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_settings');
    }
};
