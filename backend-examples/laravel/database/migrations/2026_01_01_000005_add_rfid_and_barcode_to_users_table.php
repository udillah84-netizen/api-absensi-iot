<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rfid_uid')) {
                $table->string('rfid_uid', 64)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'barcode_uid')) {
                $table->string('barcode_uid', 64)->nullable()->unique()->after('rfid_uid');
            }
            if (!Schema::hasColumn('users', 'identifier_number')) {
                $table->string('identifier_number', 64)->nullable()->unique()->after('barcode_uid');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('identifier_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rfid_uid', 'barcode_uid', 'identifier_number', 'department']);
        });
    }
};
