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
        Schema::create('berkas_umum', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->foreignId('kategori_berkas_id')->nullable()->constrained('kategori_berkas')->nullOnDelete();
            $table->string('local_path')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->text('drive_link')->nullable();
            $table->enum('status', ['pending', 'uploud', 'failed'])->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_umum');
    }
};
