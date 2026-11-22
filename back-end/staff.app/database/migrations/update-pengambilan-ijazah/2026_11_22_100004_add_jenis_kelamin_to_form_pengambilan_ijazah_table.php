<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('form_pengambilan_ijazah') && !Schema::hasColumn('form_pengambilan_ijazah', 'jenis_kelamin')) {
            Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('prodi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('form_pengambilan_ijazah') && Schema::hasColumn('form_pengambilan_ijazah', 'jenis_kelamin')) {
            Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
                $table->dropColumn('jenis_kelamin');
            });
        }
    }
};
