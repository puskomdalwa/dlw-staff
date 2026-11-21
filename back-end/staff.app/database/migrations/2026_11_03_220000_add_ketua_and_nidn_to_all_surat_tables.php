<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * List of all surat tables.
     */
    protected array $tables = [
        'surat_tugas',
        'surat_izin_penelitian',
        'surat_keterangan',
        'surat_keterangan_aktif_mahasiswa',
        'surat_keterangan_lulus_mata_kuliah',
        'surat_keterangan_ujian_komprehensif_diniyah',
        'surat_keterangan_administrasi_keuangan',
        'surat_keterangan_tasma_kkn_ppl',
        'surat_keterangan_kkn',
        'surat_keterangan_ppl',
        'surat_keterangan_qismul_aman',
        'surat_keterangan_spm',
        'surat_keterangan_daftar_s2',
        'surat_keterangan_transfer',
        'surat_pernyataan_verifikasi_nilai',
        'hasil_rapat',
        'pengumuman_akademik',
        'sk_yudisium',
        'surat_keterangan_6',
    ];

    /**
     * Tables that already had 'ketua' column before this migration.
     */
    protected array $originallyHadKetua = [
        'surat_keterangan_tasma_kkn_ppl',
        'surat_keterangan_kkn',
        'surat_keterangan_ppl',
        'surat_keterangan_qismul_aman',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'ketua')) {
                        $table->string('ketua')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'nidn')) {
                        $table->string('nidn')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!in_array($tableName, $this->originallyHadKetua) && Schema::hasColumn($tableName, 'ketua')) {
                        $table->dropColumn('ketua');
                    }
                    if (Schema::hasColumn($tableName, 'nidn')) {
                        $table->dropColumn('nidn');
                    }
                });
            }
        }
    }
};

