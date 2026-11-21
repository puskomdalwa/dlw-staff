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
        Schema::create('mahasiswa_sidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_sidang_id')
                ->constrained('jadwal_sidang')
                ->onDelete('cascade');
            $table->foreignId('pengajuan_judul_id')
                ->constrained('pengajuan_judul')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['jadwal_sidang_id', 'pengajuan_judul_id'], 'mahasiswa_sidang_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_sidang');
    }
};
