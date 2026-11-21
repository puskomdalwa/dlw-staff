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
        Schema::create('pengambilan_ijazah_signer_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('step_order');
            $table->string('step_key', 50)->unique();
            $table->string('title');
            $table->string('signer_name')->nullable();
            $table->string('signer_nidn')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_ijazah_signer_defaults');
    }
};
