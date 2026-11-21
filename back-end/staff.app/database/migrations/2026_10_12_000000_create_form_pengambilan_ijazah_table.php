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
        Schema::create('form_pengambilan_ijazah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mhs', 255);
            $table->string('prodi', 255);
            $table->string('dosen_pembimbing', 255);
            $table->text('judul_skripsi');
            $table->date('tanggal_sidang');
            $table->string('no_hp', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_pengambilan_ijazah');
    }
};
