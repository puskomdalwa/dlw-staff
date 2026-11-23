<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REVISED_ORDER = [
        'kaprodi' => 1,
        'kkn' => 2,
        'keamanan' => 3,
        'baku' => 4,
        'idaroh_syuun' => 5,
    ];

    private const PREVIOUS_ORDER = [
        'kaprodi' => 1,
        'baku' => 2,
        'kkn' => 3,
        'idaroh_syuun' => 4,
        'keamanan' => 5,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::REVISED_ORDER as $stepKey => $order) {
            DB::table('sk6_signatures')
                ->where('step_key', $stepKey)
                ->update(['step_order' => $order]);

            DB::table('sk6_signer_defaults')
                ->where('step_key', $stepKey)
                ->update(['step_order' => $order]);
        }

        DB::table('sk6_signatures')
            ->where('step_key', 'idaroh_syuun')
            ->where('title', 'Wakil Rektor I')
            ->update(['title' => 'Idaroh Syuun']);

        DB::table('sk6_signer_defaults')
            ->where('step_key', 'idaroh_syuun')
            ->where('title', 'Wakil Rektor I')
            ->update(['title' => 'Idaroh Syuun']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::PREVIOUS_ORDER as $stepKey => $order) {
            DB::table('sk6_signatures')
                ->where('step_key', $stepKey)
                ->update(['step_order' => $order]);

            DB::table('sk6_signer_defaults')
                ->where('step_key', $stepKey)
                ->update(['step_order' => $order]);
        }

        DB::table('sk6_signatures')
            ->where('step_key', 'idaroh_syuun')
            ->where('title', 'Idaroh Syuun')
            ->update(['title' => 'Wakil Rektor I']);

        DB::table('sk6_signer_defaults')
            ->where('step_key', 'idaroh_syuun')
            ->where('title', 'Idaroh Syuun')
            ->update(['title' => 'Wakil Rektor I']);
    }
};
