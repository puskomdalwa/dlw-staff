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
        if (Schema::hasTable('pengajuan_judul') && !Schema::hasColumn('pengajuan_judul', 'status_sidang')) {
            Schema::table('pengajuan_judul', function (Blueprint $table) {
                $table->enum('status_sidang', ['sudah', 'belum'])
                    ->nullable()
                    ->default('belum')
                    ->after('petanda_tangan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengajuan_judul') && Schema::hasColumn('pengajuan_judul', 'status_sidang')) {
            Schema::table('pengajuan_judul', function (Blueprint $table) {
                $table->dropColumn('status_sidang');
            });
        }
    }
};
