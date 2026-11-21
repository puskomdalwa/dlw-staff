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
        Schema::create('surat_tugas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor');
            $table->string('pembimbing1');
            $table->string('alamat_pembimbing1');
            $table->string('tugas_pembimbing1');
            $table->text('tugasnya')->nullable();
            $table->string('pembimbing2')->nullable();
            $table->string('alamat_pembimbing2')->nullable();
            $table->string('tugas_pembimbing2')->nullable();
            $table->string('nama_mhs');
            $table->string('nim_nik');
            $table->text('judul_skripsi');
            $table->string('masa_penugasan');
            $table->integer('user_id');
            $table->integer('prodi_id');
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
        Schema::dropIfExists('surat_tugas');
    }
};
