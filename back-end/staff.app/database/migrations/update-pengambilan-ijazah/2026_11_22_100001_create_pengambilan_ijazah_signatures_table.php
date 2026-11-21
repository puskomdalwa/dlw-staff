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
        Schema::create('pengambilan_ijazah_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_pengambilan_ijazah_id');
            $table->unsignedTinyInteger('step_order');
            $table->string('step_key', 50);
            $table->string('title');
            $table->string('signer_name')->nullable();
            $table->string('signer_nidn')->nullable();
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_source', 50)->nullable();
            $table->unsignedBigInteger('signed_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('signature_token', 64)->unique();
            $table->timestamps();

            $table->foreign('form_pengambilan_ijazah_id', 'pij_sig_form_fk')
                ->references('id')
                ->on('form_pengambilan_ijazah')
                ->onDelete('cascade');

            $table->unique(['form_pengambilan_ijazah_id', 'step_key'], 'pij_sig_form_step_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_ijazah_signatures');
    }
};
