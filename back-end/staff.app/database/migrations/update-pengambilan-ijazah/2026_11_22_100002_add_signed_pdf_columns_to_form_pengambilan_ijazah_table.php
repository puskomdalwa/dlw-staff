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
        Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
            $table->string('signed_local_path')->nullable()->after('drive_link');
            $table->string('signed_drive_file_id')->nullable()->after('signed_local_path');
            $table->string('signed_drive_link')->nullable()->after('signed_drive_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_pengambilan_ijazah', function (Blueprint $table) {
            $table->dropColumn(['signed_local_path', 'signed_drive_file_id', 'signed_drive_link']);
        });
    }
};
