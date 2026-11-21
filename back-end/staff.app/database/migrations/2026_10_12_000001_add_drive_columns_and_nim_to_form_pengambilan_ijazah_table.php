<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
            $table->string('nim', 50)->nullable()->after('nama_mhs');
            $table->string('drive_file_id', 255)->nullable()->after('no_hp');
            $table->string('local_path', 255)->nullable()->after('drive_file_id');
            $table->string('drive_link', 255)->nullable()->after('local_path');
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending')->after('drive_link');
            $table->unsignedBigInteger('user_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
            $table->dropColumn(['nim', 'drive_file_id', 'local_path', 'drive_link', 'status', 'user_id']);
        });
    }
};
