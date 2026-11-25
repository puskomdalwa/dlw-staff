<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        if (
            DB::table('sk6_signer_defaults')->exists()
            && !DB::table('sk6_signer_defaults')->where('step_key', 'warek_1')->exists()
        ) {
            DB::table('sk6_signer_defaults')->insert([
                'step_order' => 7,
                'step_key' => 'warek_1',
                'title' => 'Wakil Rektor 1',
                'signer_name' => 'Dr. Muhamad Solehudin M.H.I.',
                'signer_nidn' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $warekDefault = DB::table('sk6_signer_defaults')->where('step_key', 'warek_1')->first();
        $warekTitle = $warekDefault?->title ?: 'Wakil Rektor 1';
        $warekSigner = $warekDefault?->signer_name ?: 'Dr. Muhamad Solehudin M.H.I.';
        $warekNidn = $warekDefault?->signer_nidn;

        $sk6Ids = DB::table('sk6_signatures')->distinct()->pluck('surat_keterangan_6_id');
        foreach ($sk6Ids as $sk6Id) {
            $hasWarek = DB::table('sk6_signatures')
                ->where('surat_keterangan_6_id', $sk6Id)
                ->where('step_key', 'warek_1')
                ->exists();

            if (!$hasWarek) {
                DB::table('sk6_signatures')->insert([
                    'surat_keterangan_6_id' => $sk6Id,
                    'step_order' => 7,
                    'step_key' => 'warek_1',
                    'title' => $warekTitle,
                    'signer_name' => $warekSigner,
                    'signer_nidn' => $warekNidn,
                    'status' => 'pending',
                    'signature_token' => Str::random(40),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sk6_signatures')->where('step_key', 'warek_1')->delete();
        DB::table('sk6_signer_defaults')->where('step_key', 'warek_1')->delete();
    }
};
