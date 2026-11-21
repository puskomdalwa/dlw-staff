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
        Schema::create('sk6_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keterangan_6_id')
                ->constrained('surat_keterangan_6')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('step_order');
            $table->string('step_key', 50);
            $table->string('title');
            $table->string('signer_name')->nullable();
            $table->string('signer_nidn', 100)->nullable();
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_source', 100)->nullable();
            $table->unsignedBigInteger('signed_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('signature_token', 64)->unique();
            $table->timestamps();

            $table->unique(['surat_keterangan_6_id', 'step_key'], 'sk6_sig_sk6_step_unique');
            $table->index(['surat_keterangan_6_id', 'step_order'], 'sk6_sig_sk6_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk6_signatures');
    }
};
