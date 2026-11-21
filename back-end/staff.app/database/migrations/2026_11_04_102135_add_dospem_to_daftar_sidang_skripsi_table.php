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
        Schema::table('daftar_sidang_skripsi', function (Blueprint $table) {
            if (!Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing1')) {
                $table->string('pembimbing1')->nullable()->after('judul_skripsi');
            }
            if (!Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing2')) {
                $table->string('pembimbing2')->nullable()->after('pembimbing1');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daftar_sidang_skripsi', function (Blueprint $table) {
            if (Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing1')) {
                $table->dropColumn('pembimbing1');
            }
            if (Schema::hasColumn('daftar_sidang_skripsi', 'pembimbing2')) {
                $table->dropColumn('pembimbing2');
            }
        });
    }
};
