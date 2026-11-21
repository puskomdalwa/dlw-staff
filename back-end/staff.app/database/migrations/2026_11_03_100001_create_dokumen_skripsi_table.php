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
        Schema::create('dokumen_skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daftar_sidang_skripsi_id')->constrained('daftar_sidang_skripsi')->onDelete('cascade');
            $table->string('nama_dokumen');
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->text('drive_link')->nullable();
            $table->enum('status', ['pending', 'uplouded', 'uploaded', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_skripsi');
    }
};

