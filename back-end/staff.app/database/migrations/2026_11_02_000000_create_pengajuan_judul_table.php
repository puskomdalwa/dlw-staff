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
        Schema::create('pengajuan_judul', function (Blueprint $table) {
            $table->id();
            $table->string('nim');
            $table->string('nama_mhs');
            $table->string('prodi');
            $table->text('judul_proposal_skripsi');
            $table->text('rumusan_masalah');
            $table->string('dosen_penasehat');
            $table->text('catatan_kprodi')->nullable();
            $table->integer('tanda_tangan_id')->nullable()->comment('kprodi');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'uplouded', 'failed'])->default('pending');
            $table->integer('prodi_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('petanda_tangan', ['ya', 'tidak', 'stempel'])->default('tidak');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_judul');
    }
};

