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
        Schema::create('surat_keterangan_sudah_sidang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->string('nama');
            $table->string('nim');
            $table->string('prodi');
            $table->unsignedBigInteger('prodi_id')->nullable();
            $table->date('tanggal_sidang');
            $table->string('kepala')->nullable();
            $table->string('nidn')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // Field pendukung standar surat
            $table->date('tanggal')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('petanda_tangan', ['ya', 'tidak', 'stempel'])->default('tidak');
            $table->unsignedBigInteger('tanda_tangan_id')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keterangan_sudah_sidang');
    }
};

