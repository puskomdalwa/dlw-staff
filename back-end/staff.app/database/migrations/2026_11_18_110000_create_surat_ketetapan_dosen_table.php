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
        Schema::create('surat_ketetapan_dosen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institusi_id')->nullable();
            $table->string('nomor')->nullable();
            $table->string('nama_dosen');
            $table->string('jabatan');
            $table->string('nidn')->nullable();
            $table->string('instansi');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('alamat_penandatangan')->nullable();
            $table->string('tempat_ditetapkan')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('petanda_tangan')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->text('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_ketetapan_dosen');
    }
};
