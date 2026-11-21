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
        if (Schema::hasTable('surat_keterangan_6')) {
            Schema::table('surat_keterangan_6', function (Blueprint $table) {
                if (!Schema::hasColumn('surat_keterangan_6', 'signed_local_path')) {
                    $table->string('signed_local_path', 255)->nullable()->after('drive_link');
                }
                if (!Schema::hasColumn('surat_keterangan_6', 'signed_drive_file_id')) {
                    $table->string('signed_drive_file_id', 255)->nullable()->after('signed_local_path');
                }
                if (!Schema::hasColumn('surat_keterangan_6', 'signed_drive_link')) {
                    $table->string('signed_drive_link', 255)->nullable()->after('signed_drive_file_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('surat_keterangan_6')) {
            Schema::table('surat_keterangan_6', function (Blueprint $table) {
                $cols = array_filter([
                    Schema::hasColumn('surat_keterangan_6', 'signed_local_path') ? 'signed_local_path' : null,
                    Schema::hasColumn('surat_keterangan_6', 'signed_drive_file_id') ? 'signed_drive_file_id' : null,
                    Schema::hasColumn('surat_keterangan_6', 'signed_drive_link') ? 'signed_drive_link' : null,
                ]);
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
