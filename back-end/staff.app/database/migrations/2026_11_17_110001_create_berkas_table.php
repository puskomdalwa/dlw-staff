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
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mhs');
            $table->string('nim');
            $table->foreignId('kategori_berkas_id')->constrained('kategori_berkas')->onDelete('cascade');
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->text('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploaded', 'uplouded', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
