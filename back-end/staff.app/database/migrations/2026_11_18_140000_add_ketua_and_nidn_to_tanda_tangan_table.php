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
        if (Schema::hasTable('tanda_tangan')) {
            Schema::table('tanda_tangan', function (Blueprint $table) {
                if (!Schema::hasColumn('tanda_tangan', 'ketua')) {
                    $table->string('ketua')->nullable();
                }
                if (!Schema::hasColumn('tanda_tangan', 'nidn')) {
                    $table->string('nidn')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tanda_tangan')) {
            Schema::table('tanda_tangan', function (Blueprint $table) {
                if (Schema::hasColumn('tanda_tangan', 'ketua')) {
                    $table->dropColumn('ketua');
                }
                if (Schema::hasColumn('tanda_tangan', 'nidn')) {
                    $table->dropColumn('nidn');
                }
            });
        }
    }
};
