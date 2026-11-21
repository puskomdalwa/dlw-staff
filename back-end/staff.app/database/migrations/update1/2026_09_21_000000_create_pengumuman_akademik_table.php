<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengumuman_akademik', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->string('judul')->nullable();
            $table->string('kepada')->nullable()->default(null);
            $table->text('isi');
            $table->string('tempat')->default('Pasuruan');
            $table->date('tanggal');
            $table->unsignedBigInteger('tanda_tangan_id')->nullable();
            $table->enum('petanda_tangan', ['ya', 'tidak', 'stempel'])->default('tidak');
            $table->string('drive_file_id')->nullable();
            $table->string('local_path')->nullable();
            $table->string('drive_link')->nullable();
            $table->integer('user_id');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->enum('status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->timestamps();
        });

        // Seed jenis surat untuk Pengumuman Akademik jika belum ada
        if (Schema::hasTable('jenis_surat')) {
            if (DB::table('jenis_surat')->where('alias', 'PA')->count() == 0) {
                DB::table('jenis_surat')->insert([
                    'alias' => 'PA',
                    'nama' => 'PENGUMUMAN AKADEMIK',
                    'format_surat' => 'SU-{NO}/UII.085/BAAK/PP.00/{BULAN}/{TAHUN}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_akademik');
        if (Schema::hasTable('jenis_surat')) {
            DB::table('jenis_surat')->where('alias', 'PA')->delete();
        }
    }
};
