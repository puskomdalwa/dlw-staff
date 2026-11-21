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
        Schema::table('berkas', function (Blueprint $table) {
            if (!Schema::hasColumn('berkas', 'tipe_berkas')) {
                $table->enum('tipe_berkas', ['non_skripsi', 'skripsi'])->default('non_skripsi')->after('kategori_berkas_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            if (Schema::hasColumn('berkas', 'tipe_berkas')) {
                $table->dropColumn('tipe_berkas');
            }
        });
    }
};
