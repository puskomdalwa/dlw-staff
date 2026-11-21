<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_form_settings', function (Blueprint $table) {
            $table->id();
            $table->string('form_code')->default('pengambilan_ijazah')->unique();
            $table->string('slug')->unique();
            $table->string('title')->default('Formulir Pengambilan Ijazah');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expired_at')->nullable();
            $table->string('duration_type')->nullable(); // 'minutes', 'hours', 'days', 'indefinite'
            $table->integer('duration_value')->nullable();
            $table->integer('total_submissions')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Insert initial default setting for pengambilan_ijazah
        DB::table('public_form_settings')->insert([
            'form_code' => 'pengambilan_ijazah',
            'slug' => 'pengambilan-ijazah',
            'title' => 'Formulir Pengambilan Ijazah (Bebas Tanggungan)',
            'description' => 'Silakan isi formulir pengambilan ijazah dan bebas tanggungan berikut dengan data yang valid.',
            'is_active' => true,
            'expired_at' => now()->addDays(7),
            'duration_type' => 'days',
            'duration_value' => 7,
            'total_submissions' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_form_settings');
    }
};
