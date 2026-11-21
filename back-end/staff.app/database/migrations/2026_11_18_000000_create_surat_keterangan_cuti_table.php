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
        Schema::create('surat_keterangan_cuti', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mhs');
            $table->string('nim');
            $table->integer('prodi_id')->nullable();
            $table->string('dari_semester');
            $table->string('sampai_semester');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->text('alasan')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('petanda_tangan')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->text('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->string('ketua')->nullable();
            $table->string('nidn')->nullable();
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_keterangan_cuti');
    }
};
