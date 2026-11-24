<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const NEW_ORDER = [
        'keuangan_pondok' => 1,
        'baku_kabag' => 2,
        'baku_aupk' => 3,
        'baak' => 4,
        'warek_1' => 5,
    ];

    private const PREVIOUS_ORDER = [
        'keuangan_pondok' => 1,
        'baku' => 2,
        'baak' => 3,
        'warek_1' => 4,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Shift existing orders for baak & warek_1
        foreach (['keuangan_pondok' => 1, 'baak' => 4, 'warek_1' => 5] as $key => $order) {
            DB::table('pengambilan_ijazah_signer_defaults')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);

            DB::table('pengambilan_ijazah_signatures')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);
        }

        // 2. Migrate legacy 'baku' default -> 'baku_aupk' and seed 'baku_kabag' default if needed
        $hasAupkDefault = DB::table('pengambilan_ijazah_signer_defaults')->where('step_key', 'baku_aupk')->exists();
        $legacyBakuDefault = DB::table('pengambilan_ijazah_signer_defaults')->where('step_key', 'baku')->first();

        if ($legacyBakuDefault) {
            if (!$hasAupkDefault) {
                $newTitle = $legacyBakuDefault->title === 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)'
                    ? 'Kepala Biro AUPK (Keuangan Jamiah)'
                    : $legacyBakuDefault->title;

                DB::table('pengambilan_ijazah_signer_defaults')
                    ->where('id', $legacyBakuDefault->id)
                    ->update([
                        'step_key' => 'baku_aupk',
                        'step_order' => 3,
                        'title' => $newTitle,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('pengambilan_ijazah_signer_defaults')->where('id', $legacyBakuDefault->id)->delete();
            }
        }

        if (DB::table('pengambilan_ijazah_signer_defaults')->exists() && !DB::table('pengambilan_ijazah_signer_defaults')->where('step_key', 'baku_kabag')->exists()) {
            DB::table('pengambilan_ijazah_signer_defaults')->insert([
                'step_order' => 2,
                'step_key' => 'baku_kabag',
                'title' => 'Kabag BAKU (Keuangan Jamiah)',
                'signer_name' => 'Kabag BAKU',
                'signer_nidn' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Migrate legacy 'baku' signature rows -> 'baku_aupk' and insert missing 'baku_kabag' rows
        $kabagDefault = DB::table('pengambilan_ijazah_signer_defaults')->where('step_key', 'baku_kabag')->first();
        $kabagTitle = $kabagDefault?->title ?: 'Kabag BAKU (Keuangan Jamiah)';
        $kabagSigner = $kabagDefault?->signer_name ?: 'Kabag BAKU';
        $kabagNidn = $kabagDefault?->signer_nidn;

        $formIds = DB::table('pengambilan_ijazah_signatures')->distinct()->pluck('form_pengambilan_ijazah_id');
        foreach ($formIds as $formId) {
            $hasAupk = DB::table('pengambilan_ijazah_signatures')
                ->where('form_pengambilan_ijazah_id', $formId)
                ->where('step_key', 'baku_aupk')
                ->exists();

            $legacyBaku = DB::table('pengambilan_ijazah_signatures')
                ->where('form_pengambilan_ijazah_id', $formId)
                ->where('step_key', 'baku')
                ->first();

            if ($legacyBaku) {
                if (!$hasAupk) {
                    $newTitle = $legacyBaku->title === 'Kepala Biro Administrasi Keuangan (Keuangan Jamiah)'
                        ? 'Kepala Biro AUPK (Keuangan Jamiah)'
                        : $legacyBaku->title;

                    DB::table('pengambilan_ijazah_signatures')
                        ->where('id', $legacyBaku->id)
                        ->update([
                            'step_key' => 'baku_aupk',
                            'step_order' => 3,
                            'title' => $newTitle,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('pengambilan_ijazah_signatures')->where('id', $legacyBaku->id)->delete();
                }
            }

            $hasKabag = DB::table('pengambilan_ijazah_signatures')
                ->where('form_pengambilan_ijazah_id', $formId)
                ->where('step_key', 'baku_kabag')
                ->exists();

            if (!$hasKabag) {
                DB::table('pengambilan_ijazah_signatures')->insert([
                    'form_pengambilan_ijazah_id' => $formId,
                    'step_order' => 2,
                    'step_key' => 'baku_kabag',
                    'title' => $kabagTitle,
                    'signer_name' => $kabagSigner,
                    'signer_nidn' => $kabagNidn,
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
        $now = now();

        DB::table('pengambilan_ijazah_signatures')->where('step_key', 'baku_kabag')->delete();
        DB::table('pengambilan_ijazah_signer_defaults')->where('step_key', 'baku_kabag')->delete();

        DB::table('pengambilan_ijazah_signer_defaults')
            ->where('step_key', 'baku_aupk')
            ->update([
                'step_key' => 'baku',
                'step_order' => self::PREVIOUS_ORDER['baku'],
                'updated_at' => $now,
            ]);

        DB::table('pengambilan_ijazah_signatures')
            ->where('step_key', 'baku_aupk')
            ->update([
                'step_key' => 'baku',
                'step_order' => self::PREVIOUS_ORDER['baku'],
                'updated_at' => $now,
            ]);

        foreach (self::PREVIOUS_ORDER as $key => $order) {
            DB::table('pengambilan_ijazah_signer_defaults')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);

            DB::table('pengambilan_ijazah_signatures')
                ->where('step_key', $key)
                ->update(['step_order' => $order, 'updated_at' => $now]);
        }
    }
};
