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
            if (!Schema::hasColumn('berkas', 'nama_dokumen')) {
                $table->string('nama_dokumen')->nullable()->after('kategori_berkas_id');
            }
            $table->unsignedBigInteger('kategori_berkas_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            if (Schema::hasColumn('berkas', 'nama_dokumen')) {
                $table->dropColumn('nama_dokumen');
            }
        });
    }
};
