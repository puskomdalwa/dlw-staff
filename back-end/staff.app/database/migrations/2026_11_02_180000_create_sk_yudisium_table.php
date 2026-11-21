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
        Schema::create('sk_yudisium', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mhs');
            $table->string('nim');
            $table->string('prodi');
            $table->unsignedBigInteger('tanda_tangan_id')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'uplouded', 'failed'])->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();

            // Atribut pelengkap sistem
            $table->string('nomor_surat')->nullable();
            $table->string('nilai_skripsi')->nullable();
            $table->integer('prodi_id')->nullable();
            $table->enum('petanda_tangan', ['ya', 'tidak', 'stempel'])->default('tidak');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk_yudisium');
    }
};

