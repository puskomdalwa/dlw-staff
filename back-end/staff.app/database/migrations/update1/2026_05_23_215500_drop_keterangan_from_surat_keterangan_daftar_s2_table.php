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
        if (Schema::hasTable('surat_keterangan_daftar_s2') && Schema::hasColumn('surat_keterangan_daftar_s2', 'keterangan')) {
            Schema::table('surat_keterangan_daftar_s2', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('surat_keterangan_daftar_s2') && !Schema::hasColumn('surat_keterangan_daftar_s2', 'keterangan')) {
            Schema::table('surat_keterangan_daftar_s2', function (Blueprint $table) {
                $table->string('keterangan')->nullable();
            });
        }
    }
};
