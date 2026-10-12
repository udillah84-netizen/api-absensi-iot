<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unregistered_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 128)->unique();
            $table->enum('jenis', ['RFID', 'BARCODE'])->default('RFID');
            $table->string('device_id', 50)->nullable();
            $table->dateTime('last_scanned_at');
            $table->unsignedInteger('tap_count')->default(1);
            $table->timestamps();

            $table->index('last_scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unregistered_cards');
    }
};
